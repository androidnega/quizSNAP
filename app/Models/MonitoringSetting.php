<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class MonitoringSetting extends Model
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
        if (! Schema::hasTable('monitoring_settings')) {
            return $default;
        }

        try {
            $row = static::query()->where('key', $key)->first();

            return $row?->value ?? $default;
        } catch (\Throwable $e) {
            report($e);

            return $default;
        }
    }

    public static function set(string $key, mixed $value): void
    {
        if (! Schema::hasTable('monitoring_settings')) {
            return;
        }

        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
