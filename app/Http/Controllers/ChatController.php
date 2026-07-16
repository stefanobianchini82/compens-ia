<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\AI\PlainText;
use App\AI\StudyAgentFactory;
use App\AI\TextToSpeechFactory;
use App\Http\Controllers\Concerns\InteractsWithChatSession;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\StreamedEvent;
use NeuronAI\Chat\History\ChatHistoryInterface;
use NeuronAI\Chat\History\FileChatHistory;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\ContentBlocks\AudioContent;
use NeuronAI\Chat\Messages\Stream\Chunks\TextChunk;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Chat\Messages\UserMessage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

use function base64_decode;
use function count;
use function end;
use function mb_strlen;

class ChatController extends Controller
{
    use InteractsWithChatSession;

    public function stream(Request $request, StudyAgentFactory $factory): StreamedResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
            'subject_id' => ['required', 'exists:subjects,id'],
        ]);

        $subject = Subject::findOrFail($validated['subject_id']);
        $chat = $this->currentChatSession($request);

        if ($chat->subject_id !== $subject->id) {
            // Passando da una materia reale a un'altra svuotiamo la chat: la
            // cronologia (e la context window) non deve mai mescolare materie
            // diverse. Garanzia server-side anche se il cambio non passa dal modale.
            if ($chat->subject_id !== null) {
                $this->clearChatSession($chat);
            }
            $chat->update(['subject_id' => $subject->id]);
        }

        $text = $validated['message'];

        // Salviamo subito il messaggio dell'utente (per la UI e le statistiche).
        $chat->messages()->create([
            'role' => ChatMessage::ROLE_USER,
            'content' => $text,
        ]);

        return response()->eventStream(function () use ($factory, $subject, $chat, $text) {
            $agent = $factory->forChat($subject, $chat);
            $embedBefore = $agent->embeddingsProvider()->getTotalTokens();

            try {
                $events = $agent->stream(new UserMessage($text))->events();

                foreach ($events as $chunk) {
                    if ($chunk instanceof TextChunk && $chunk->content !== '') {
                        yield new StreamedEvent('delta', json_encode(['content' => $chunk->content]));
                    }
                }

                // Il valore di ritorno del generatore è lo stato finale del workflow.
                $message = $events->getReturn()->getMessage();
                $usage = $message->getUsage();
                $embedTokens = $agent->embeddingsProvider()->getTotalTokens() - $embedBefore;

                $assistantMessage = $chat->messages()->create([
                    'role' => ChatMessage::ROLE_ASSISTANT,
                    'content' => $message->getContent() ?? '',
                    'input_tokens' => $usage?->inputTokens ?? 0,
                    'output_tokens' => $usage?->outputTokens ?? 0,
                    'embed_tokens' => max(0, $embedTokens),
                ]);

                yield new StreamedEvent('done', json_encode([
                    'sessionTokens' => $chat->totalTokens(),
                    'totalTokens' => ChatMessage::grandTotalTokens(),
                    // Serve al client per richiedere il TTS server-side di questa risposta.
                    'messageId' => $assistantMessage->id,
                ]));
            } catch (Throwable $e) {
                report($e);

                // Un turno fallito lascia un messaggio "user" spaiato nella
                // cronologia: lo ripuliamo così il turno successivo riparte pulito.
                $this->repairHistory($agent->getChatHistory());

                yield new StreamedEvent('error', json_encode([
                    'message' => 'Ops! Qualcosa non ha funzionato. Controlla le impostazioni '
                        .'(provider e API key) e riprova.',
                ]));
            }
        }, [], null);
    }

    /**
     * Sintetizza ad alta voce (Text-to-Speech OpenAI) una risposta già salvata.
     * Restituisce l'audio MP3 e conta i caratteri letti (contatore TTS separato
     * dai token). Se il TTS server-side non è disponibile (motore "browser",
     * provider Gemini o key mancante) risponde 422: il client ripiega sulla voce
     * del dispositivo (Web Speech API).
     */
    public function tts(Request $request, TextToSpeechFactory $factory): Response|JsonResponse
    {
        $validated = $request->validate([
            'message_id' => ['required', 'exists:chat_messages,id'],
        ]);

        if (! $factory->available()) {
            return response()->json([
                'message' => 'Lettura OpenAI non disponibile: uso la voce del dispositivo.',
            ], 422);
        }

        $message = ChatMessage::findOrFail($validated['message_id']);

        $plain = PlainText::fromMarkdown($message->content);
        if ($plain === '') {
            return response()->json(['message' => 'Niente da leggere.'], 422);
        }

        try {
            $result = $factory->make()->chat(new UserMessage($plain));
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Non riesco a generare l\'audio in questo momento.',
            ], 422);
        }

        $base64 = null;
        foreach ($result->getContentBlocks() as $block) {
            if ($block instanceof AudioContent) {
                $base64 = $block->getContent();
                break;
            }
        }

        if ($base64 === null) {
            return response()->json(['message' => 'Audio non disponibile.'], 422);
        }

        // Conteggio separato dai token: caratteri effettivamente inviati al TTS.
        $message->increment('tts_chars', mb_strlen($plain));

        return response(base64_decode($base64), 200, [
            'Content-Type' => 'audio/mpeg',
            'Cache-Control' => 'no-store',
        ]);
    }

    /**
     * Svuota la chat della sessione corrente (pulsante "Svuota chat" o cambio
     * materia via fetch). La sessione HTTP e l'id della ChatSession restano
     * invariati. Risponde 204 alle richieste AJAX, altrimenti torna in dashboard.
     */
    public function reset(Request $request): RedirectResponse|Response
    {
        $chat = $this->currentChatSession($request);

        $this->clearChatSession($chat);

        if ($request->expectsJson()) {
            return response()->noContent();
        }

        return redirect()->route('dashboard')->with('status', 'Chat svuotata.');
    }

    /**
     * Cancella i messaggi salvati della sessione (azzera i contatori token) e
     * ripulisce la cronologia su file di NeuronAI.
     */
    private function clearChatSession(ChatSession $chat): void
    {
        $chat->messages()->delete();

        // Stessa chiave/directory di StudyAgentFactory::forChat + StudyAgent::chatHistory:
        // ricreiamo il FileChatHistory senza costruire l'agente (che richiederebbe
        // provider/API key e una materia). flushAll() elimina il file su disco.
        (new FileChatHistory(
            directory: storage_path('app/history'),
            key: 'session-'.$chat->getKey(),
            contextWindow: 50000,
        ))->flushAll();
    }

    /**
     * Rimuove dalla coda della cronologia i messaggi "spaiati" (un user senza
     * risposta assistant), lasciando solo i turni completati. Evita l'errore di
     * "sequenza non valida" al turno successivo.
     */
    private function repairHistory(ChatHistoryInterface $history): void
    {
        $messages = $history->getMessages();
        $original = count($messages);

        while ($messages !== []) {
            $last = end($messages);
            if ($last instanceof AssistantMessage && ! $last instanceof ToolCallMessage) {
                break;
            }
            array_pop($messages);
        }

        if (count($messages) === $original) {
            return;
        }

        $history->flushAll();
        foreach ($messages as $message) {
            $history->addMessage($message);
        }
    }
}
