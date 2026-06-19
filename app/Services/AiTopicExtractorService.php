<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Extract teachable quiz topics from course outlines via DeepSeek.
 */
class AiTopicExtractorService
{
    public function __construct(
        private DocumentTextExtractor $documentExtractor,
    ) {}

    /**
     * Extract topics from plain text.
     *
     * @return array{topics: string[], error: ?string}
     */
    public function extractFromText(string $text, ?string $courseCode = null, ?string $courseName = null): array
    {
        $text = trim($text);
        if ($text === '') {
            return ['topics' => [], 'error' => 'No outline text to analyze.'];
        }

        if (! AiQuestionService::isGenerationEnabled()) {
            return ['topics' => [], 'error' => 'AI is disabled in Settings → AI.'];
        }

        $apiKey = $this->getDeepSeekKey();
        if ($apiKey === null) {
            return ['topics' => [], 'error' => 'DeepSeek API key not set. Add a key in Settings → AI.'];
        }

        $context = '';
        if ($courseCode !== null && trim($courseCode) !== '') {
            $context .= "Course code (exclude as a topic): " . trim($courseCode) . "\n";
        }
        if ($courseName !== null && trim($courseName) !== '') {
            $context .= "Course title (exclude as a topic): " . trim($courseName) . "\n";
        }

        $snippet = mb_substr($text, 0, 12000);
        if (mb_strlen($text) > 12000) {
            $snippet .= "\n[... outline truncated ...]";
        }

        $prompt = <<<PROMPT
You extract exam-ready topic labels from a course outline or syllabus.

{$context}
Rules:
- Return ONLY a JSON array of strings, e.g. ["Topic A","Topic B"].
- Each item must be a short teachable subject (2–8 words), suitable as a quiz topic tag.
- EXCLUDE: course code, course title, institution name, lecturer name, credit hours, prerequisites, grading policy, references/bibliography, "Week 1", "Module 2", page numbers, dates, and generic headers like "Introduction", "Overview", "Objectives", "Course Description".
- Prefer chapter/unit themes and substantive learning outcomes.
- No duplicates. Maximum 25 topics.

Outline:
---
{$snippet}
---
PROMPT;

        try {
            $response = Http::withToken($apiKey)
                ->timeout(60)
                ->post('https://api.deepseek.com/v1/chat/completions', [
                    'model' => 'deepseek-chat',
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'temperature' => 0.3,
                    'max_tokens' => 2048,
                ]);
        } catch (\Throwable $e) {
            Log::warning('AiTopicExtractor: HTTP error', ['message' => $e->getMessage()]);

            return ['topics' => [], 'error' => 'Could not reach DeepSeek. Try again shortly.'];
        }

        if (! $response->successful()) {
            $parsed = $response->json() ?? [];
            $apiMsg = $parsed['error']['message'] ?? $parsed['message'] ?? 'API error';

            return ['topics' => [], 'error' => 'DeepSeek error: ' . $apiMsg];
        }

        $body = $response->json();
        $content = $body['choices'][0]['message']['content'] ?? null;
        if (! is_string($content) || trim($content) === '') {
            return ['topics' => [], 'error' => 'AI returned an empty response.'];
        }

        $decoded = $this->parseJsonArray($content);
        if (! is_array($decoded)) {
            return ['topics' => [], 'error' => 'Could not parse topics from AI response.'];
        }

        $topics = $this->sanitizeTopics($decoded, $courseCode, $courseName);

        if (empty($topics)) {
            return ['topics' => [], 'error' => 'No suitable topics found. Add topics manually or try a different outline.'];
        }

        return ['topics' => $topics, 'error' => null];
    }

    /**
     * @return array{topics: string[], error: ?string}
     */
    public function extractFromFile(UploadedFile $file, ?string $courseCode = null, ?string $courseName = null): array
    {
        $text = $this->documentExtractor->extract($file);
        if (trim($text) === '') {
            return ['topics' => [], 'error' => 'Could not read text from the uploaded file. Use .txt, .pdf, or .docx.'];
        }

        return $this->extractFromText($text, $courseCode, $courseName);
    }

    /** @param  array<int, mixed>  $raw */
    private function sanitizeTopics(array $raw, ?string $courseCode, ?string $courseName): array
    {
        $excludePatterns = [
            '/^week\s*\d+/i',
            '/^module\s*\d+/i',
            '/^unit\s*\d+/i',
            '/^lecture\s*\d+/i',
            '/^chapter\s*\d+$/i',
            '/^section\s*\d+/i',
            '/^\d+(\.\d+)*$/',
            '/^introduction$/i',
            '/^overview$/i',
            '/^objectives?$/i',
            '/^course\s+(description|outline|syllabus|content)$/i',
            '/^learning\s+outcomes?$/i',
            '/^assessment$/i',
            '/^references?$/i',
            '/^bibliography$/i',
            '/^prerequisites?$/i',
            '/^credit\s*hours?$/i',
            '/^table\s+of\s+contents$/i',
        ];

        $courseCodeNorm = $courseCode !== null ? strtolower(trim($courseCode)) : '';
        $courseNameNorm = $courseName !== null ? strtolower(trim($courseName)) : '';

        $out = [];
        foreach ($raw as $item) {
            if (! is_string($item)) {
                continue;
            }
            $t = trim(preg_replace('/\s+/u', ' ', $item) ?? $item);
            if ($t === '' || mb_strlen($t) > 80) {
                continue;
            }
            $lower = strtolower($t);
            if ($courseCodeNorm !== '' && ($lower === $courseCodeNorm || str_contains($lower, $courseCodeNorm))) {
                continue;
            }
            if ($courseNameNorm !== '' && ($lower === $courseNameNorm || str_contains($courseNameNorm, $lower) || str_contains($lower, $courseNameNorm))) {
                continue;
            }
            $skip = false;
            foreach ($excludePatterns as $pattern) {
                if (preg_match($pattern, $t)) {
                    $skip = true;
                    break;
                }
            }
            if ($skip) {
                continue;
            }
            if (! in_array($t, $out, true)) {
                $out[] = $t;
            }
        }

        return array_slice($out, 0, 25);
    }

    private function getDeepSeekKey(): ?string
    {
        $key = Setting::getValue(Setting::KEY_DEEPSEEK_API);
        if ($key !== null && $key !== '') {
            return $key;
        }
        $key = config('services.deepseek.key');

        return ($key !== null && $key !== '') ? $key : null;
    }

    /** @return array<int, mixed>|null */
    private function parseJsonArray(string $content): ?array
    {
        $content = trim($content);
        if (preg_match('/^```(?:json)?\s*([\s\S]*?)```\s*$/s', $content, $m)) {
            $content = trim($m[1]);
        } elseif (preg_match('/^```(?:json)?\s*([\s\S]*?)(?=```|$)/s', $content, $m)) {
            $content = trim($m[1]);
        }
        $start = strpos($content, '[');
        if ($start === false) {
            $decoded = json_decode($content, true);

            return is_array($decoded) ? $decoded : null;
        }
        $end = strrpos($content, ']');
        if ($end === false || $end <= $start) {
            $decoded = json_decode(substr($content, $start), true);

            return is_array($decoded) ? $decoded : null;
        }
        $json = substr($content, $start, $end - $start + 1);
        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : null;
    }
}
