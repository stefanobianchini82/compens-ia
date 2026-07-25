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

use function array_pop;
use function count;
use function end;
use function implode;
use function ltrim;
use function preg_match;
use function preg_replace;
use function preg_split;
use function str_contains;
use function str_repeat;
use function strcspn;
use function strlen;
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
        $clean = trim(substr($text, (int) $start));
        if ($clean === '') {
            return '';
        }

        // Differenzia i nodi per FORMA invece che per colore (accessibilità DSA):
        // radice a cerchio, rami principali a esagono, sotto-rami a rettangolo.
        // Se qualcosa va storto sul testo del modello, si ripiega sul testo pulito.
        try {
            $shaped = $this->applyShapes($clean);
        } catch (Throwable $e) {
            report($e);

            return $clean;
        }

        return $shaped !== '' ? $shaped : $clean;
    }

    /**
     * Assegna a ogni nodo una forma in base alla profondità, riscrivendo le righe
     * Mermaid. La profondità è ricavata dall'indentazione tramite uno stack, così
     * il risultato è robusto anche se il modello non usa esattamente due spazi per
     * livello. Le etichette sono garantite «solo lettere, numeri e spazi» dal
     * prompt, quindi avvolgerle nei delimitatori di forma è sicuro; il passaggio è
     * inoltre idempotente (rimuove eventuali delimitatori già presenti).
     *
     * Forme: livello 0 → cerchio «(( ))», livello 1 → esagono «{{ }}»,
     * livello ≥2 → rettangolo «[ ]».
     */
    private function applyShapes(string $mermaid): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $mermaid);
        if ($lines === false || $lines === []) {
            return $mermaid;
        }

        $out = [];
        $stack = [];        // indentazioni degli antenati, per ricavare la profondità
        $seenRoot = false;  // già superata la prima riga «mindmap»?

        foreach ($lines as $line) {
            // Righe vuote intatte.
            if (trim($line) === '') {
                $out[] = $line;

                continue;
            }

            // La riga d'intestazione «mindmap» resta com'è.
            if (! $seenRoot && preg_match('/^\s*mindmap\b/', $line) === 1) {
                $out[] = $line;
                $seenRoot = true;

                continue;
            }

            $indent = strlen($line) - strlen(ltrim($line, " \t"));
            $content = ltrim($line, " \t");

            // Profondità relativa: si scartano gli antenati con indentazione ≥ a questa.
            while ($stack !== [] && end($stack) >= $indent) {
                array_pop($stack);
            }
            $depth = count($stack);
            $stack[] = $indent;

            $label = $this->bareLabel($content);
            if ($label === '') {
                $out[] = $line;

                continue;
            }

            $out[] = str_repeat(' ', $indent).$this->wrapByDepth($label, $depth);
        }

        return implode("\n", $out);
    }

    /**
     * Estrae l'etichetta «nuda» di un nodo: rimuove un eventuale id/prefisso e i
     * delimitatori di forma già presenti (es. «root((Teatro))» → «Teatro»,
     * «id1[Cavea]» → «Cavea»). Le etichette semplici (senza delimitatori) restano
     * invariate.
     */
    private function bareLabel(string $content): string
    {
        $c = trim($content);

        // Se ci sono delimitatori di forma, scarta l'eventuale prefisso prima del
        // primo delimitatore e ritaglia i delimitatori iniziali/finali.
        if (preg_match('/[()\[\]{}]/', $c) === 1) {
            $c = substr($c, strcspn($c, '()[]{}'));
            $c = trim($c, "()[]{}");
        }

        return trim($c);
    }

    /** Avvolge l'etichetta nella forma corrispondente al livello di profondità. */
    private function wrapByDepth(string $label, int $depth): string
    {
        return match (true) {
            $depth <= 0 => '(('.$label.'))',
            $depth === 1 => '{{'.$label.'}}',
            default => '['.$label.']',
        };
    }
}
