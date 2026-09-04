<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A small, generic key-value store for app-wide settings that need to be
 * editable from the admin UI without a deploy (unlike the .env-backed
 * config values used elsewhere, e.g. config/dashboard.php's threshold).
 * One row per setting key; the value is arbitrary JSON.
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::query()->where('key', $key)->value('value') ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
