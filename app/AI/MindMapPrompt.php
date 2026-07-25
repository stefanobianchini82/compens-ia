<?php

declare(strict_types=1);

namespace App\AI;

use NeuronAI\Agent\SystemPrompt;
use Stringable;

/**
 * System prompt per trasformare una risposta dell'assistente in una mappa
 * concettuale, in sintassi Mermaid «mindmap».
 *
 * Pensata per lo studio DSA: la mappa concettuale è uno strumento compensativo
 * per eccellenza. Deve restare fedele al testo fornito (che deriva già dal RAG
 * sui libri), con etichette brevi e poche diramazioni, senza inventare nulla.
 */
final class MindMapPrompt implements Stringable
{
    public function __toString(): string
    {
        // I testi del prompt seguono il locale corrente dell'app.
        return (string) new SystemPrompt(
            background: __('prompts.mindmap.background'),
            steps: __('prompts.mindmap.steps'),
            output: __('prompts.mindmap.output'),
        );
    }
}
