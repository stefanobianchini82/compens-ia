<?php

declare(strict_types=1);

// Messaggi user-facing lato server: flash, risposte JSON, eccezioni.
return [
    // Flash di conferma
    'subject_added' => 'Materia aggiunta.',
    'subject_deleted' => 'Materia eliminata.',
    'book_uploaded' => 'Libro caricato: lo sto preparando per lo studio.',
    'book_deleted' => 'Libro eliminato.',
    'settings_saved' => 'Impostazioni salvate.',
    'chat_cleared' => 'Chat svuotata.',

    // Chat / streaming
    'chat_error' => 'Ops! Qualcosa non ha funzionato. Controlla le impostazioni (provider e API key) e riprova.',

    // Text-to-Speech
    'tts_unavailable' => 'Lettura OpenAI non disponibile: uso la voce del dispositivo.',
    'tts_nothing' => 'Niente da leggere.',
    'tts_failed' => 'Non riesco a generare l\'audio in questo momento.',
    'tts_no_audio' => 'Audio non disponibile.',

    // Speech-to-Text
    'stt_unavailable' => 'Dettatura non disponibile: uso il microfono del dispositivo, se supportato.',
    'stt_failed' => 'Non riesco a capire l\'audio in questo momento. Riprova.',

    // Mappe concettuali
    'mindmap_unavailable' => 'Mappa non disponibile: controlla provider e API key nelle impostazioni.',
    'mindmap_no_text' => 'Non c\'è testo da trasformare in mappa.',
    'mindmap_failed' => 'Non riesco a creare la mappa in questo momento. Riprova tra poco.',
    'mindmap_empty' => 'Mappa non disponibile per questa risposta.',

    // Eccezioni delle factory / job
    'provider_missing' => 'Provider LLM o API key mancanti: configura le impostazioni prima di usare l\'assistente.',
    'mindmap_provider_missing' => 'Provider o API key non configurati: impossibile generare la mappa.',
    'tts_provider_missing' => 'Text-to-Speech OpenAI non disponibile con le impostazioni correnti.',
    'stt_provider_missing' => 'Speech-to-Text OpenAI non disponibile con le impostazioni correnti.',
    'pdf_no_text' => 'Non sono riuscito a leggere il testo dal PDF. Assicurati che sia un PDF con testo (non solo immagini scansionate) e che poppler (pdftotext) sia installato o raggiungibile (via PATH di sistema o variabile POPPLER_BIN_PATH).',
];
