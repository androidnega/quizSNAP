<?php

namespace App\Services;

use App\Models\AiGenerationLog;
use App\Models\Question;
use App\Models\QuestionPool;
use App\Models\Quiz;
use App\Models\Setting;
use App\Support\QuestionTypes;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiQuestionService
{
    /** Last API error message — set on any failure, cleared on success. */
    private ?string $lastApiError = null;

    public static function isGenerationEnabled(): bool
    {
        return Setting::getValue(Setting::KEY_AI_QUIZ_GENERATION_ENABLED, '1') === '1';
    }

    /** DeepSeek API key: Dashboard → Settings → AI first, then .env DEEPSEEK_API_KEY. */
    private function getDeepSeekKey(): ?string
    {
        $key = Setting::getValue(Setting::KEY_DEEPSEEK_API);
        if ($key !== null && $key !== '') {
            return $key;
        }
        $key = config('services.deepseek.key');

        return ($key !== null && $key !== '') ? $key : null;
    }

    /**
     * Call DeepSeek API (OpenAI-compatible). Sets $this->lastApiError on failure.
     * Returns ['text' => string|null, 'usage' => [...]].
     */
    private function callDeepSeek(string $apiKey, string $prompt): array
    {
        $emptyUsage = ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];
        $response = Http::withToken($apiKey)
            ->timeout(90)
            ->post('https://api.deepseek.com/v1/chat/completions', [
                'model' => 'deepseek-chat',
                'messages' => [['role' => 'user', 'content' => $prompt]],
                'temperature' => 0.7,
                'max_tokens' => 8192,
            ]);
        if (!$response->successful()) {
            $status = $response->status();
            $parsed = $response->json() ?? [];
            $apiMsg = $parsed['error']['message'] ?? $parsed['message'] ?? null;
            $this->lastApiError = '[DeepSeek HTTP ' . $status . ']' . ($apiMsg ? ' ' . $apiMsg : '');
            return ['text' => null, 'usage' => $emptyUsage];
        }
        $body = $response->json();
        if (!is_array($body) || empty($body['choices'][0]['message']['content'])) {
            $this->lastApiError = '[DeepSeek] Empty or unexpected response structure.';
            return ['text' => null, 'usage' => $emptyUsage];
        }
        $usage = $emptyUsage;
        if (isset($body['usage']) && is_array($body['usage'])) {
            $u = $body['usage'];
            $usage = [
                'prompt_tokens' => (int) ($u['prompt_tokens'] ?? 0),
                'completion_tokens' => (int) ($u['completion_tokens'] ?? 0),
                'total_tokens' => (int) ($u['total_tokens'] ?? 0),
            ];
            if ($usage['total_tokens'] === 0 && ($usage['prompt_tokens'] > 0 || $usage['completion_tokens'] > 0)) {
                $usage['total_tokens'] = $usage['prompt_tokens'] + $usage['completion_tokens'];
            }
        }
        $this->lastApiError = null; // clear on success
        return ['text' => $body['choices'][0]['message']['content'], 'usage' => $usage];
    }

    /** Returns the last API error from DeepSeek (useful when generation returns 0 questions). */
    public function getLastApiError(): ?string
    {
        return $this->lastApiError;
    }

    /** Whether AI generation is enabled and a DeepSeek key is configured. */
    public function hasApiKey(): bool
    {
        return self::isGenerationEnabled() && $this->getDeepSeekKey() !== null;
    }

    /** @deprecated Use hasApiKey() — kept for route compatibility. */
    public function hasGeminiKey(): bool
    {
        return $this->hasApiKey();
    }

    /**
     * Maximum number of questions allowed per quiz generation (config + env).
     */
    public function getPerQuizLimit(): int
    {
        $limit = (int) config('quizsnap.ai.max_generation_per_quiz', 250);
        $limit = $limit > 0 ? $limit : 250;
        // Keep practical headroom for large pool creation (e.g. 120-150 questions).
        return max(150, $limit);
    }

    /**
     * Validate pasted AI JSON for the ChatGPT/manual flow.
     * Accepts MCQ, true/false, and fill-in items via optional "type" on each object.
     *
     * @param  array<string, int>|null  $expectedTypeCounts
     * @return array{valid: bool, errors: string[], parsed: array|null}
     */
    public function validateAiJson(string $json, int $expectedCount, ?array $expectedTypeCounts = null): array
    {
        $errors = [];
        $json = trim($json);
        if ($json === '') {
            return ['valid' => false, 'errors' => ['JSON is empty.'], 'parsed' => null];
        }
        $decoded = $this->parseJsonArray($json);
        if (! is_array($decoded)) {
            $err = json_last_error_msg();

            return ['valid' => false, 'errors' => ['Invalid JSON: ' . ($err ?: 'could not parse array.')], 'parsed' => null];
        }
        $count = count($decoded);
        if ($count !== $expectedCount) {
            $errors[] = 'Number of questions is ' . $count . '; expected ' . $expectedCount . '.';
        }
        if ($expectedTypeCounts !== null) {
            $expected = QuestionTypes::normalizeCounts($expectedTypeCounts);
            $actual = [
                QuestionTypes::MCQ => 0,
                QuestionTypes::TRUE_FALSE => 0,
                QuestionTypes::FILL_IN => 0,
            ];
            foreach ($decoded as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $type = QuestionTypes::inferTypeFromItem($item);
                $actual[$type]++;
            }
            foreach ($expected as $type => $needed) {
                if ($needed > 0 && ($actual[$type] ?? 0) !== $needed) {
                    $label = QuestionTypes::labels()[$type] ?? $type;
                    $errors[] = 'Expected ' . $needed . ' ' . $label . ' question(s), found ' . ($actual[$type] ?? 0) . '.';
                }
            }
        }
        foreach ($decoded as $index => $item) {
            $errors = array_merge($errors, $this->validateParsedQuestionItem($item, $index + 1));
        }
        $valid = empty($errors);

        return ['valid' => $valid, 'errors' => $errors, 'parsed' => $valid ? $decoded : null];
    }

    /**
     * @return string[]
     */
    private function validateParsedQuestionItem(mixed $item, int $index): array
    {
        $errors = [];
        if (! is_array($item)) {
            return ['Question ' . $index . ': must be an object.'];
        }
        $type = QuestionTypes::inferTypeFromItem($item);
        $hasText = isset($item['text']) || isset($item['question']);
        if (! $hasText) {
            $errors[] = 'Question ' . $index . ': missing required key "text" or "question".';
        }
        if ($type === QuestionTypes::FILL_IN) {
            $correct = QuestionTypes::extractCorrectAnswer($item);
            if ($correct === null || (is_string($correct) && trim($correct) === '') || (is_array($correct))) {
                $errors[] = 'Question ' . $index . ': fill-in questions require "correct" (expected answer text).';
            }

            return $errors;
        }
        if ($type === QuestionTypes::TRUE_FALSE) {
            $correct = QuestionTypes::extractCorrectAnswer($item);
            if (! QuestionTypes::isValidTrueFalseCorrect($correct)) {
                $errors[] = 'Question ' . $index . ': true/false correct answer must be True or False (or A/B).';
            }

            return $errors;
        }
        if (! isset($item['options']) || ! is_array($item['options'])) {
            $errors[] = 'Question ' . $index . ': missing or invalid "options" (must be an object).';
        } else {
            $keys = array_keys($item['options']);
            sort($keys);
            if ($keys !== ['A', 'B', 'C', 'D']) {
                $errors[] = 'Question ' . $index . ': options must have exactly 4 keys: A, B, C, D.';
            }
        }
        $hasCorrect = isset($item['correct']) || isset($item['correctAnswer']);
        if (! $hasCorrect) {
            $errors[] = 'Question ' . $index . ': missing required key "correct" or "correctAnswer".';
        } else {
            $correct = $item['correct'] ?? $item['correctAnswer'];
            if (! in_array((string) $correct, ['A', 'B', 'C', 'D'], true)) {
                $errors[] = 'Question ' . $index . ': correct answer must be one of A, B, C, D.';
            }
        }

        return $errors;
    }

    /**
     * Create question pools from validated AI JSON (ChatGPT/manual flow).
     *
     * @return int[]
     */
    public function createPoolsFromValidatedJson(Quiz $quiz, array $items): array
    {
        $ids = [];
        $topicFallback = 'General knowledge';
        foreach ($items as $item) {
            $poolId = $this->createPoolFromParsedItem($quiz, $item, $topicFallback);
            if ($poolId !== null) {
                $ids[] = $poolId;
            }
        }

        return $ids;
    }

    /**
     * @param  array<string, int>  $typeCounts
     */
    public function buildMixedTypePrompt(string $topicNames, array $typeCounts, bool $includeExplanations = false): string
    {
        $counts = QuestionTypes::normalizeCounts($typeCounts);
        $total = array_sum($counts);
        if ($total < 1) {
            $counts[QuestionTypes::MCQ] = 1;
            $total = 1;
        }
        $parts = [];
        if ($counts[QuestionTypes::MCQ] > 0) {
            $parts[] = $counts[QuestionTypes::MCQ] . ' multiple choice (MCQ) with exactly 4 options (A–D)';
        }
        if ($counts[QuestionTypes::TRUE_FALSE] > 0) {
            $parts[] = $counts[QuestionTypes::TRUE_FALSE] . ' true/false';
        }
        if ($counts[QuestionTypes::FILL_IN] > 0) {
            $parts[] = $counts[QuestionTypes::FILL_IN] . ' fill-in-the-blank (short answer)';
        }
        $mix = implode(', ', $parts);
        $prompt = 'Use ONLY these precise topics—do not add or substitute others: ' . $topicNames . ".\n"
            . 'Generate exactly ' . $total . ' quiz questions that clearly align with these topics: ' . $mix . ".\n"
            . "Base each question on information directly relevant to one or more of the listed topics.\n"
            . "Reply with a JSON array only, no other text.\n"
            . "Each item MUST include: \"type\" (\"mcq\", \"true_false\", or \"fill_in\"), \"text\" (question text), \"topic\" (one listed topic).\n"
            . "MCQ items: \"options\" object with keys A,B,C,D and \"correct\" as one letter.\n"
            . "True/false items: \"correct\" as True or False (no options required).\n"
            . "Fill-in items: \"correct\" as the expected short answer text (no options).\n";
        if ($includeExplanations) {
            $prompt .= "For MCQ and true/false, also include \"explanation_wrong\" and \"explanation_correct\".\n";
        } else {
            $prompt .= "Do not include explanations.\n";
        }
        $prompt .= 'Example: [{"type":"mcq","text":"...?","options":{"A":"...","B":"...","C":"...","D":"..."},"correct":"A","topic":"..."},{"type":"true_false","text":"...","correct":"True","topic":"..."},{"type":"fill_in","text":"...","correct":"expected answer","topic":"..."}]';

        return $prompt;
    }

    /**
     * @return array<string, int>
     */
    public function countExistingByType(Quiz $quiz): array
    {
        $counts = QuestionTypes::normalizeCounts(null);
        foreach ($quiz->questionPools()->get(['type']) as $pool) {
            $type = QuestionTypes::normalize((string) ($pool->type ?? QuestionTypes::MCQ));
            $counts[$type]++;
        }
        foreach ($quiz->questions()->get(['type']) as $question) {
            $type = QuestionTypes::normalize((string) ($question->type ?? QuestionTypes::MCQ));
            $counts[$type]++;
        }

        return $counts;
    }

    /**
     * @return array<string, int>
     */
    public function resolveBatchTypeCounts(Quiz $quiz, int $batchSize): array
    {
        $targets = $quiz->getQuestionTypeCounts();
        $existing = $this->countExistingByType($quiz);
        $needed = [];
        foreach ($targets as $type => $target) {
            $needed[$type] = max(0, $target - ($existing[$type] ?? 0));
        }
        if (array_sum($needed) === 0) {
            return [QuestionTypes::MCQ => max(1, $batchSize)];
        }
        $batch = [];
        $remaining = max(1, $batchSize);
        $guard = 0;
        while ($remaining > 0 && array_sum($needed) > 0 && $guard < 1000) {
            $guard++;
            $progress = false;
            foreach ([QuestionTypes::MCQ, QuestionTypes::TRUE_FALSE, QuestionTypes::FILL_IN] as $type) {
                if ($remaining <= 0) {
                    break 2;
                }
                if (($needed[$type] ?? 0) > 0) {
                    $batch[$type] = ($batch[$type] ?? 0) + 1;
                    $needed[$type]--;
                    $remaining--;
                    $progress = true;
                }
            }
            if (! $progress) {
                break;
            }
        }
        if ($batch === []) {
            $batch[QuestionTypes::MCQ] = max(1, $batchSize);
        }

        return $batch;
    }

    private function createPoolFromParsedItem(Quiz $quiz, array $item, string $topicFallback): ?int
    {
        $type = QuestionTypes::inferTypeFromItem($item);
        $text = (string) ($item['text'] ?? $item['question'] ?? 'AI Question');
        $topic = isset($item['topic']) && is_string($item['topic']) ? $item['topic'] : $topicFallback;
        $explanationWrong = is_string($item['explanation_wrong'] ?? null) ? $item['explanation_wrong'] : null;
        $explanationCorrect = is_string($item['explanation_correct'] ?? null) ? $item['explanation_correct'] : null;
        $rawCorrect = QuestionTypes::extractCorrectAnswer($item);

        if ($type === QuestionTypes::FILL_IN) {
            $correct = QuestionTypes::coerceCorrectToString($rawCorrect);
            if ($correct === '') {
                return null;
            }
            $pool = QuestionPool::create([
                'quiz_id' => $quiz->id,
                'question_text' => $text,
                'type' => $type,
                'options' => null,
                'correct_answer' => $correct,
                'topic' => $topic,
                'is_approved' => false,
                'explanation_wrong' => $explanationWrong,
                'explanation_correct' => $explanationCorrect,
            ]);

            return $pool->id;
        }

        if ($type === QuestionTypes::TRUE_FALSE) {
            $correct = QuestionTypes::normalizeTrueFalseCorrect($rawCorrect ?? 'True');
            $pool = QuestionPool::create([
                'quiz_id' => $quiz->id,
                'question_text' => $text,
                'type' => $type,
                'options' => QuestionTypes::trueFalseOptions(),
                'correct_answer' => $correct,
                'topic' => $topic,
                'is_approved' => false,
                'explanation_wrong' => $explanationWrong,
                'explanation_correct' => $explanationCorrect,
            ]);

            return $pool->id;
        }

        $opts = $item['options'] ?? [];
        $correct = QuestionTypes::coerceCorrectToString($rawCorrect ?? 'A');
        if (! in_array($correct, ['A', 'B', 'C', 'D'], true)) {
            $correct = 'A';
        }
        $options = $this->normalizeOptions($opts, $topicFallback);
        $pool = QuestionPool::create([
            'quiz_id' => $quiz->id,
            'question_text' => $text,
            'type' => QuestionTypes::MCQ,
            'options' => $options,
            'correct_answer' => $correct,
            'topic' => $topic,
            'is_approved' => false,
            'explanation_wrong' => $explanationWrong,
            'explanation_correct' => $explanationCorrect,
        ]);

        return $pool->id;
    }

    /**
     * Call DeepSeek for question generation. Returns ['text' => ..., 'provider' => ..., 'usage' => ...].
     */
    private function callAiWithUsage(string $prompt, bool $geminiOnly = false): array
    {
        $empty = ['text' => null, 'provider' => null, 'usage' => ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0]];
        unset($geminiOnly);

        if (! self::isGenerationEnabled()) {
            $this->lastApiError = 'AI question generation is disabled in Settings → AI.';

            return $empty;
        }

        $deepseekKey = $this->getDeepSeekKey();
        if ($deepseekKey === null) {
            $this->lastApiError = 'DeepSeek API key not set. Add a key in Dashboard → Settings → AI.';
            Log::warning('AI question generation: no DeepSeek key');

            return $empty;
        }

        $result = $this->callDeepSeek($deepseekKey, $prompt);
        if (isset($result['text']) && $result['text'] !== null && $result['text'] !== '') {
            $this->lastApiError = null;

            return [
                'text' => $result['text'],
                'provider' => 'deepseek',
                'usage' => $result['usage'] ?? $empty['usage'],
            ];
        }

        Log::warning('AI question generation: DeepSeek returned no text', [
            'last_api_error' => $this->lastApiError,
        ]);

        return $empty;
    }

    /** Returns raw text or null (e.g. wrong-answer explanations). */
    private function callAi(string $prompt): ?string
    {
        $result = $this->callAiWithUsage($prompt);
        return $result['text'];
    }

    /**
     * Extract JSON array from model response (may be wrapped in markdown or text). Uses first [ to last ] for outer array.
     * Strips markdown code fences so ```json [...] ``` is parsed correctly.
     */
    private function parseJsonArray(string $content): ?array
    {
        $content = trim($content);
        // Strip ```json ... ``` or ``` ... ```
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
        if (is_array($decoded)) {
            return $decoded;
        }
        // Truncated or malformed JSON: try repairing (trailing comma, missing bracket)
        $repaired = preg_replace('/,\s*$/', '', $json);
        if (substr(rtrim($repaired), -1) !== ']') {
            $repaired .= ']';
        }
        $decoded = json_decode($repaired, true);
        if (is_array($decoded)) {
            return $decoded;
        }
        return null;
    }

    /**
     * Normalize options from AI (object {"A":"...","B":"..."} or list [{"key":"A","text":"..."}] ) to [["key"=>"A","text"=>"..."], ...].
     */
    private function normalizeOptions(mixed $opts, string $topicNames): array
    {
        $out = [];
        foreach (['A', 'B', 'C', 'D'] as $k) {
            $text = 'Option ' . $k;
            if (is_array($opts)) {
                if (isset($opts[$k]) && is_string($opts[$k])) {
                    $text = $opts[$k];
                } elseif (isset($opts[$k]) && is_array($opts[$k])) {
                    $text = (string) ($opts[$k]['text'] ?? $opts[$k]['value'] ?? $text);
                } else {
                    foreach ($opts as $o) {
                        if (is_array($o) && ($o['key'] ?? $o['letter'] ?? null) === $k) {
                            $text = (string) ($o['text'] ?? $o['value'] ?? $text);
                            break;
                        }
                    }
                }
            }
            $out[] = ['key' => $k, 'text' => $text];
        }
        return $out;
    }

    /** @deprecated Alias for generatePoolAndStore — route compatibility. */
    public function generatePoolAndStoreGeminiOnly(Quiz $quiz, array $topics, int $count, ?string $sourceText = null): array
    {
        return $this->generatePoolAndStore($quiz, $topics, $count, $sourceText);
    }

    /**
     * Generate questions via DeepSeek and store in question_pools as unapproved.
     * Blocked when AI is disabled or no API key: returns []. Enforces per-quiz limit.
     */
    public function generatePoolAndStore(Quiz $quiz, array $topics, int $count, ?string $sourceText = null, bool $geminiOnly = false): array
    {
        if (! $this->hasApiKey()) {
            return [];
        }
        $count = min($count, $this->getPerQuizLimit());
        if ($count < 1) {
            return [];
        }
        $topicNames = collect($topics)->pluck('name')->filter()->implode(', ');
        if (empty($topicNames)) {
            $topicNames = 'General knowledge';
        }
        
        // Use batching for large requests to avoid token limits
        $batchSize = 20; // Generate max 20 questions per API call
        if ($count > $batchSize) {
            return $this->generatePoolInBatches($quiz, $topics, $topicNames, $count, $sourceText, $batchSize, $geminiOnly);
        }
        
        // Single batch for smaller requests
        $context = '';
        if ($sourceText !== null && $sourceText !== '') {
            $context = "Use the following material as the primary source for generating exam questions. Base questions on this content.\n\n---\n" . mb_substr($sourceText, 0, 80000) . "\n---\n\n";
        }
        $batchTypeCounts = $this->resolveBatchTypeCounts($quiz, $count);
        $prompt = $context . $this->buildMixedTypePrompt($topicNames, $batchTypeCounts, true);
        $result = $this->callAiWithUsage($prompt, $geminiOnly);
        $content = $result['text'] ?? null;
        $decoded = ($content !== null && $content !== '') ? $this->parseJsonArray($content) : null;
        if (! is_array($decoded) || empty($decoded)) {
            // Fallback: simpler prompt often works when the main one times out or returns bad JSON.
            $fallbackCount = min(3, $count);
            $ids = $this->generatePoolAndStoreSimple($quiz, $topicNames, $fallbackCount, '', $geminiOnly);
            if (! empty($ids)) {
                return array_slice($ids, 0, $count);
            }
            if ($count >= 1) {
                $ids = $this->generateOneQuestionMinimal($quiz, $topicNames, $geminiOnly);
                if (! empty($ids)) {
                    return $ids;
                }
            }
            return [];
        }
        $ids = [];
        foreach (array_slice($decoded, 0, $count) as $item) {
            if (! is_array($item)) {
                continue;
            }
            $poolId = $this->createPoolFromParsedItem($quiz, $item, $topicNames);
            if ($poolId !== null) {
                $ids[] = $poolId;
            }
        }
        $usage = $result['usage'] ?? ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];
        AiGenerationLog::create([
            'quiz_id' => $quiz->id,
            'prompt_tokens' => $usage['prompt_tokens'],
            'completion_tokens' => $usage['completion_tokens'],
            'total_tokens' => $usage['total_tokens'] ?: ($usage['prompt_tokens'] + $usage['completion_tokens']),
            'provider' => $result['provider'] ?? null,
            'questions_generated' => count($ids),
            'generated_at' => now(),
        ]);
        return $ids;
    }

    /**
     * Ultra-minimal: one question only, no context. Used when all other attempts return empty.
     */
    private function generateOneQuestionMinimal(Quiz $quiz, string $topicNames, bool $geminiOnly = false): array
    {
        $prompt = "Topic: {$topicNames}. Write 1 multiple choice question. Reply with ONLY this JSON array (no other text): [{\"text\":\"Your question here\",\"options\":{\"A\":\"\",\"B\":\"\",\"C\":\"\",\"D\":\"\"},\"correct\":\"A\"}]";
        $result = $this->callAiWithUsage($prompt, $geminiOnly);
        $content = $result['text'] ?? null;
        if ($content === null || $content === '') {
            return [];
        }
        $decoded = $this->parseJsonArray($content);
        if (! is_array($decoded) || empty($decoded)) {
            return [];
        }
        $item = $decoded[0];
        $text = $item['text'] ?? $item['question'] ?? 'AI Question';
        $opts = $item['options'] ?? $item['choices'] ?? [];
        $correct = $item['correct'] ?? $item['correct_answer'] ?? 'A';
        $options = $this->normalizeOptions($opts, $topicNames);
        $pool = QuestionPool::create([
            'quiz_id' => $quiz->id,
            'question_text' => $text,
            'options' => $options,
            'correct_answer' => $correct,
            'topic' => $topicNames,
            'is_approved' => false,
            'explanation_wrong' => null,
            'explanation_correct' => null,
        ]);
        if (isset($result['usage'], $result['provider'])) {
            AiGenerationLog::create([
                'quiz_id' => $quiz->id,
                'prompt_tokens' => $result['usage']['prompt_tokens'] ?? 0,
                'completion_tokens' => $result['usage']['completion_tokens'] ?? 0,
                'total_tokens' => $result['usage']['total_tokens'] ?? 0,
                'provider' => $result['provider'],
                'questions_generated' => 1,
                'generated_at' => now(),
            ]);
        }
        return [$pool->id];
    }

    /**
     * Simpler prompt (minimal JSON) to improve reliability when the full prompt returns empty or unparseable output.
     */
    private function generatePoolAndStoreSimple(Quiz $quiz, string $topicNames, int $count, string $context = '', bool $geminiOnly = false): array
    {
        $prompt = $context
            . "Topics: {$topicNames}. Generate exactly {$count} multiple choice questions. "
            . "Reply with ONLY a JSON array, no other text. Each item: {\"text\":\"question\",\"options\":{\"A\":\"...\",\"B\":\"...\",\"C\":\"...\",\"D\":\"...\"},\"correct\":\"A\"}. "
            . "Example: [{\"text\":\"What is 2+2?\",\"options\":{\"A\":\"3\",\"B\":\"4\",\"C\":\"5\",\"D\":\"6\"},\"correct\":\"B\"}]";
        $result = $this->callAiWithUsage($prompt, $geminiOnly);
        $content = $result['text'] ?? null;
        if ($content === null || $content === '') {
            return [];
        }
        $decoded = $this->parseJsonArray($content);
        if (! is_array($decoded) || empty($decoded)) {
            return [];
        }
        $ids = [];
        foreach (array_slice($decoded, 0, $count) as $item) {
            $text = $item['text'] ?? $item['question'] ?? 'AI Question';
            $opts = $item['options'] ?? $item['choices'] ?? [];
            $correct = $item['correct'] ?? $item['correct_answer'] ?? 'A';
            $options = $this->normalizeOptions($opts, $topicNames);
            $pool = QuestionPool::create([
                'quiz_id' => $quiz->id,
                'question_text' => $text,
                'options' => $options,
                'correct_answer' => $correct,
                'topic' => $topicNames,
                'is_approved' => false,
                'explanation_wrong' => null,
                'explanation_correct' => null,
            ]);
            $ids[] = $pool->id;
        }
        if (! empty($ids)) {
            $usage = $result['usage'] ?? ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];
            AiGenerationLog::create([
                'quiz_id' => $quiz->id,
                'prompt_tokens' => $usage['prompt_tokens'],
                'completion_tokens' => $usage['completion_tokens'],
                'total_tokens' => $usage['total_tokens'] ?: ($usage['prompt_tokens'] + $usage['completion_tokens']),
                'provider' => $result['provider'] ?? null,
                'questions_generated' => count($ids),
                'generated_at' => now(),
            ]);
        }
        return $ids;
    }

    /**
     * Generate questions in multiple batches to avoid token limits.
     * Each batch makes a separate API call, then combines results.
     */
    private function generatePoolInBatches(Quiz $quiz, array $topics, string $topicNames, int $totalCount, ?string $sourceText, int $batchSize, bool $geminiOnly = false): array
    {
        $allIds = [];
        $totalPromptTokens = 0;
        $totalCompletionTokens = 0;
        $totalTokens = 0;
        $provider = null;
        
        $context = '';
        if ($sourceText !== null && $sourceText !== '') {
            $context = "Use the following material as the primary source for generating exam questions. Base questions on this content.\n\n---\n" . mb_substr($sourceText, 0, 80000) . "\n---\n\n";
        }
        
        $batches = (int) ceil($totalCount / $batchSize);
        for ($i = 0; $i < $batches; $i++) {
            $remaining = $totalCount - count($allIds);
            $batchCount = min($batchSize, $remaining);
            
            if ($batchCount < 1) {
                break;
            }
            
            $batchNumber = $i + 1;
            $batchTypeCounts = $this->resolveBatchTypeCounts($quiz, $batchCount);
            $prompt = $context . $this->buildMixedTypePrompt($topicNames, $batchTypeCounts, true)
                . "\nThis is batch {$batchNumber} of {$batches}. Generate UNIQUE questions that differ from previous batches.";
            
            $result = $this->callAiWithUsage($prompt, $geminiOnly);
            $content = $result['text'] ?? null;
            
            if ($content === null || $content === '') {
                continue; // Skip failed batch, try next one
            }
            
            $decoded = $this->parseJsonArray($content);
            if (!is_array($decoded)) {
                continue; // Skip invalid batch
            }
            
            // Store questions from this batch
            foreach (array_slice($decoded, 0, $batchCount) as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $poolId = $this->createPoolFromParsedItem($quiz, $item, $topicNames);
                if ($poolId !== null) {
                    $allIds[] = $poolId;
                }
            }
            
            // Accumulate token usage
            $usage = $result['usage'] ?? ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];
            $totalPromptTokens += $usage['prompt_tokens'];
            $totalCompletionTokens += $usage['completion_tokens'];
            $totalTokens += $usage['total_tokens'];
            if ($provider === null && isset($result['provider'])) {
                $provider = $result['provider'];
            }
            
            // Small delay between batches to avoid rate limits
            if ($i < $batches - 1) {
                usleep(500000); // 0.5 second delay
            }
        }
        
        // Log total usage for all batches
        if (!empty($allIds)) {
            AiGenerationLog::create([
                'quiz_id' => $quiz->id,
                'prompt_tokens' => $totalPromptTokens,
                'completion_tokens' => $totalCompletionTokens,
                'total_tokens' => $totalTokens ?: ($totalPromptTokens + $totalCompletionTokens),
                'provider' => $provider,
                'questions_generated' => count($allIds),
                'generated_at' => now(),
            ]);
        }
        
        return $allIds;
    }

    /**
     * Generate questions via AI and store in questions table (for runtime pool top-up).
     * Blocked when no API key (returns []). Enforces per-quiz limit. Logs token usage per quiz.
     * For large counts (>20), uses batching to avoid token limits.
     * Returns array of question IDs.
     */
    public function generateAndStore(Quiz $quiz, array $topics, int $count, array $excludeIds): array
    {
        if (!$this->hasApiKey()) {
            return [];
        }
        $count = min($count, $this->getPerQuizLimit());
        if ($count < 1) {
            return [];
        }
        $topicNames = collect($topics)->pluck('name')->filter()->implode(', ');
        if (empty($topicNames)) {
            $topicNames = 'General knowledge';
        }
        
        // Use batching for large requests
        $batchSize = 20;
        if ($count > $batchSize) {
            return $this->generateAndStoreInBatches($quiz, $topicNames, $count, $batchSize);
        }
        
        // Single batch for smaller requests
        $prompt = "Use ONLY these precise topics—do not add or substitute others: {$topicNames}. "
            . "Generate exactly {$count} multiple choice quiz questions (MCQ) that clearly align with these topics. "
            . "Base each question on information directly relevant to one or more of the listed topics. "
            . "For each question provide: question text, 4 options (A,B,C,D), and the correct letter. "
            . "Format as JSON array only: [{\"text\":\"...\",\"options\":{\"A\":\"...\",\"B\":\"...\",\"C\":\"...\",\"D\":\"...\"},\"correct\":\"A\"}]";
        $result = $this->callAiWithUsage($prompt);
        $content = $result['text'] ?? null;
        if ($content === null || $content === '') {
            return [];
        }
        $decoded = $this->parseJsonArray($content);
        if (!is_array($decoded)) {
            return [];
        }
        $ids = [];
        foreach (array_slice($decoded, 0, $count) as $item) {
            $text = $item['text'] ?? 'AI Question';
            $opts = $item['options'] ?? [];
            $correct = $item['correct'] ?? 'A';
            $options = [];
            foreach (['A', 'B', 'C', 'D'] as $k) {
                $options[] = ['key' => $k, 'text' => $opts[$k] ?? 'Option ' . $k];
            }
            $q = Question::create([
                'quiz_id' => $quiz->id,
                'text' => $text,
                'type' => 'mcq',
                'options' => $options,
                'correct_answer' => $correct,
                'topic' => $topicNames,
                'source' => 'ai',
                'points' => 1,
            ]);
            $ids[] = $q->id;
        }
        $usage = $result['usage'] ?? ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];
        AiGenerationLog::create([
            'quiz_id' => $quiz->id,
            'prompt_tokens' => $usage['prompt_tokens'],
            'completion_tokens' => $usage['completion_tokens'],
            'total_tokens' => $usage['total_tokens'] ?: ($usage['prompt_tokens'] + $usage['completion_tokens']),
            'provider' => $result['provider'] ?? null,
            'questions_generated' => count($ids),
            'generated_at' => now(),
        ]);
        return $ids;
    }

    /**
     * Generate questions in batches for runtime pool (questions table).
     */
    private function generateAndStoreInBatches(Quiz $quiz, string $topicNames, int $totalCount, int $batchSize): array
    {
        $allIds = [];
        $totalPromptTokens = 0;
        $totalCompletionTokens = 0;
        $totalTokens = 0;
        $provider = null;
        
        $batches = (int) ceil($totalCount / $batchSize);
        for ($i = 0; $i < $batches; $i++) {
            $remaining = $totalCount - count($allIds);
            $batchCount = min($batchSize, $remaining);
            
            if ($batchCount < 1) {
                break;
            }
            
            $batchNumber = $i + 1;
            $prompt = "Use ONLY these precise topics—do not add or substitute others: {$topicNames}. "
                . "Generate exactly {$batchCount} multiple choice quiz questions (MCQ) that clearly align with these topics. "
                . "This is batch {$batchNumber} of {$batches}. Generate UNIQUE questions that differ from previous batches. "
                . "Base each question on information directly relevant to one or more of the listed topics. "
                . "For each question provide: question text, 4 options (A,B,C,D), and the correct letter. "
                . "Format as JSON array only: [{\"text\":\"...\",\"options\":{\"A\":\"...\",\"B\":\"...\",\"C\":\"...\",\"D\":\"...\"},\"correct\":\"A\"}]";
            
            $result = $this->callAiWithUsage($prompt);
            $content = $result['text'] ?? null;
            
            if ($content === null || $content === '') {
                continue;
            }
            
            $decoded = $this->parseJsonArray($content);
            if (!is_array($decoded)) {
                continue;
            }
            
            foreach (array_slice($decoded, 0, $batchCount) as $item) {
                $text = $item['text'] ?? 'AI Question';
                $opts = $item['options'] ?? [];
                $correct = $item['correct'] ?? 'A';
                $options = [];
                foreach (['A', 'B', 'C', 'D'] as $k) {
                    $options[] = ['key' => $k, 'text' => $opts[$k] ?? 'Option ' . $k];
                }
                $q = Question::create([
                    'quiz_id' => $quiz->id,
                    'text' => $text,
                    'type' => 'mcq',
                    'options' => $options,
                    'correct_answer' => $correct,
                    'topic' => $topicNames,
                    'source' => 'ai',
                    'points' => 1,
                ]);
                $allIds[] = $q->id;
            }
            
            $usage = $result['usage'] ?? ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];
            $totalPromptTokens += $usage['prompt_tokens'];
            $totalCompletionTokens += $usage['completion_tokens'];
            $totalTokens += $usage['total_tokens'];
            if ($provider === null && isset($result['provider'])) {
                $provider = $result['provider'];
            }
            
            if ($i < $batches - 1) {
                usleep(500000); // 0.5 second delay between batches
            }
        }
        
        if (!empty($allIds)) {
            AiGenerationLog::create([
                'quiz_id' => $quiz->id,
                'prompt_tokens' => $totalPromptTokens,
                'completion_tokens' => $totalCompletionTokens,
                'total_tokens' => $totalTokens ?: ($totalPromptTokens + $totalCompletionTokens),
                'provider' => $provider,
                'questions_generated' => count($allIds),
                'generated_at' => now(),
            ]);
        }
        
        return $allIds;
    }

    /**
     * Generate a short, meaning-based explanation of why the student's answer is wrong.
     * Used on result page when the question has no stored explanation_wrong.
     * Returns null if AI is unavailable or fails.
     */
    public function generateWrongAnswerExplanation(Question $question, string $studentAnswer): ?string
    {
        $questionText = is_string($question->text) ? $question->text : '';
        $correct = trim((string) ($question->correct_answer ?? ''));
        $chosen = trim($studentAnswer);
        if ($questionText === '' || $correct === '' || $chosen === '') {
            return null;
        }
        $optionsLines = [];
        if (is_array($question->options)) {
            foreach ($question->options as $opt) {
                $key = $opt['key'] ?? $opt;
                $text = $opt['text'] ?? $opt;
                $optionsLines[] = $key . ': ' . (is_string($text) ? $text : '');
            }
        }
        $optionsStr = !empty($optionsLines) ? implode(' ', $optionsLines) : 'N/A';
        $prompt = "Question: " . mb_substr($questionText, 0, 600) . "\nOptions: " . mb_substr($optionsStr, 0, 400)
            . "\nCorrect answer: " . $correct . ". The student chose: " . $chosen . "."
            . "\nIn one short sentence (max 25 words), explain why the student's answer is wrong in the context of the question. Reply with only that sentence, no label or prefix.";
        $text = $this->callAi($prompt);
        if ($text === null || $text === '') {
            return null;
        }
        $text = trim(preg_replace('/^(Why your answer is wrong|Reason):\s*/i', '', $text));
        return mb_substr($text, 0, 300) ?: null;
    }
}
