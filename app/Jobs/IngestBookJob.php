<?php

declare(strict_types=1);

namespace App\Jobs;

use App\AI\PopplerBinary;
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
                throw new \RuntimeException(
                    'Non sono riuscito a leggere il testo dal PDF. '
                    .'Assicurati che sia un PDF con testo (non solo immagini scansionate) '
                    .'e che poppler (pdftotext) sia installato o raggiungibile '
                    .'(via PATH di sistema o variabile POPPLER_BIN_PATH).'
                );
            }

            $agent = $factory->forIngestion($book->subject);
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
