<?php

declare(strict_types=1);

// Stringhe consumate dai file JavaScript. Iniettate in `window.i18n` dal layout
// tramite @js(__('js')). Includono anche la mappa lingua→voce per STT/TTS.
return [
    // Lingua per il riconoscimento/sintesi vocale del browser.
    'speech_lang' => 'it-IT',

    // chat.js
    'choose_subject_first' => 'Scegli prima una materia 📚',
    'thinking' => 'Sto pensando…',
    'invalid_response' => 'Risposta non valida dal server.',
    'generic_error' => 'Si è verificato un errore.',
    'cannot_answer' => 'Non riesco a rispondere in questo momento. Riprova tra poco.',
    'welcome' => '<p>Ciao! 👋 Scegli una materia e scrivimi la tua domanda.<br>Ti aiuto a capire, un passo alla volta.</p>',
    'change_subject_title' => 'Cambiare materia?',
    'change_subject_body' => 'Cambiando materia la chat attuale verrà svuotata.',
    'change_subject_confirm' => 'Cambia e svuota',

    // reset-modal.js (modale di conferma svuotamento chat)
    'confirm' => 'Conferma',
    'clear_title' => 'Vuoi svuotare la chat?',
    'clear_body' => 'I messaggi verranno eliminati definitivamente.',
    'clear_confirm' => 'Svuota',

    // mindmap.js
    'mindmap_idle' => '🗺️ Mappa',
    'mindmap_loading' => '⏳ Creo la mappa…',
    'mindmap_hide' => '🙈 Nascondi mappa',
    'mindmap_show' => '🗺️ Mostra mappa',
    'mindmap_error' => 'Non riesco a creare la mappa in questo momento.',
    'mindmap_download' => '🖼️ Scarica immagine',
    'mindmap_download_aria' => 'Scarica la mappa come immagine PNG',
    'mindmap_download_error' => 'Non riesco a salvare l\'immagine in questo momento.',
    'mindmap_print' => '🖨️ Stampa',
    'mindmap_print_aria' => 'Stampa la mappa concettuale',
    'mindmap_print_error' => 'Non riesco a stampare la mappa in questo momento.',
    'mindmap_aria' => 'Mappa concettuale della risposta',
    'mindmap_print_title' => 'Mappa concettuale',
    'mindmap_download_filename' => 'mappa-concettuale.png',

    // stt.js
    'stt_stop_aria' => 'Ferma la dettatura',
    'stt_start_aria' => 'Detta la domanda a voce',
    'stt_mic_error' => 'Non riesco a usare il microfono.',
    'stt_mic_access_error' => 'Non riesco ad accedere al microfono.',
    'stt_not_understood' => 'Non ho capito. Riprova.',

    // tts.js
    'tts_listen' => '🔊 Ascolta',
    'tts_preparing' => '⏳ Preparo…',
    'tts_pause' => '⏸️ Pausa',
    'tts_resume' => '▶️ Riprendi',
    'tts_pause_aria' => 'Metti in pausa la lettura',
    'tts_listen_aria' => 'Ascolta la risposta',

    // reading-prefs.js (pannello «Aa»)
    'reading_prefs' => 'Preferenze di lettura',
    'reading_heading' => '🔤 Come leggi meglio?',
    'reading_font' => 'Carattere',
    'reading_font_default' => 'Predefinito',
    'reading_size' => 'Dimensione testo',
    'reading_line' => 'Spazio tra le righe',
    'reading_letter' => 'Spazio tra le lettere',
    'reading_ruler' => 'Righello di lettura',
    'reading_reset' => 'Ripristina',
];
