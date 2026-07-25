<?php

declare(strict_types=1);

namespace App\AI;

use App\Models\Setting;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Gemini\Gemini;
use NeuronAI\Providers\OpenAI\OpenAI;
use RuntimeException;

/**
 * Costruisce il provider LLM usato per generare le mappe concettuali (Mermaid)
 * a partire da una risposta già prodotta dall'assistente.
 *
 * A differenza dello {@see StudyAgent}, qui NON serve il RAG: è una singola
 * trasformazione testo → mappa. A differenza del Text-to-Speech, funziona con
 * entrambi i provider (OpenAI e Gemini), perché usa la normale API di chat.
 */
class MindMapFactory
{
    /** Modelli di chat predefiniti (allineati a {@see StudyAgentFactory}). */
    private const DEFAULT_CHAT_MODEL = [
        'openai' => 'gpt-4o-mini',
        'gemini' => 'gemini-1.5-flash',
    ];

    /**
     * Vero se è possibile generare mappe: serve un provider valido e la API key.
     */
    public function available(): bool
    {
        return in_array(Setting::get('provider'), ['openai', 'gemini'], true)
            && filled(Setting::get('api_key'));
    }

    /**
     * Istanzia il provider LLM configurato dall'utente.
     *
     * @throws RuntimeException se provider/API key non sono configurati.
     */
    public function make(): AIProviderInterface
    {
        if (! $this->available()) {
            throw new RuntimeException(__('messages.mindmap_provider_missing'));
        }

        $provider = (string) Setting::get('provider');
        $key = (string) Setting::get('api_key');
        $model = Setting::get('chat_model') ?: self::DEFAULT_CHAT_MODEL[$provider];

        return match ($provider) {
            'gemini' => new Gemini(key: $key, model: $model),
            default => new OpenAI(key: $key, model: $model),
        };
    }
}
