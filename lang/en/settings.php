<?php

declare(strict_types=1);

// Settings screen (form + usage statistics).
return [
    'page_title' => 'Settings',
    'heading' => '⚙️ Settings',

    'needs_setup_title' => '⚠️ Setup required',
    'needs_setup_body' => 'Without the <strong>provider</strong> and the <strong>API key</strong> the assistant cannot work. Enter the details below and save to start studying.',

    'language_label' => 'Language',
    'language_it' => 'Italiano',
    'language_en' => 'English',

    'provider_label' => 'Artificial intelligence provider',
    'provider_openai' => 'OpenAI (ChatGPT)',
    'provider_gemini' => 'Google Gemini',
    'provider_note' => 'Note: OpenAI and Gemini are supported because they are also needed to index the books (embeddings).',

    'api_key_label' => 'API key',
    'api_key_placeholder_set' => '•••••••• (already set, leave empty to keep it)',
    'api_key_placeholder_unset' => 'Paste your API key here',

    'tts_label' => '🔊 Read aloud',
    'tts_browser' => 'Device voice (free, always available)',
    'tts_openai' => 'OpenAI (higher quality, requires OpenAI provider)',
    'tts_note' => 'Adds a ▶ button so you can <strong>listen</strong> to the answers. The device voice is free. The OpenAI voice sounds more natural but consumes characters (see the counter below) and only works with the OpenAI provider: with Gemini the device voice is used instead.',

    'models_summary' => 'Models (optional)',
    'chat_model_label' => 'Chat model',
    'embed_model_label' => 'Embeddings model',
    'model_placeholder_default' => 'Default based on the provider',
    'tts_voice_label' => 'OpenAI voice (TTS)',
    'tts_voice_placeholder' => 'Default: nova (e.g. alloy, echo, fable, onyx, shimmer)',
    'tts_model_label' => 'OpenAI TTS model',
    'tts_model_placeholder' => 'Default: gpt-4o-mini-tts (e.g. tts-1, tts-1-hd)',

    'save' => 'Save settings',

    'usage_title' => '📊 Token usage',
    'tokens_total' => 'Total tokens',
    'tokens_session' => 'Current session tokens',

    'tts_usage_title' => '🔊 Read aloud (characters)',
    'tts_chars_total' => 'Total characters read',
    'tts_chars_session' => 'Characters read this session',
    'tts_usage_note' => 'Count of characters synthesized with the OpenAI voice (the TTS billing unit). The device voice is free and does not increase these values.',

    'stt_usage_title' => '🎤 Voice dictation (tokens)',
    'stt_tokens_total' => 'Total dictation tokens',
    'stt_tokens_session' => 'Dictation tokens this session',
    'stt_usage_note' => 'Count of tokens from the OpenAI transcription (the dictation billing unit). The device microphone is free and does not increase these values.',
];
