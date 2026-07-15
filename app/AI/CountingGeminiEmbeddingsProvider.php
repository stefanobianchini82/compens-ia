<?php

declare(strict_types=1);

namespace App\AI;

use NeuronAI\RAG\Embeddings\GeminiEmbeddingsProvider;

/**
 * Variante di GeminiEmbeddingsProvider allineata all'interfaccia di conteggio
 * token, così il codice chiamante (job di ingestione, chat) è identico a
 * prescindere dal provider scelto.
 *
 * NOTA: l'endpoint `embedContent` di Gemini NON restituisce il numero di token
 * consumati, quindi qui il conteggio resta a 0 (token embedding "n/d" per
 * Gemini). Il conteggio dei token di chat, invece, resta disponibile per
 * entrambi i provider tramite `response->getUsage()`.
 */
final class CountingGeminiEmbeddingsProvider extends GeminiEmbeddingsProvider implements TokenCountingEmbeddingsProvider
{
    public function getTotalTokens(): int
    {
        return 0;
    }
}
