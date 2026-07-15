<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Percorso del binario poppler (pdftotext)
    |--------------------------------------------------------------------------
    |
    | L'estrazione del testo dai PDF (NeuronAI PdfReader) lancia il binario di
    | sistema `pdftotext`. In sviluppo lo si installa via `brew install poppler`
    | e viene trovato automaticamente sul PATH.
    |
    | Per il delivery con NativePHP (app desktop distribuita a utenti che non
    | hanno poppler installato) il binario va bundlato dentro l'app: qui si può
    | indicare il suo percorso esplicito. Se lasciato a null, PopplerBinary prova
    | la cartella convenzionale `resources/bin/{OS}/` e, in ultima istanza, ricade
    | sull'auto-discovery sul PATH di sistema.
    |
    */

    'poppler_bin_path' => env('POPPLER_BIN_PATH'),

];
