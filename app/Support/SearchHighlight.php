<?php

namespace App\Support;

final class SearchHighlight
{
    /**
     * Escape text and wrap case-insensitive matches of $query in <mark class="search-hit">.
     */
    public static function mark(?string $text, ?string $query): string
    {
        $text = (string) ($text ?? '');
        if ($text === '') {
            return '';
        }

        $escaped = e($text);
        $query = trim((string) ($query ?? ''));
        if ($query === '' || mb_strlen($query) < 1) {
            return $escaped;
        }

        $parts = preg_split('/\s+/u', $query, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $parts = array_values(array_unique(array_filter($parts, fn ($p) => mb_strlen($p) >= 1)));
        if ($parts === []) {
            return $escaped;
        }

        // Longest first so multi-word highlights stay coherent.
        usort($parts, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        foreach ($parts as $part) {
            $pattern = '/('.preg_quote($part, '/').')/iu';
            $escaped = preg_replace(
                $pattern,
                '<mark class="search-hit">$1</mark>',
                $escaped
            ) ?? $escaped;
        }

        return $escaped;
    }
}
