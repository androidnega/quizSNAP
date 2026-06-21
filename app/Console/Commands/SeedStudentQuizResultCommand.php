<?php

namespace App\Console\Commands;

use App\Models\Answer;
use App\Models\ClassGroupStudent;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\QuizViolation;
use App\Models\Result;
use App\Models\Student;
use App\Services\QuestionAssignmentService;
use App\Services\QuizLinkService;
use App\Support\QuestionTypes;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedStudentQuizResultCommand extends Command
{
    protected $signature = 'quiz:seed-student-result
        {index : Student index number, e.g. BC/ITS/24/023}
        {--quiz-title=PRINCIPLES OF MANAGEMENT QUIZ JUNE 2026 : Quiz title to match}
        {--correct=17 : Number of correct answers}
        {--total=20 : Total questions for the attempt}
        {--head-turns=3 : Number of head_turn violations to record}
        {--student-name= : Optional roster name when adding to class group}
        {--force : Replace answers, violations, and result on an existing session}';

    protected $description = 'Create or update a completed quiz session with realistic answers, score, and violations.';

    public function handle(QuestionAssignmentService $assignmentService, QuizLinkService $quizLinks): int
    {
        $index = strtoupper(trim((string) $this->argument('index')));
        $quizTitle = trim((string) $this->option('quiz-title'));
        $targetCorrect = max(0, (int) $this->option('correct'));
        $targetTotal = max(1, (int) $this->option('total'));
        $headTurnCount = max(0, (int) $this->option('head-turns'));
        $force = (bool) $this->option('force');

        if ($index === '') {
            $this->error('Index number is required.');

            return Command::FAILURE;
        }

        $quiz = Quiz::query()
            ->where(function ($query) use ($quizTitle): void {
                $query->whereRaw('UPPER(TRIM(title)) = ?', [strtoupper($quizTitle)])
                    ->orWhere('title', 'like', '%'.$quizTitle.'%');
            })
            ->orderByRaw('CASE WHEN UPPER(TRIM(title)) = ? THEN 0 ELSE 1 END', [strtoupper($quizTitle)])
            ->first();

        if (! $quiz) {
            $this->error('Quiz not found: '.$quizTitle);

            return Command::FAILURE;
        }

        $studentName = trim((string) $this->option('student-name'));
        if ($studentName === '') {
            $studentName = Student::findByIndex($index)?->student_name
                ?? ClassGroupStudent::findByIndexNumber($index)?->student_name
                ?? 'Student '.$index;
        }

        if ($quiz->class_group_id) {
            ClassGroupStudent::query()->updateOrCreate(
                [
                    'class_group_id' => $quiz->class_group_id,
                    'index_number' => $index,
                ],
                [
                    'student_name' => $studentName,
                ]
            );
            $this->info('Ensured roster entry for '.$index.' in class group '.$quiz->class_group_id.'.');
        }

        $quizLinks->recordRulesAcceptance($quiz, $index, '127.0.0.1');

        $session = QuizSession::query()
            ->where('quiz_id', $quiz->id)
            ->whereRaw('UPPER(TRIM(student_index)) = ?', [$index])
            ->orderByDesc('id')
            ->first();

        if ($session && $session->ended_at && ! $force) {
            $this->warn('Session #'.$session->id.' already completed. Re-run with --force to replace answers/score/violations.');

            return Command::FAILURE;
        }

        $sessionId = null;

        DB::transaction(function () use (
            $assignmentService,
            $quiz,
            $index,
            $session,
            $targetCorrect,
            $targetTotal,
            $headTurnCount,
            &$sessionId
        ): void {
            if (! $session) {
                $assignment = $assignmentService->assignQuestions($quiz);
                $assignedIds = $assignment['question_ids'] ?? [];
                if (count($assignedIds) < $targetTotal) {
                    throw new \RuntimeException('Quiz does not have enough questions in the pool (need '.$targetTotal.').');
                }

                $session = QuizSession::create([
                    'quiz_id' => $quiz->id,
                    'student_index' => $index,
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'QuizSnap/seed-student-result',
                    'device_type' => 'desktop',
                    'device_name' => 'Seeded session',
                    'start_time' => now()->subMinutes($quiz->duration_minutes ?: 45),
                    'ended_at' => now()->subMinutes(2),
                    'last_heartbeat_at' => now()->subMinutes(2),
                    'camera_verified' => true,
                    'camera_started_at' => now()->subMinutes(($quiz->duration_minutes ?: 45) + 5),
                    'assigned_question_ids' => array_slice($assignedIds, 0, $targetTotal),
                    'assigned_correct_answers' => array_intersect_key(
                        $assignment['correct_answers'] ?? [],
                        array_flip(array_slice($assignedIds, 0, $targetTotal))
                    ),
                    'shuffled_question_options' => array_intersect_key(
                        $assignment['shuffled_options'] ?? [],
                        array_flip(array_slice($assignedIds, 0, $targetTotal))
                    ),
                    'session_token' => QuizSession::generateToken(),
                    'minor_violations' => $headTurnCount,
                    'major_violations' => 0,
                    'auto_submitted' => false,
                    'submission_reason' => null,
                ]);
            } else {
                $session->update([
                    'student_index' => $index,
                    'start_time' => $session->start_time ?? now()->subMinutes($quiz->duration_minutes ?: 45),
                    'ended_at' => $session->ended_at ?? now()->subMinutes(2),
                    'camera_verified' => true,
                    'minor_violations' => $headTurnCount,
                    'major_violations' => 0,
                    'auto_submitted' => false,
                    'submission_reason' => null,
                ]);
            }

            $lockedIds = array_values(array_map('intval', (array) ($session->assigned_question_ids ?? [])));
            if (count($lockedIds) !== $targetTotal) {
                $assignment = $assignmentService->assignQuestions($quiz);
                $assignedIds = array_slice($assignment['question_ids'] ?? [], 0, $targetTotal);
                if (count($assignedIds) < $targetTotal) {
                    throw new \RuntimeException('Could not assign '.$targetTotal.' questions for this quiz.');
                }
                $session->update([
                    'assigned_question_ids' => $assignedIds,
                    'assigned_correct_answers' => array_intersect_key($assignment['correct_answers'] ?? [], array_flip($assignedIds)),
                    'shuffled_question_options' => array_intersect_key($assignment['shuffled_options'] ?? [], array_flip($assignedIds)),
                ]);
                $session->refresh();
                $lockedIds = $assignedIds;
            }

            $correctSnapshot = (array) ($session->assigned_correct_answers ?? []);
            $shuffledOptions = (array) ($session->shuffled_question_options ?? []);
            $questionsById = Question::whereIn('id', $lockedIds)->get()->keyBy('id');

            Answer::where('quiz_session_id', $session->id)->delete();
            QuizViolation::where('quiz_session_id', $session->id)->delete();

            $correctCount = 0;
            $answerTime = ($session->start_time ?? now()->subHour())->copy();

            foreach ($lockedIds as $offset => $questionId) {
                $question = $questionsById->get($questionId);
                $correctAnswer = (string) ($correctSnapshot[$questionId] ?? $correctSnapshot[(string) $questionId] ?? '');
                $shouldBeCorrect = $offset < $targetCorrect;
                $studentAnswer = $shouldBeCorrect
                    ? $correctAnswer
                    : $this->wrongAnswerForQuestion($question, $correctAnswer, $shuffledOptions[$questionId] ?? $shuffledOptions[(string) $questionId] ?? []);

                if ($shouldBeCorrect && trim($studentAnswer) !== '') {
                    $correctCount++;
                }

                $answerTime = $answerTime->copy()->addSeconds(random_int(45, 120));
                Answer::create([
                    'quiz_session_id' => $session->id,
                    'question_id' => $questionId,
                    'student_answer' => $studentAnswer,
                    'answered_at' => $answerTime,
                ]);
            }

            $directions = ['left', 'right', 'left'];
            $violationTime = ($session->start_time ?? now()->subHour())->copy()->addMinutes(3);
            for ($i = 0; $i < $headTurnCount; $i++) {
                $direction = $directions[$i % count($directions)];
                $violationTime = $violationTime->copy()->addMinutes(4 + $i);
                QuizViolation::create([
                    'quiz_session_id' => $session->id,
                    'type' => 'head_turn',
                    'severity' => QuizViolation::severityForType('head_turn'),
                    'metadata' => json_encode([
                        'direction' => $direction,
                        'head_turn_count' => $i + 1,
                        'head_turn_limit' => 10,
                        'logged_at' => $violationTime->toIso8601String(),
                        'seeded' => true,
                    ]),
                    'occurred_at' => $violationTime,
                ]);
            }

            $score = round(100 * $correctCount / max(1, count($lockedIds)), 2);
            Result::updateOrCreate(
                ['quiz_session_id' => $session->id],
                [
                    'score' => min($score, 100),
                    'total_questions' => count($lockedIds),
                    'correct_count' => $correctCount,
                    'violations_count' => $headTurnCount,
                    'submitted_at' => $session->ended_at ?? now(),
                ]
            );

            $sessionId = $session->id;
        });

        $session = QuizSession::with('result')->findOrFail($sessionId);
        $this->info('Session #'.$session->id.' ready for '.$index.' on quiz #'.$quiz->id.' ('.$quiz->title.')');
        $this->line('Score: '.$session->result?->correct_count.'/'.$session->result?->total_questions.' ('.round((float) $session->result?->score, 0).'%)');
        $this->line('Violations: '.$session->violations()->where('type', 'head_turn')->count().' head_turn');

        return Command::SUCCESS;
    }

    /**
     * @param  array<int, array{key?: string, text?: string}|string>  $options
     */
    private function wrongAnswerForQuestion(?Question $question, string $correctAnswer, array $options): string
    {
        $type = QuestionTypes::normalize((string) ($question?->type ?? 'mcq'));

        if ($type === QuestionTypes::TRUE_FALSE) {
            $upper = strtoupper(trim($correctAnswer));

            return match ($upper) {
                'TRUE', 'T', 'A' => 'B',
                'FALSE', 'F', 'B' => 'A',
                default => $upper === 'TRUE' ? 'FALSE' : 'TRUE',
            };
        }

        if ($type === QuestionTypes::FILL_IN) {
            return trim($correctAnswer).'x';
        }

        foreach ($options as $opt) {
            $key = is_array($opt) ? (string) ($opt['key'] ?? '') : (string) $opt;
            if ($key !== '' && strtoupper($key) !== strtoupper(trim($correctAnswer))) {
                return $key;
            }
        }

        return strtoupper(trim($correctAnswer)) === 'A' ? 'B' : 'A';
    }
}
