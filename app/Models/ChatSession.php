<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sessione di chat. La chiave "session-{id}" è usata come chiave della
 * cronologia conversazione di NeuronAI.
 *
 * @property int $id
 * @property int|null $subject_id
 * @property string|null $title
 */
class ChatSession extends Model
{
    protected $fillable = ['subject_id', 'title'];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    /** Somma dei token (input + output + embed) usati in questa sessione. */
    public function totalTokens(): int
    {
        return (int) $this->messages()
            ->selectRaw('COALESCE(SUM(input_tokens + output_tokens + embed_tokens), 0) as total')
            ->value('total');
    }

    /**
     * Somma dei caratteri letti dal Text-to-Speech (motore OpenAI) in questa
     * sessione. Contatore separato dai token dell'LLM.
     */
    public function totalTtsChars(): int
    {
        return (int) $this->messages()
            ->selectRaw('COALESCE(SUM(tts_chars), 0) as total')
            ->value('total');
    }
}
