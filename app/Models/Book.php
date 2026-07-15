<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Libro scolastico caricato in PDF.
 *
 * @property int $id
 * @property int $subject_id
 * @property string $title
 * @property string $original_filename
 * @property string $path
 * @property string $status
 * @property int $chunk_count
 * @property int $embed_tokens
 * @property string|null $error_message
 */
class Book extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_READY = 'ready';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'subject_id',
        'title',
        'original_filename',
        'path',
        'status',
        'chunk_count',
        'embed_tokens',
        'error_message',
    ];

    protected $casts = [
        'chunk_count' => 'integer',
        'embed_tokens' => 'integer',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /** Etichetta leggibile dello stato, per la UI. */
    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'In coda',
            self::STATUS_PROCESSING => 'In lavorazione',
            self::STATUS_READY => 'Pronto',
            self::STATUS_FAILED => 'Errore',
            default => $this->status,
        };
    }
}
