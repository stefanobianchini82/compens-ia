<?php

declare(strict_types=1);

namespace App\AI;

/**
 * Configurazione necessaria a costruire uno {@see StudyAgent}: viene assemblata
 * dallo {@see StudyAgentFactory} leggendo le impostazioni salvate dall'utente e
 * la materia selezionata.
 */
final readonly class AgentConfig
{
    public function __construct(
        /** Provider LLM: 'openai' oppure 'gemini'. */
        public string $provider,
        public string $apiKey,
        public string $chatModel,
        public string $embedModel,
        /** Slug della materia: usato come nome del file del vector store. */
        public string $subjectSlug,
        /** Nome leggibile della materia: usato nel system prompt. */
        public ?string $subjectName,
        /** Chiave della cronologia conversazione (es. "session-12"). */
        public string $historyKey,
        public string $vectorDirectory,
        public string $historyDirectory,
    ) {
    }
}
