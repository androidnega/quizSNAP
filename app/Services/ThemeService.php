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
        $presets = config('themes');
        if (is_array($presets) && $presets !== []) {
            return $presets;
        }

        $path = config_path('themes.php');
        if (is_file($path)) {
            $loaded = require $path;
            if (is_array($loaded) && $loaded !== []) {
                return $loaded;
            }
        }

        return [self::DEFAULT_PRESET => $this->emergencyPreset()];
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

        $preset = $presets[$id] ?? $presets[self::DEFAULT_PRESET] ?? reset($presets);
        if (! is_array($preset)) {
            $preset = $this->emergencyPreset();
            $id = self::DEFAULT_PRESET;
        }

        return array_merge(['id' => $id], $preset);
    }

    public function isValidPreset(?string $id): bool
    {
        return is_string($id) && $id !== '' && array_key_exists($id, $this->allPresets());
    }

    public function bustCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Minimal fallback when config/themes.php cannot be loaded.
     *
     * @return array<string, mixed>
     */
    private function emergencyPreset(): array
    {
        return [
            'name' => 'QuizSnap Classic',
            'description' => 'Default theme',
            'theme_color' => '#fafaf9',
            'fonts' => [
                'sans' => 'Inter',
                'display' => 'Outfit',
                'url' => 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap',
            ],
            'primary' => [
                50 => '#eff6ff', 100 => '#dbeafe', 200 => '#bfdbfe', 300 => '#93c5fd',
                400 => '#60a5fa', 500 => '#3b82f6', 600 => '#2563eb', 700 => '#1d4ed8',
                800 => '#1e40af', 900 => '#1e3a8a',
            ],
            'brand' => '#fbbf24',
            'brand_dark' => '#f59e0b',
            'brand_border' => 'rgba(245, 158, 11, 0.35)',
            'brand_soft' => '#fef3c7',
            'brand_deep' => '#b45309',
            'brand_hover' => 'rgba(245, 158, 11, 0.25)',
            'wordmark_a' => '#1d4ed8',
            'wordmark_b' => '#fbbf24',
            'bg' => '#fafaf9',
            'surface' => '#ffffff',
            'text' => '#0f172a',
            'muted' => '#64748b',
            'border' => '#e2e8f0',
        ];
    }
}
