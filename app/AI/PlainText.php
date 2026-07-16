<?php

declare(strict_types=1);

namespace App\AI;

use function preg_replace;
use function trim;

/**
 * Ripulisce il Markdown prodotto dall'assistente in testo semplice, adatto a
 * essere letto ad alta voce dal Text-to-Speech: senza asterischi, cancelletti,
 * backtick o parentesi dei link, che altrimenti verrebbero pronunciati.
 */
final class PlainText
{
    public static function fromMarkdown(string $markdown): string
    {
        $text = $markdown;

        // Link [testo](url) e immagini ![alt](url) → solo il testo/alt.
        $text = preg_replace('/!?\[([^\]]*)\]\([^)]*\)/', '$1', $text);
        // Blocchi e span di codice: rimuovi i backtick, tieni il contenuto.
        $text = preg_replace('/`{1,3}/', '', $text);
        // Enfasi **grassetto**, *corsivo*, __ __, _ _ → togli i marcatori.
        $text = preg_replace('/(\*{1,3}|_{1,3})(.+?)\1/s', '$2', $text);
        // Titoli (#, ##, …) e citazioni (>) a inizio riga.
        $text = preg_replace('/^\s{0,3}#{1,6}\s*/m', '', (string) $text);
        $text = preg_replace('/^\s{0,3}>\s?/m', '', (string) $text);
        // Marcatori di elenco puntato a inizio riga (-, *, +).
        $text = preg_replace('/^\s*[-*+]\s+/m', '', (string) $text);
        // Righe orizzontali (---, ***).
        $text = preg_replace('/^\s*([-*_])\1{2,}\s*$/m', '', (string) $text);

        return trim((string) $text);
    }
}
