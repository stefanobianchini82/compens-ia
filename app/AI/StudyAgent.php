<?php

declare(strict_types=1);

namespace App\AI;

use NeuronAI\Chat\History\ChatHistoryInterface;
use NeuronAI\Chat\History\FileChatHistory;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Gemini\Gemini;
use NeuronAI\Providers\OpenAI\OpenAI;
use NeuronAI\RAG\Embeddings\EmbeddingsProviderInterface;
use NeuronAI\RAG\RAG;
use NeuronAI\RAG\VectorStore\VectorStoreInterface;

/**
 * Agente dell'assistente allo studio DSA.
 *
 * Estende RAG di NeuronAI e combina in un unico posto:
 *   - provider()     -> quale LLM usare (OpenAI o Gemini, da impostazioni utente)
 *   - embeddings()   -> come vettorializzare il testo per il RAG (con conteggio token)
 *   - vectorStore()  -> dove cercare i vettori (un file per MATERIA)
 *   - chatHistory()  -> memoria della conversazione (su file, persistente per sessione)
 *   - instructions() -> il system prompt specifico per ragazzi con DSA
 *
 * A differenza della demo, la configurazione NON è hardcoded su $_ENV: viene
 * iniettata via {@see AgentConfig} dallo {@see StudyAgentFactory}, così lo
 * stesso agente serve materie e provider diversi.
 */
class StudyAgent extends RAG
{
    public function __construct(
        private readonly AgentConfig $config,
    ) {
        parent::__construct();
    }

    protected function provider(): AIProviderInterface
    {
        return match ($this->config->provider) {
            'gemini' => new Gemini(
                key: $this->config->apiKey,
                model: $this->config->chatModel,
            ),
            default => new OpenAI(
                key: $this->config->apiKey,
                model: $this->config->chatModel,
            ),
        };
    }

    protected function instructions(): string
    {
        return (string) new StudyPrompt($this->config->subjectName);
    }

    protected function embeddings(): EmbeddingsProviderInterface
    {
        return match ($this->config->provider) {
            'gemini' => new CountingGeminiEmbeddingsProvider(
                key: $this->config->apiKey,
                model: $this->config->embedModel,
            ),
            default => new CountingOpenAIEmbeddingsProvider(
                key: $this->config->apiKey,
                model: $this->config->embedModel,
            ),
        };
    }

    /**
     * Vector store LOCALE, con un file separato PER MATERIA: la ricerca RAG in
     * chat resta confinata ai libri della materia selezionata.
     */
    protected function vectorStore(): VectorStoreInterface
    {
        return new SafeFileVectorStore(
            directory: $this->config->vectorDirectory,
            name: $this->config->subjectSlug,
        );
    }

    protected function chatHistory(): ChatHistoryInterface
    {
        return new FileChatHistory(
            directory: $this->config->historyDirectory,
            key: $this->config->historyKey,
            contextWindow: 50000,
        );
    }

    /**
     * Espone il provider di embeddings già risolto, così i controller/job
     * possono leggere i token consumati dopo un'operazione.
     */
    public function embeddingsProvider(): TokenCountingEmbeddingsProvider
    {
        /** @var TokenCountingEmbeddingsProvider $provider */
        $provider = $this->resolveEmbeddingsProvider();

        return $provider;
    }
}
