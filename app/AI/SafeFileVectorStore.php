<?php

declare(strict_types=1);

namespace App\AI;

use NeuronAI\RAG\VectorStore\FileVectorStore;

use function is_file;

/**
 * FileVectorStore "tollerante": se l'indice dei vettori di una materia non è
 * ancora stato creato (nessun libro indicizzato), la ricerca restituisce
 * semplicemente nessun documento invece di andare in errore aprendo un file
 * inesistente.
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
}
