<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class ThemeService
{
    public const DEFAULT_PRESET = 'quizsnap-classic';

    public const CACHE_KEY = 'theme:active-preset';

    /**
     * @return array<string, array<string, mixed>>
     */
    public function allPresets(): array
    {
        return config('themes', []);
    }

    /**
     * @return list<string>
     */
    public function presetIds(): array
    {
        return array_keys($this->allPresets());
    }

    public function activePresetId(): string
    {
        return Cache::remember(self::CACHE_KEY, 3600, function () {
            $id = Setting::getValue(Setting::KEY_THEME_PRESET, self::DEFAULT_PRESET);

            return $this->isValidPreset($id) ? (string) $id : self::DEFAULT_PRESET;
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function activePreset(): array
    {
        return $this->resolve($this->activePresetId());
    }

    /**
     * @return array<string, mixed>
     */
    public function resolve(?string $id): array
    {
        $presets = $this->allPresets();
        $id = $this->isValidPreset($id) ? (string) $id : self::DEFAULT_PRESET;

        return array_merge(
            ['id' => $id],
            $presets[$id] ?? $presets[self::DEFAULT_PRESET]
        );
    }

    public function isValidPreset(?string $id): bool
    {
        return is_string($id) && $id !== '' && array_key_exists($id, $this->allPresets());
    }

    public function bustCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
