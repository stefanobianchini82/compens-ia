<?php

declare(strict_types=1);

// Schermata Impostazioni (form + statistiche di utilizzo).
return [
    'page_title' => 'Impostazioni',
    'heading' => '⚙️ Impostazioni',

    'needs_setup_title' => '⚠️ Configurazione necessaria',
    'needs_setup_body' => 'Senza il <strong>provider</strong> e la <strong>chiave API</strong> l\'assistente non può funzionare. Inserisci i dati qui sotto e salva per iniziare a studiare.',

    'language_label' => 'Lingua',
    'language_it' => 'Italiano',
    'language_en' => 'English',

    'provider_label' => 'Provider di intelligenza artificiale',
    'provider_openai' => 'OpenAI (ChatGPT)',
    'provider_gemini' => 'Google Gemini',
    'provider_note' => 'Nota: sono supportati OpenAI e Gemini perché servono anche a indicizzare i libri (embeddings).',

    'api_key_label' => 'Chiave API',
    'api_key_placeholder_set' => '•••••••• (già impostata, lascia vuoto per non cambiarla)',
    'api_key_placeholder_unset' => 'Incolla qui la tua chiave API',

    'tts_label' => '🔊 Lettura ad alta voce',
    'tts_browser' => 'Voce del dispositivo (gratis, sempre disponibile)',
    'tts_openai' => 'OpenAI (qualità superiore, richiede provider OpenAI)',
    'tts_note' => 'Aggiunge un pulsante ▶ per farti <strong>ascoltare</strong> le risposte. La voce del dispositivo non ha costi. La voce OpenAI è più naturale ma consuma caratteri (vedi contatore qui sotto) e funziona solo con provider OpenAI: con Gemini si usa comunque la voce del dispositivo.',
    'tts_karaoke_note' => '✏️ L\'evidenziazione parola per parola durante la lettura (effetto «karaoke») è disponibile <strong>solo con la voce del dispositivo</strong>: con la voce OpenAI si ascolta l\'audio senza evidenziazione.',

    'models_summary' => 'Modelli (facoltativo)',
    'chat_model_label' => 'Modello chat',
    'embed_model_label' => 'Modello embeddings',
    'model_placeholder_default' => 'Predefinito in base al provider',
    'tts_voice_label' => 'Voce OpenAI (TTS)',
    'tts_voice_placeholder' => 'Predefinita: nova (es. alloy, echo, fable, onyx, shimmer)',
    'tts_model_label' => 'Modello TTS OpenAI',
    'tts_model_placeholder' => 'Predefinito: gpt-4o-mini-tts (es. tts-1, tts-1-hd)',

    'save' => 'Salva impostazioni',

    'usage_title' => '📊 Utilizzo dei token',
    'tokens_total' => 'Token totali',
    'tokens_session' => 'Token sessione corrente',

    'tts_usage_title' => '🔊 Lettura ad alta voce (caratteri)',
    'tts_chars_total' => 'Caratteri letti totali',
    'tts_chars_session' => 'Caratteri letti sessione corrente',
    'tts_usage_note' => 'Conteggio dei caratteri sintetizzati con la voce OpenAI (unità di fatturazione del TTS). La voce del dispositivo non ha costi e non incrementa questi valori.',

    'stt_usage_title' => '🎤 Dettatura vocale (token)',
    'stt_tokens_total' => 'Token dettatura totali',
    'stt_tokens_session' => 'Token dettatura sessione corrente',
    'stt_usage_note' => 'Conteggio dei token della trascrizione OpenAI (unità di fatturazione della dettatura). Il microfono del dispositivo non ha costi e non incrementa questi valori.',
];
