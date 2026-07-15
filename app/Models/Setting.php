<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Impostazione chiave/valore. Il valore della chiave `api_key` viene cifrato a
 * riposo tramite il cast `encrypted`.
 *
 * @property string $key
 * @property string|null $value
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /** Chiavi il cui valore va cifrato nel database. */
    private const ENCRYPTED_KEYS = ['api_key'];

    private const CACHE_KEY = 'settings.all';

    /**
     * Legge il valore di un'impostazione (con cache in memoria/persistente).
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        return static::values()[$key] ?? $default;
    }

    /**
     * @return array<string, string|null>
     */
    public static function values(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, static function (): array {
            return static::query()->get()->mapWithKeys(static function (self $setting): array {
                return [$setting->key => $setting->decodeValue()];
            })->all();
        });
    }

    /**
     * Salva (o aggiorna) un'impostazione e invalida la cache.
     */
    public static function put(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => self::encodeValue($key, $value)],
        );

        Cache::forget(self::CACHE_KEY);
    }

    /** Vero se provider e API key sono entrambi valorizzati. */
    public static function isConfigured(): bool
    {
        return filled(static::get('provider')) && filled(static::get('api_key'));
    }

    private function decodeValue(): ?string
    {
        if ($this->value !== null && in_array($this->key, self::ENCRYPTED_KEYS, true)) {
            try {
                return decrypt($this->value);
            } catch (\Throwable) {
                return null;
            }
        }

        return $this->value;
    }

    private static function encodeValue(string $key, ?string $value): ?string
    {
        if ($value !== null && in_array($key, self::ENCRYPTED_KEYS, true)) {
            return encrypt($value);
        }

        return $value;
    }
}
