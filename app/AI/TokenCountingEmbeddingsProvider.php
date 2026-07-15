<?php

declare(strict_types=1);

namespace App\AI;

use NeuronAI\RAG\Embeddings\EmbeddingsProviderInterface;

/**
 * Provider di embeddings che, oltre a calcolare i vettori, tiene il conto dei
 * token consumati dall'avvio del processo. Serve a mostrare all'utente il costo
 * in token della vettorializzazione (ingestione dei libri e query in chat).
 */
interface TokenCountingEmbeddingsProvider extends EmbeddingsProviderInterface
{
    /** Token embedding accumulati da quando il provider è stato creato. */
    public function getTotalTokens(): int;
}
