<?php

namespace App\Support;

final class QuestionTypes
{
    public const MCQ = 'mcq';

    public const TRUE_FALSE = 'true_false';

    public const FILL_IN = 'fill_in';

    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            self::MCQ => 'Multiple choice (MCQ)',
            self::TRUE_FALSE => 'True / False',
            self::FILL_IN => 'Fill in the blank',
        ];
    }

    public static function normalize(string $type): string
    {
        $type = strtolower(trim(str_replace([' ', '/'], ['_', '_'], $type)));
        return match ($type) {
            'true_false', 'true-false', 'truefalse', 'tf', 't_f', 'boolean', 'bool' => self::TRUE_FALSE,
            'fill_in', 'fill-in', 'fillin', 'short_answer', 'short-answer', 'shortanswer' => self::FILL_IN,
            default => self::MCQ,
        };
    }

    /**
     * Infer question type from an AI JSON item when type is missing or ambiguous.
     *
     * @param  array<string, mixed>  $item
     */
    public static function inferTypeFromItem(array $item): string
    {
        if (isset($item['type']) && trim((string) $item['type']) !== '') {
            return self::normalize((string) $item['type']);
        }

        $correct = self::extractCorrectAnswer($item);
        if (is_bool($correct)) {
            return self::TRUE_FALSE;
        }

        $opts = $item['options'] ?? null;
        if (is_array($opts) && $opts !== []) {
            $keys = array_map(static fn ($k) => strtoupper((string) $k), array_keys($opts));
            sort($keys);
            if ($keys === ['A', 'B', 'C', 'D']) {
                return self::MCQ;
            }
            if ($keys === ['A', 'B'] && self::optionsLookLikeTrueFalse($opts)) {
                return self::TRUE_FALSE;
            }
        }

        if ($correct !== null && self::isValidTrueFalseCorrect($correct)) {
            return self::TRUE_FALSE;
        }

        if ($correct !== null && ! is_array($correct)) {
            return self::FILL_IN;
        }

        return self::MCQ;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public static function extractCorrectAnswer(array $item): mixed
    {
        foreach (['correct', 'correctAnswer', 'answer', 'expected_answer', 'expected', 'blank_answer', 'fill_answer'] as $key) {
            if (array_key_exists($key, $item)) {
                return $item[$key];
            }
        }

        return null;
    }

    /**
     * Expected answer text for fill-in questions (handles arrays and alternate AI keys).
     *
     * @param  array<string, mixed>  $item
     */
    public static function extractFillInAnswer(array $item): string
    {
        foreach (['correct', 'correctAnswer', 'answer', 'expected_answer', 'expected', 'blank_answer', 'fill_answer'] as $key) {
            if (! array_key_exists($key, $item)) {
                continue;
            }
            $parsed = self::parseFillInScalar($item[$key]);
            if ($parsed !== '') {
                return $parsed;
            }
        }

        if (isset($item['acceptable_answers']) && is_array($item['acceptable_answers'])) {
            foreach ($item['acceptable_answers'] as $entry) {
                $parsed = self::parseFillInScalar($entry);
                if ($parsed !== '') {
                    return $parsed;
                }
            }
        }

        return '';
    }

    public static function parseFillInScalar(mixed $value): string
    {
        if (is_array($value)) {
            foreach ($value as $entry) {
                $parsed = self::parseFillInScalar($entry);
                if ($parsed !== '') {
                    return $parsed;
                }
            }

            return '';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (! is_scalar($value)) {
            return '';
        }
        $text = trim((string) $value);
        if ($text === '' || strcasecmp($text, 'Array') === 0) {
            return '';
        }

        return $text;
    }

    public static function coerceCorrectToString(mixed $value): string
    {
        if (is_array($value)) {
            return self::parseFillInScalar($value);
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_int($value) || is_float($value)) {
            if ((int) $value === 1) {
                return 'true';
            }
            if ((int) $value === 0) {
                return 'false';
            }
        }

        return trim((string) $value);
    }

    public static function isValidTrueFalseCorrect(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }
        $c = strtolower(self::coerceCorrectToString($value));

        return in_array($c, ['true', 'false', 'a', 'b', 't', 'f', 'yes', 'no', '1', '0'], true);
    }

    /**
     * @param  array<mixed, mixed>  $options
     */
    private static function optionsLookLikeTrueFalse(array $options): bool
    {
        $texts = [];
        foreach ($options as $opt) {
            if (is_array($opt)) {
                $texts[] = strtolower(trim((string) ($opt['text'] ?? $opt['value'] ?? '')));
            } else {
                $texts[] = strtolower(trim((string) $opt));
            }
        }

        return count(array_intersect($texts, ['true', 'false'])) >= 2;
    }

    /**
     * @param  array<string, int>|null  $counts
     * @return array<string, int>
     */
    public static function normalizeCounts(?array $counts, int $fallbackTotal = 0): array
    {
        $out = [
            self::MCQ => 0,
            self::TRUE_FALSE => 0,
            self::FILL_IN => 0,
        ];
        if (! is_array($counts)) {
            if ($fallbackTotal > 0) {
                $out[self::MCQ] = $fallbackTotal;
            }

            return $out;
        }
        foreach ($counts as $key => $value) {
            $type = self::normalize((string) $key);
            $out[$type] = max(0, (int) $value);
        }
        if (array_sum($out) === 0 && $fallbackTotal > 0) {
            $out[self::MCQ] = $fallbackTotal;
        }

        return $out;
    }

    public static function total(array $counts): int
    {
        return array_sum(self::normalizeCounts($counts));
    }

    /** @return array{key: string, text: string}[] */
    public static function trueFalseOptions(): array
    {
        return [
            ['key' => 'A', 'text' => 'True'],
            ['key' => 'B', 'text' => 'False'],
        ];
    }

    public static function normalizeTrueFalseCorrect(mixed $correct): string
    {
        $c = strtolower(self::coerceCorrectToString($correct));
        if (in_array($c, ['true', 't', 'yes', 'a', '1'], true)) {
            return 'A';
        }
        if (in_array($c, ['false', 'f', 'no', 'b', '0'], true)) {
            return 'B';
        }

        $upper = strtoupper(self::coerceCorrectToString($correct));

        return in_array($upper, ['A', 'B'], true) ? $upper : 'A';
    }

    public static function answersMatch(string $studentAnswer, string $correctAnswer, string $type): bool
    {
        $student = trim($studentAnswer);
        $correct = trim($correctAnswer);
        if ($student === '' || $correct === '') {
            return false;
        }
        if (self::normalize($type) === self::FILL_IN) {
            return mb_strtolower($student) === mb_strtolower($correct);
        }

        return strtoupper($student) === strtoupper($correct);
    }
}
