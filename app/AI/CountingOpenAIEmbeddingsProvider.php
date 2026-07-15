<?php

declare(strict_types=1);

namespace App\AI;

use NeuronAI\HttpClient\HttpRequest;
use NeuronAI\RAG\Document;
use NeuronAI\RAG\Embeddings\OpenAIEmbeddingsProvider;

use function array_chunk;
use function array_map;
use function array_merge;

/**
 * Variante di OpenAIEmbeddingsProvider che NON scarta il campo `usage`
 * restituito dall'API embeddings di OpenAI, ma ne accumula i token.
 *
 * Il provider originale legge solo `response['data']` (i vettori) e ignora
 * `response['usage']`, quindi il costo in token della vettorializzazione resta
 * invisibile. Qui rifacciamo la stessa richiesta HTTP del genitore e in più
 * sommiamo `usage.total_tokens`.
 *
 * NOTA: il corpo dei due metodi è volutamente identico al genitore, cambia solo
 * la riga che legge `usage`. Se l'upstream cambia la forma della richiesta,
 * questa sottoclasse va riallineata a mano.
 */
final class CountingOpenAIEmbeddingsProvider extends OpenAIEmbeddingsProvider implements TokenCountingEmbeddingsProvider
{
    /** Token embedding accumulati dall'avvio del processo (query + ingestione). */
    private int $totalTokens = 0;

    public function getTotalTokens(): int
    {
        return $this->totalTokens;
    }

    /**
     * @throws \NeuronAI\Exceptions\HttpException
     */
    public function embedText(string $text): array
    {
        $response = $this->httpClient->request(HttpRequest::post('embeddings', [
            'model' => $this->model,
            'input' => $text,
            'encoding_format' => 'float',
            ...($this->dimensions ? ['dimensions' => $this->dimensions] : []),
        ]))->json();

        $this->totalTokens += $response['usage']['total_tokens'] ?? 0;

        return $response['data'][0]['embedding'];
    }

    /**
     * @param array<int, Document> $documents
     * @return array<int, Document>
     * @throws \NeuronAI\Exceptions\HttpException
     */
    public function embedDocuments(array $documents): array
    {
        $chunks = array_chunk($documents, 100);

        foreach ($chunks as $chunk) {
            $response = $this->httpClient->request(HttpRequest::post('embeddings', [
                'model' => $this->model,
                'input' => array_map(fn (Document $document): string => $document->getContent(), $chunk),
                'encoding_format' => 'float',
                ...($this->dimensions ? ['dimensions' => $this->dimensions] : []),
            ]))->json();

            $this->totalTokens += $response['usage']['total_tokens'] ?? 0;

            foreach ($response['data'] as $index => $item) {
                $chunk[$index]->embedding = $item['embedding'];
            }
        }

        return array_merge(...$chunks);
    }
}
