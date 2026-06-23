<?php

namespace App\Support;

/**
 * Prompt text for AI question generation — assessment design rules shared by DeepSeek calls.
 */
final class AssessmentPromptBuilder
{
    /**
     * Core examiner persona and quality rules (platform-wide).
     */
    public static function designGuidelines(): string
    {
        return <<<'TEXT'
You are an expert assessment designer and examiner.
Your task is to generate challenging, application-based exam questions from the topics provided.

QUALITY PRIORITY (default for most questions):
- Prefer application of knowledge, critical thinking, problem-solving, analysis, and evaluation.
- Use realistic workplace, business, scientific, educational, social, or industry scenarios when appropriate.
- Use short case studies or situational stems so students must think before answering.
- Every question should require reasoning, not bare memorization.

AVOID as the default style (do not make these the majority):
- Simple recall phrasing such as "Define...", "What is...", "List...", "State...", "Mention...".
- Questions answerable by copying a single sentence from memory without understanding.

ALLOWED:
- Occasional recall or definition questions when they fit the topic, but keep them a minority.
- Direct factual checks when embedded in a scenario or when needed for balance.

TARGET COGNITIVE MIX (approximate across the full set):
- 30% Application
- 40% Analysis
- 20% Evaluation
- 10% Creation / synthesis

TARGET DIFFICULTY MIX (approximate across the full set):
- 20% Easy
- 50% Moderate
- 30% Difficult
TEXT;
    }

    /**
     * Per-type authoring rules for supported platform question types.
     */
    public static function typeAuthoringRules(): string
    {
        return <<<'TEXT'
PER-TYPE RULES:

A. Multiple choice (MCQ)
- Use short case studies or realistic scenarios when possible.
- Exactly 4 options (A–D); only one best answer.
- Distractors must be plausible and test misunderstanding, not trick wording.

B. True / false
- Scenario-based; test reasoning and judgment, not bare definitions.
- The statement should require evaluating a situation or claim.

C. Fill-in-the-blank
- Context-based stems; students apply concepts to complete the statement.
- Expected answers should be concise (a word, phrase, or short term), not an essay.
- Use ___ in the question text to show the blank.
- JSON: "type" must be "fill_in", "correct" must be a plain string (not A/B/C/D), never include "options".
TEXT;
    }

    /**
     * Wrap course outline / source material for the prompt.
     */
    public static function sourceMaterialBlock(string $sourceText): string
    {
        $snippet = mb_substr(trim($sourceText), 0, 80000);

        return "SOURCE MATERIAL — use as the primary basis for scenarios, facts, and problems. "
            . "Ground questions in this content; do not invent facts that contradict it.\n\n"
            . "---\n{$snippet}\n---\n\n";
    }

    /**
     * @param  array<string, int>  $typeCounts
     */
    public static function buildMixedTypePrompt(string $topicNames, array $typeCounts, bool $includeExplanations = true): string
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
            $parts[] = $counts[QuestionTypes::FILL_IN] . ' fill-in-the-blank';
        }
        $mix = implode(', ', $parts);

        $prompt = self::designGuidelines() . "\n\n"
            . self::typeAuthoringRules() . "\n\n"
            . 'TOPICS — use ONLY these precise topics; do not add or substitute others: ' . $topicNames . "\n"
            . 'Generate exactly ' . $total . ' quiz questions aligned with those topics: ' . $mix . ".\n"
            . "Distribute questions across the listed topics. Each item must tag one listed topic.\n"
            . "Reply with a JSON array only, no other text before or after.\n"
            . "Each item MUST include: \"type\" (\"mcq\", \"true_false\", or \"fill_in\"), \"text\" (question text), \"topic\" (one listed topic).\n"
            . "MCQ items: \"options\" object with keys A,B,C,D and \"correct\" as one letter.\n"
            . "True/false items: \"correct\" as True or False (no options required).\n"
            . "Fill-in items: \"type\" must be \"fill_in\"; \"text\" must include ___ for the blank; \"correct\" must be a plain JSON string answer (not an array, not A-D); do NOT include \"options\".\n";

        if ($includeExplanations) {
            $prompt .= "For MCQ and true/false, include \"explanation_wrong\" and \"explanation_correct\" (brief, pedagogical).\n";
        } else {
            $prompt .= "Do not include explanations.\n";
        }

        $prompt .= 'Example: [{"type":"mcq","text":"A clinic receives... Which action is best?","options":{"A":"...","B":"...","C":"...","D":"..."},"correct":"B","topic":"...","explanation_correct":"...","explanation_wrong":"..."},{"type":"true_false","text":"Given the scenario...","correct":"False","topic":"...","explanation_correct":"...","explanation_wrong":"..."},{"type":"fill_in","text":"After the audit, the team concluded that ___ was the root cause.","correct":"expected answer","topic":"..."}]';

        return $prompt;
    }

    /**
     * Shorter guidelines for fallback / MCQ-only generation paths.
     */
    public static function compactQualityReminder(): string
    {
        return 'Prefer scenario-based, application-focused questions; avoid making simple recall ("Define...", "What is...") the default. ';
    }

    /**
     * MCQ-only prompt (runtime top-up and legacy paths).
     */
    public static function buildMcqOnlyPrompt(string $topicNames, int $count, ?int $batchNumber = null, ?int $batchTotal = null): string
    {
        $batchNote = ($batchNumber !== null && $batchTotal !== null)
            ? "This is batch {$batchNumber} of {$batchTotal}. Generate UNIQUE questions that differ from previous batches. "
            : '';

        return self::designGuidelines() . "\n\n"
            . self::typeAuthoringRules() . "\n\n"
            . 'TOPICS — use ONLY these precise topics; do not add or substitute others: ' . $topicNames . ".\n"
            . "Generate exactly {$count} multiple choice quiz questions (MCQ) that clearly align with these topics.\n"
            . $batchNote
            . "Base each question on information directly relevant to one or more of the listed topics.\n"
            . "For each question provide: question text, 4 options (A,B,C,D), and the correct letter.\n"
            . 'Format as JSON array only: [{"type":"mcq","text":"...","options":{"A":"...","B":"...","C":"...","D":"..."},"correct":"A","topic":"..."}]';
    }

    /**
     * Minimal single-question fallback.
     */
    public static function buildMinimalMcqPrompt(string $topicNames): string
    {
        return self::compactQualityReminder()
            . "Topic: {$topicNames}. Write 1 scenario-based multiple choice question. "
            . 'Reply with ONLY this JSON array (no other text): [{"text":"...","options":{"A":"","B":"","C":"","D":""},"correct":"A"}]';
    }

    /**
     * Simple multi-MCQ fallback when the full mixed-type prompt fails.
     */
    public static function buildSimpleMcqPrompt(string $topicNames, int $count, string $contextPrefix = ''): string
    {
        return $contextPrefix
            . self::compactQualityReminder()
            . "Topics: {$topicNames}. Generate exactly {$count} multiple choice questions. "
            . 'Reply with ONLY a JSON array, no other text. Each item: {"text":"question","options":{"A":"...","B":"...","C":"...","D":"..."},"correct":"A"}. '
            . 'Example: [{"text":"A manager notices declining sales in Q2. What should they analyze first?","options":{"A":"...","B":"...","C":"...","D":"..."},"correct":"B"}]';
    }

    public static function buildFillInOnlyPrompt(string $topicNames, int $count, string $contextPrefix = ''): string
    {
        return $contextPrefix
            . self::compactQualityReminder()
            . "Topics: {$topicNames}.\n"
            . "Generate exactly {$count} fill-in-the-blank question(s).\n"
            . "CRITICAL: each item must be {\"type\":\"fill_in\",\"text\":\"context with ___ blank\",\"correct\":\"expected answer\",\"topic\":\"...\"}.\n"
            . "\"correct\" must be a JSON string. Do NOT use an array. Do NOT include \"options\". Do NOT use A/B/C/D as the answer.\n"
            . 'Reply with ONLY a JSON array.';
    }

    public static function buildTrueFalseOnlyPrompt(string $topicNames, int $count, string $contextPrefix = ''): string
    {
        return $contextPrefix
            . self::compactQualityReminder()
            . "Topics: {$topicNames}.\n"
            . "Generate exactly {$count} scenario-based true/false question(s).\n"
            . 'Each item: {"type":"true_false","text":"...","correct":"True","topic":"..."} or "False". Reply with ONLY a JSON array.';
    }
}
