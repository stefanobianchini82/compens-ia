<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Messaggio della chat.
 *
 * @property int $id
 * @property int $chat_session_id
 * @property string $role
 * @property string $content
 * @property int $input_tokens
 * @property int $output_tokens
 * @property int $embed_tokens
 * @property int $tts_chars
 */
class ChatMessage extends Model
{
    public const ROLE_USER = 'user';
    public const ROLE_ASSISTANT = 'assistant';

    protected $fillable = [
        'chat_session_id',
        'role',
        'content',
        'input_tokens',
        'output_tokens',
        'embed_tokens',
        'tts_chars',
    ];

    protected $casts = [
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
        'embed_tokens' => 'integer',
        'tts_chars' => 'integer',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class, 'chat_session_id');
    }

    /** Somma di tutti i token usati nell'intera applicazione. */
    public static function grandTotalTokens(): int
    {
        return (int) static::query()
            ->selectRaw('COALESCE(SUM(input_tokens + output_tokens + embed_tokens), 0) as total')
            ->value('total');
    }

    /**
     * Somma dei caratteri letti dal Text-to-Speech (motore OpenAI) nell'intera
     * applicazione. Contatore separato dai token dell'LLM.
     */
    public static function grandTotalTtsChars(): int
    {
        return (int) static::query()
            ->selectRaw('COALESCE(SUM(tts_chars), 0) as total')
            ->value('total');
    }
}
