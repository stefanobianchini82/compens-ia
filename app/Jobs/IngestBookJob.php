<?php

declare(strict_types=1);

namespace App\Jobs;

use App\AI\PopplerBinary;
use App\AI\SafeFileVectorStore;
use App\AI\StudyAgentFactory;
use App\Models\Book;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use NeuronAI\RAG\DataLoader\FileDataLoader;
use NeuronAI\RAG\DataLoader\PdfReader;
use NeuronAI\RAG\Splitter\SentenceTextSplitter;
use Throwable;

/**
 * Indicizzazione (RAG) di un libro PDF, eseguita in background:
 *   1. estrae il testo dal PDF (richiede `pdftotext` / poppler)
 *   2. lo spezza in chunk
 *   3. calcola gli embeddings e li salva nel vector store DELLA MATERIA
 *   4. aggiorna stato, numero di chunk e token consumati sul libro
 */
class IngestBookJob implements ShouldQueue
{
    use Queueable;

    /** Un libro grande può richiedere tempo: alziamo il timeout. */
    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(
        public readonly int $bookId,
    ) {
    }

    public function handle(StudyAgentFactory $factory): void
    {
        $book = Book::with('subject')->find($this->bookId);

        if ($book === null || $book->subject === null) {
            return;
        }

        $book->update(['status' => Book::STATUS_PROCESSING, 'error_message' => null]);

        try {
            $absolutePath = Storage::disk('local')->path($book->path);

            $documents = FileDataLoader::for($absolutePath)
                ->addReader('pdf', new PdfReader(PopplerBinary::path()))
                ->withSplitter(new SentenceTextSplitter(maxWords: 200, overlapWords: 20))
                ->getDocuments();

            if ($documents === []) {
                throw new \RuntimeException(__('messages.pdf_no_text'));
            }

            $agent = $factory->forIngestion($book->subject);

            // Ri-ingestione dello stesso libro: senza questa pulizia i vettori si
            // sommerebbero a quelli del giro precedente, e il retrieval restituirebbe
            // lo stesso passaggio più volte.
            (new SafeFileVectorStore(storage_path('app/vector'), name: $book->subject->slug))
                ->deleteBy('files', $absolutePath);

            $agent->addDocuments($documents);

            $book->update([
                'status' => Book::STATUS_READY,
                'chunk_count' => count($documents),
                'embed_tokens' => $agent->embeddingsProvider()->getTotalTokens(),
                'error_message' => null,
            ]);
        } catch (Throwable $e) {
            $book->update([
                'status' => Book::STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
