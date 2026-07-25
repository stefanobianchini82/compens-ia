<?php

declare(strict_types=1);

// Strings consumed by the JavaScript files. Injected into `window.i18n` by the
// layout via @js(__('js')). Also include the language→voice map for STT/TTS.
return [
    // Language for the browser speech recognition/synthesis.
    'speech_lang' => 'en-US',

    // chat.js
    'choose_subject_first' => 'Choose a subject first 📚',
    'thinking' => 'Thinking…',
    'invalid_response' => 'Invalid response from the server.',
    'generic_error' => 'Something went wrong.',
    'cannot_answer' => 'I can\'t answer right now. Please try again shortly.',
    'welcome' => '<p>Hi! 👋 Choose a subject and write me your question.<br>I\'ll help you understand, one step at a time.</p>',
    'change_subject_title' => 'Change subject?',
    'change_subject_body' => 'Changing subject will clear the current chat.',
    'change_subject_confirm' => 'Change and clear',

    // reset-modal.js (clear-chat confirmation modal)
    'confirm' => 'Confirm',
    'clear_title' => 'Clear the chat?',
    'clear_body' => 'The messages will be permanently deleted.',
    'clear_confirm' => 'Clear',

    // mindmap.js
    'mindmap_idle' => '🗺️ Map',
    'mindmap_loading' => '⏳ Creating the map…',
    'mindmap_hide' => '🙈 Hide map',
    'mindmap_show' => '🗺️ Show map',
    'mindmap_error' => 'I can\'t create the map right now.',
    'mindmap_download' => '🖼️ Download image',
    'mindmap_download_aria' => 'Download the map as a PNG image',
    'mindmap_download_error' => 'I can\'t save the image right now.',
    'mindmap_print' => '🖨️ Print',
    'mindmap_print_aria' => 'Print the concept map',
    'mindmap_print_error' => 'I can\'t print the map right now.',
    'mindmap_aria' => 'Concept map of the answer',
    'mindmap_print_title' => 'Concept map',
    'mindmap_download_filename' => 'concept-map.png',

    // stt.js
    'stt_stop_aria' => 'Stop dictation',
    'stt_start_aria' => 'Dictate your question',
    'stt_mic_error' => 'I can\'t use the microphone.',
    'stt_mic_access_error' => 'I can\'t access the microphone.',
    'stt_not_understood' => 'I didn\'t catch that. Please try again.',

    // tts.js
    'tts_listen' => '🔊 Listen',
    'tts_preparing' => '⏳ Preparing…',
    'tts_pause' => '⏸️ Pause',
    'tts_resume' => '▶️ Resume',
    'tts_pause_aria' => 'Pause the reading',
    'tts_listen_aria' => 'Listen to the answer',

    // reading-prefs.js («Aa» panel)
    'reading_prefs' => 'Reading preferences',
    'reading_heading' => '🔤 How do you read best?',
    'reading_font' => 'Font',
    'reading_font_default' => 'Default',
    'reading_size' => 'Text size',
    'reading_line' => 'Line spacing',
    'reading_letter' => 'Letter spacing',
    'reading_ruler' => 'Reading ruler',
    'reading_reset' => 'Reset',
];
