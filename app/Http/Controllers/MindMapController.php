<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\AI\MindMapFactory;
use App\AI\MindMapPrompt;
use App\AI\PlainText;
use App\Models\ChatMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use NeuronAI\Chat\Messages\UserMessage;
use Throwable;

use function preg_replace;
use function str_contains;
use function strpos;
use function substr;
use function trim;

/**
 * Genera una mappa concettuale (Mermaid «mindmap») a partire da una risposta
 * già salvata dell'assistente. È uno strumento compensativo DSA: aiuta a vedere
 * i concetti e i loro collegamenti a colpo d'occhio.
 */
class MindMapController extends Controller
{
    public function generate(Request $request, MindMapFactory $factory): JsonResponse
    {
        $validated = $request->validate([
            'message_id' => ['required', 'exists:chat_messages,id'],
        ]);

        if (! $factory->available()) {
            return response()->json([
                'message' => __('messages.mindmap_unavailable'),
            ], 422);
        }

        $message = ChatMessage::findOrFail($validated['message_id']);

        // Diamo al modello il testo pulito dal Markdown: bastano i concetti.
        $plain = PlainText::fromMarkdown($message->content);
        if ($plain === '') {
            return response()->json(['message' => __('messages.mindmap_no_text')], 422);
        }

        try {
            $provider = $factory->make();
            $provider->systemPrompt((string) new MindMapPrompt());
            $result = $provider->chat(new UserMessage($plain));
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => __('messages.mindmap_failed'),
            ], 422);
        }

        $mermaid = $this->normalizeMermaid((string) $result->getContent());
        if ($mermaid === '') {
            return response()->json(['message' => __('messages.mindmap_empty')], 422);
        }

        return response()->json(['mermaid' => $mermaid]);
    }

    /**
     * Ripulisce l'output del modello: toglie eventuali recinti di codice ```,
     * ritaglia dal primo «mindmap» in poi e verifica che sia una mappa valida.
     */
    private function normalizeMermaid(string $raw): string
    {
        $text = trim($raw);

        // Rimuove i recinti di codice (```mermaid … ``` o ``` … ```).
        $text = (string) preg_replace('/^```[a-zA-Z]*\s*|\s*```$/m', '', $text);
        $text = trim($text);

        // Deve contenere il blocco «mindmap»: partiamo da lì, scartando eventuale
        // testo introduttivo che il modello potrebbe aver aggiunto per errore.
        if (! str_contains($text, 'mindmap')) {
            return '';
        }
        $start = strpos($text, 'mindmap');

        return trim(substr($text, (int) $start));
    }
}
