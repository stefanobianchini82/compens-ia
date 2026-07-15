<?php

declare(strict_types=1);

namespace App\AI;

/**
 * Risolve il percorso del binario `pdftotext` (poppler) da passare al PdfReader
 * di NeuronAI.
 *
 * Strategia (con guardia sull'eseguibilità, perché {@see \NeuronAI\RAG\DataLoader\PdfReader::setBinPath()}
 * lancia eccezione su un path non eseguibile):
 *
 *   1. percorso esplicito da config (`POPPLER_BIN_PATH`), se eseguibile;
 *   2. binario bundlato in `resources/bin/{OS}/pdftotext`, se eseguibile
 *      (convenzione per il packaging NativePHP);
 *   3. null → PdfReader ricade sull'auto-discovery sul PATH di sistema
 *      (comportamento in sviluppo con poppler installato via brew).
 */
class PopplerBinary
{
    /**
     * Percorso eseguibile di `pdftotext`, oppure null per delegare l'auto-discovery.
     */
    public static function path(): ?string
    {
        $configured = config('ingestion.poppler_bin_path');

        if (is_string($configured) && $configured !== '' && is_executable($configured)) {
            return $configured;
        }

        $bundled = self::bundledPath();

        if ($bundled !== null && is_executable($bundled)) {
            return $bundled;
        }

        return null;
    }

    /**
     * Percorso convenzionale del binario bundlato per la piattaforma corrente.
     */
    private static function bundledPath(): ?string
    {
        $binary = PHP_OS_FAMILY === 'Windows' ? 'pdftotext.exe' : 'pdftotext';

        return base_path('resources/bin/'.PHP_OS_FAMILY.'/'.$binary);
    }
}
