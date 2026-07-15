<?php

declare(strict_types=1);

namespace App\AI;

use App\Models\ChatSession;
use App\Models\Setting;
use App\Models\Subject;
use RuntimeException;

/**
 * Costruisce uno {@see StudyAgent} già configurato, leggendo le impostazioni
 * salvate dall'utente (provider + API key) e adattandolo alla materia e alla
 * sessione di chat correnti.
 */
class StudyAgentFactory
{
    /** Modelli di default per provider (chat + embeddings). */
    private const DEFAULTS = [
        'openai' => ['chat' => 'gpt-4o-mini', 'embed' => 'text-embedding-3-small'],
        'gemini' => ['chat' => 'gemini-1.5-flash', 'embed' => 'text-embedding-004'],
    ];

    /**
     * Agente per la CHAT su una materia, con memoria legata alla sessione.
     */
    public function forChat(Subject $subject, ChatSession $session): StudyAgent
    {
        return new StudyAgent($this->config(
            subject: $subject,
            historyKey: 'session-'.$session->getKey(),
        ));
    }

    /**
     * Agente per l'INGESTIONE dei libri di una materia (nessuna cronologia utile:
     * usiamo una chiave dedicata così non sporchiamo le conversazioni).
     */
    public function forIngestion(Subject $subject): StudyAgent
    {
        return new StudyAgent($this->config(
            subject: $subject,
            historyKey: 'ingestion-'.$subject->slug,
        ));
    }

    private function config(Subject $subject, string $historyKey): AgentConfig
    {
        $provider = Setting::get('provider');
        $apiKey = Setting::get('api_key');

        if (blank($provider) || blank($apiKey)) {
            throw new RuntimeException(
                'Provider LLM o API key mancanti: configura le impostazioni prima di usare l\'assistente.'
            );
        }

        $provider = in_array($provider, ['openai', 'gemini'], true) ? $provider : 'openai';
        $defaults = self::DEFAULTS[$provider];

        return new AgentConfig(
            provider: $provider,
            apiKey: $apiKey,
            chatModel: Setting::get('chat_model') ?: $defaults['chat'],
            embedModel: Setting::get('embed_model') ?: $defaults['embed'],
            subjectSlug: $subject->slug,
            subjectName: $subject->name,
            historyKey: $historyKey,
            vectorDirectory: storage_path('app/vector'),
            historyDirectory: storage_path('app/history'),
        );
    }
}
