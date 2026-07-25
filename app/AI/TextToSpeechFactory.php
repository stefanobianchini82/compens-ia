<?php

declare(strict_types=1);

namespace App\AI;

use App\Models\Setting;
use NeuronAI\Providers\OpenAI\Audio\OpenAITextToSpeech;
use RuntimeException;

/**
 * Costruisce il provider Text-to-Speech server-side (OpenAI), leggendo le
 * impostazioni salvate dall'utente.
 *
 * NeuronAI offre il TTS nativo solo per OpenAI: con provider Gemini (o quando
 * l'utente sceglie la voce del dispositivo) il TTS server-side non è disponibile
 * e la lettura avviene lato browser (Web Speech API), senza passare da qui.
 */
class TextToSpeechFactory
{
    private const DEFAULT_MODEL = 'gpt-4o-mini-tts';
    private const DEFAULT_VOICE = 'nova';

    /**
     * Vero se il TTS server-side OpenAI è utilizzabile: motore impostato su
     * "openai", provider OpenAI e API key presente.
     */
    public function available(): bool
    {
        return Setting::get('tts_engine', 'browser') === 'openai'
            && Setting::get('provider') === 'openai'
            && filled(Setting::get('api_key'));
    }

    /**
     * Istanzia il provider TTS di OpenAI configurato.
     *
     * @throws RuntimeException se il TTS server-side non è disponibile.
     */
    public function make(): OpenAITextToSpeech
    {
        if (! $this->available()) {
            throw new RuntimeException(__('messages.tts_provider_missing'));
        }

        return new OpenAITextToSpeech(
            key: (string) Setting::get('api_key'),
            model: Setting::get('tts_model') ?: self::DEFAULT_MODEL,
            voice: Setting::get('tts_voice') ?: self::DEFAULT_VOICE,
        );
    }
}
