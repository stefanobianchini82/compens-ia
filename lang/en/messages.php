<?php

declare(strict_types=1);

// User-facing server-side messages: flash, JSON responses, exceptions.
return [
    // Confirmation flash
    'subject_added' => 'Subject added.',
    'subject_deleted' => 'Subject deleted.',
    'book_uploaded' => 'Book uploaded: I\'m preparing it for study.',
    'book_deleted' => 'Book deleted.',
    'settings_saved' => 'Settings saved.',
    'chat_cleared' => 'Chat cleared.',

    // Chat / streaming
    'chat_error' => 'Oops! Something went wrong. Check your settings (provider and API key) and try again.',

    // Text-to-Speech
    'tts_unavailable' => 'OpenAI read aloud not available: using the device voice.',
    'tts_nothing' => 'Nothing to read.',
    'tts_failed' => 'I can\'t generate the audio right now.',
    'tts_no_audio' => 'Audio not available.',

    // Speech-to-Text
    'stt_unavailable' => 'Dictation not available: using the device microphone, if supported.',
    'stt_failed' => 'I can\'t understand the audio right now. Please try again.',

    // Mind maps
    'mindmap_unavailable' => 'Map not available: check the provider and API key in the settings.',
    'mindmap_no_text' => 'There is no text to turn into a map.',
    'mindmap_failed' => 'I can\'t create the map right now. Please try again shortly.',
    'mindmap_empty' => 'Map not available for this answer.',

    // Factory / job exceptions
    'provider_missing' => 'LLM provider or API key missing: configure the settings before using the assistant.',
    'mindmap_provider_missing' => 'Provider or API key not configured: cannot generate the map.',
    'tts_provider_missing' => 'OpenAI Text-to-Speech not available with the current settings.',
    'stt_provider_missing' => 'OpenAI Speech-to-Text not available with the current settings.',
    'pdf_no_text' => 'I couldn\'t read any text from the PDF. Make sure it\'s a PDF with text (not just scanned images) and that poppler (pdftotext) is installed or reachable (via the system PATH or the POPPLER_BIN_PATH variable).',
];
