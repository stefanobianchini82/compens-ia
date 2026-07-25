<?php

declare(strict_types=1);

namespace App\AI;

use NeuronAI\RAG\VectorStore\FileVectorStore;
use NeuronAI\RAG\VectorStore\VectorStoreInterface;

use function is_file;

/**
 * FileVectorStore "tollerante": se l'indice dei vettori di una materia non è
 * ancora stato creato (nessun libro indicizzato), la ricerca restituisce
 * semplicemente nessun documento invece di andare in errore aprendo un file
 * inesistente. Stesso discorso per la cancellazione.
 *
 * Così la chat funziona anche prima di caricare i libri: l'agente risponde
 * dicendo che non trova nulla nel materiale, senza rompersi.
 */
class SafeFileVectorStore extends FileVectorStore
{
    public function similaritySearch(array $embedding): array
    {
        // getFilePath() è protected nel parent: lo riusiamo per controllare
        // l'esistenza dell'indice prima di leggerlo.
        if (!is_file($this->getFilePath())) {
            return [];
        }

        return parent::similaritySearch($embedding);
    }

    /**
     * Rimuove dall'indice i documenti di una certa fonte (es. i chunk di un
     * libro eliminato). Se l'indice non esiste ancora non c'è nulla da fare:
     * il parent, invece, proverebbe comunque a leggerlo e a sostituirlo.
     */
    public function deleteBy(string $sourceType, ?string $sourceName = null): VectorStoreInterface
    {
        if (!is_file($this->getFilePath())) {
            return $this;
        }

        return parent::deleteBy($sourceType, $sourceName);
    }
}
