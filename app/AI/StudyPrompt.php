<?php

declare(strict_types=1);

namespace App\AI;

use NeuronAI\Agent\SystemPrompt;
use Stringable;

/**
 * System prompt dell'assistente allo studio, calibrato su ragazzi con DSA
 * (Disturbi Specifici dell'Apprendimento).
 *
 * Obiettivi: linguaggio semplice, tono paziente e incoraggiante, risposte
 * basate SOLO sui libri caricati (RAG), niente informazioni inventate.
 */
final class StudyPrompt implements Stringable
{
    public function __construct(
        /** Nome della materia selezionata, per contestualizzare la risposta. */
        private readonly ?string $subject = null,
    ) {
    }

    public function __toString(): string
    {
        // Riga di contesto sulla materia, sostituita nel segnaposto :subject del
        // blocco background. I testi seguono il locale corrente dell'app.
        $materia = $this->subject
            ? __('prompts.study.subject_known', ['subject' => $this->subject])
            : __('prompts.study.subject_unknown');

        return (string) new SystemPrompt(
            background: __('prompts.study.background', ['subject' => $materia]),
            steps: __('prompts.study.steps'),
            output: __('prompts.study.output'),
        );
    }
}
