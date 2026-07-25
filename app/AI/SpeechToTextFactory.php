<?php

declare(strict_types=1);

namespace App\AI;

use App\Models\Setting;
use NeuronAI\Providers\OpenAI\Audio\OpenAISpeechToText;
use RuntimeException;

/**
 * Costruisce il provider Speech-to-Text server-side (OpenAI), leggendo le
 * impostazioni salvate dall'utente.
 *
 * Serve all'input vocale: il ragazzo detta la domanda invece di scriverla (utile
 * in caso di disgrafia). NeuronAI offre la trascrizione nativa solo per OpenAI:
 * con provider Gemini l'STT server-side non è disponibile (il client può
 * ripiegare, se supportato, sul riconoscimento vocale del browser).
 */
class SpeechToTextFactory
{
    // Nota: whisper-1 con response_format=json NON restituisce l'oggetto `usage`,
    // e NeuronAI (OpenAISpeechToText) lo dà per scontato → "Undefined array key
    // input_tokens". Usiamo un modello «transcribe» che include sempre `usage`.
    private const DEFAULT_MODEL = 'gpt-4o-mini-transcribe';

    /**
     * Vero se l'STT server-side OpenAI è utilizzabile: provider OpenAI e API key.
     */
    public function available(): bool
    {
        return Setting::get('provider') === 'openai'
            && filled(Setting::get('api_key'));
    }

    /**
     * Istanzia il provider Speech-to-Text di OpenAI, nella lingua dell'interfaccia.
     *
     * @throws RuntimeException se l'STT server-side non è disponibile.
     */
    public function make(): OpenAISpeechToText
    {
        if (! $this->available()) {
            throw new RuntimeException(__('messages.stt_provider_missing'));
        }

        return new OpenAISpeechToText(
            key: (string) Setting::get('api_key'),
            model: Setting::get('stt_model') ?: self::DEFAULT_MODEL,
            // Lingua di trascrizione allineata a quella scelta dall'utente.
            language: app()->getLocale(),
        );
    }
}
