<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Materia scolastica. Lo slug identifica il file del vector store della materia.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $color
 */
class Subject extends Model
{
    protected $fillable = ['name', 'slug', 'color'];

    protected static function booted(): void
    {
        static::creating(static function (self $subject): void {
            if (blank($subject->slug)) {
                $subject->slug = static::uniqueSlug($subject->name);
            }
        });
    }

    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }

    private static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'materia';
        $slug = $base;
        $i = 2;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
