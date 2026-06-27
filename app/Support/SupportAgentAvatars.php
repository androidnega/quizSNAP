<?php

namespace App\Support;

final class SupportAgentAvatars
{
    public const DEFAULT = 'vector:headset';

    /** @return list<string> */
    public static function emojiPresets(): array
    {
        return ['😊', '🎧', '💬', '🛟', '⭐', '👋', '🙋', '💡', '🔧', '✨', '🤝', '📞'];
    }

    /** @return array<string, array{label: string, viewBox: string, path: string}> */
    public static function vectorPresets(): array
    {
        return [
            'headset' => [
                'label' => 'Headset',
                'viewBox' => '0 0 24 24',
                'path' => 'M4 12a8 8 0 0116 0v4a3 3 0 01-3 3h-2v-6h4v-2a6 6 0 00-12 0v2h4v6H7a3 3 0 01-3-3v-4z',
            ],
            'chat' => [
                'label' => 'Chat',
                'viewBox' => '0 0 24 24',
                'path' => 'M8 10h8M8 14h5M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
            ],
            'life-ring' => [
                'label' => 'Help',
                'viewBox' => '0 0 24 24',
                'path' => 'M12 8v4m0 4h.01M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83M12 3a9 9 0 100 18 9 9 0 000-18z',
            ],
            'shield' => [
                'label' => 'Shield',
                'viewBox' => '0 0 24 24',
                'path' => 'M12 3l8 4v5c0 5-3.5 9-8 9s-8-4-8-9V7l8-4z',
            ],
            'star' => [
                'label' => 'Star',
                'viewBox' => '0 0 24 24',
                'path' => 'M12 2l2.9 6.9L22 10l-5.5 4.7L18.2 22 12 18.5 5.8 22l1.7-7.3L2 10l7.1-1.1L12 2z',
            ],
            'spark' => [
                'label' => 'Spark',
                'viewBox' => '0 0 24 24',
                'path' => 'M13 2L9 12H3l8.5 10L11 12h6l-4-10z',
            ],
        ];
    }

    public static function isValid(?string $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (str_starts_with($value, 'emoji:')) {
            $emoji = substr($value, 6);

            return $emoji !== '' && in_array($emoji, self::emojiPresets(), true);
        }

        if (str_starts_with($value, 'vector:')) {
            $id = substr($value, 7);

            return isset(self::vectorPresets()[$id]);
        }

        return false;
    }

    public static function normalize(?string $value): ?string
    {
        $value = $value !== null ? trim($value) : '';
        if ($value === '') {
            return null;
        }

        return self::isValid($value) ? $value : null;
    }

    /** @return array{type: string, emoji?: string, vector?: string, label?: string, viewBox?: string, path?: string}> */
    public static function resolve(?string $value): array
    {
        $value = self::normalize($value) ?? self::DEFAULT;

        if (str_starts_with($value, 'emoji:')) {
            return [
                'type' => 'emoji',
                'emoji' => substr($value, 6),
            ];
        }

        $id = str_starts_with($value, 'vector:') ? substr($value, 7) : 'headset';
        $vector = self::vectorPresets()[$id] ?? self::vectorPresets()['headset'];

        return [
            'type' => 'vector',
            'vector' => $id,
            'label' => $vector['label'],
            'viewBox' => $vector['viewBox'],
            'path' => $vector['path'],
        ];
    }

    /** @return array{vectors: list<array{id: string, label: string, viewBox: string, path: string}>, emojis: list<string>, default: string} */
    public static function catalog(): array
    {
        $vectors = [];
        foreach (self::vectorPresets() as $id => $def) {
            $vectors[] = array_merge(['id' => 'vector:'.$id], $def);
        }

        return [
            'vectors' => $vectors,
            'emojis' => self::emojiPresets(),
            'default' => self::DEFAULT,
        ];
    }
}
