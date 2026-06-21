<?php

namespace App\Services;

use App\Models\ClassGroupStudent;
use App\Models\Quiz;
use App\Models\QuizAcceptance;
use App\Models\QuizSession;
use App\Models\Student;

final class QuizLinkService
{
    public function findByToken(?string $token): ?Quiz
    {
        $token = trim((string) $token);
        if ($token === '' || ! preg_match('#^[a-zA-Z0-9_-]{8,64}$#', $token)) {
            return null;
        }

        $quiz = Quiz::with(['course', 'classGroup'])->where('link_token', $token)->first();
        if (! $quiz) {
            return null;
        }

        // Ignore withCount() from other queries — always count questions fresh for link access.
        $quiz->offsetUnset('questions_count');

        return $quiz;
    }

    public function extractToken(string $input): ?string
    {
        $input = trim($input);
        if ($input === '') {
            return null;
        }
        if (preg_match('#/t/([a-zA-Z0-9_-]+)#', $input, $matches)) {
            return $matches[1];
        }
        if (preg_match('#^([a-zA-Z0-9_-]{8,64})$#', $input, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public function resolveStudent(?int $studentId = null): ?Student
    {
        $studentId = $studentId ?? session('student_id');

        return $studentId ? Student::find($studentId) : null;
    }

    public function normalizedIndex(?Student $student = null): ?string
    {
        if ($student && $student->index_number) {
            return strtoupper(trim($student->index_number));
        }

        $sessionIndex = session('index_number') ?? session('student_index');

        return $sessionIndex ? strtoupper(trim((string) $sessionIndex)) : null;
    }

    /**
     * Where to send someone who opened a public quiz link (landing, start-quiz form, etc.).
     *
     * @return array{route: string, params: array<string, string>}|null
     */
    public function publicLinkDestination(string $token, ?string $indexNumber = null): ?array
    {
        $quiz = $this->findByToken($token);
        if (! $quiz || ! $this->isLinkOpen($quiz, $indexNumber)) {
            return null;
        }

        if ($quiz->starts_at && $quiz->starts_at->isFuture()) {
            return ['route' => 'student.quiz-will-start', 'params' => ['token' => $token]];
        }

        return ['route' => 'student.rules.show.quiz', 'params' => ['token' => $token]];
    }

    public function isLinkOpen(Quiz $quiz, ?string $indexNumber = null): bool
    {
        return $quiz->isAvailableForStudent(false, $indexNumber);
    }

    public function latestSession(Quiz $quiz, ?string $indexNumber): ?QuizSession
    {
        if (! $indexNumber) {
            return null;
        }

        return $quiz->sessions()
            ->whereRaw('UPPER(TRIM(student_index)) = ?', [$indexNumber])
            ->latest('id')
            ->first();
    }

    public function hasAcceptedRules(Quiz $quiz, ?string $indexNumber): bool
    {
        if (! $indexNumber) {
            return false;
        }

        return QuizAcceptance::where('quiz_id', $quiz->id)
            ->whereRaw('UPPER(TRIM(index_number)) = ?', [$indexNumber])
            ->exists();
    }

    public function isRegisteredForQuiz(Quiz $quiz, Student $student): bool
    {
        $indexNumber = strtoupper(trim($student->index_number ?? ''));
        if ($indexNumber === '') {
            return false;
        }

        if ($quiz->class_group_id) {
            if (ClassGroupStudent::existsInClassGroup((int) $quiz->class_group_id, $indexNumber)) {
                return true;
            }
        }

        if ($quiz->academic_class_id && $quiz->academic_year_id) {
            return (int) $student->academic_class_id === (int) $quiz->academic_class_id
                && (int) $student->academic_year_id === (int) $quiz->academic_year_id
                && (! $quiz->level_id || (int) $student->level_id === (int) $quiz->level_id)
                && (! $quiz->semester_id || (int) $student->semester_id === (int) $quiz->semester_id);
        }

        return false;
    }

    public function rememberQuizContext(Quiz $quiz, bool $rulesAccepted = false): void
    {
        $payload = [
            'quiz_id_for_login' => $quiz->id,
            'quiz_link_token' => $quiz->link_token,
        ];

        if ($rulesAccepted) {
            $payload['rules_accepted'] = true;
        }

        session($payload);
    }

    /**
     * Clear stale quiz-link keys without dropping an active in-progress attempt token.
     */
    public function forgetStaleQuizLinkContext(): void
    {
        session()->forget([
            'quiz_id',
            'quiz_id_for_login',
            'quiz_link_token',
            'index_number',
            'rules_accepted',
            'eligible_courses',
        ]);
    }

    /**
     * Clear all quiz-entry session keys (including active attempt token).
     */
    public function forgetQuizContext(): void
    {
        $this->forgetStaleQuizLinkContext();
        session()->forget(['quiz_session_token']);
    }

    /**
     * Public URL to continue an open quiz attempt (token in query for reliable resume).
     */
    public function resumeUrl(QuizSession $session): string
    {
        if ($session->ended_at !== null) {
            return route('student.result', ['token' => $session->session_token]);
        }

        $route = $session->start_time !== null
            ? route('student.quiz.show')
            : route('student.quiz.ready');

        return $route.'?token='.urlencode((string) $session->session_token);
    }

    /**
     * Store attempt context in session and return the resume URL.
     */
    public function resumeRoute(QuizSession $session): string
    {
        if ($session->ended_at !== null) {
            return route('student.result', ['token' => $session->session_token]);
        }

        session([
            'quiz_id' => $session->quiz_id,
            'index_number' => $session->student_index,
            'quiz_session_token' => $session->session_token,
            'rules_accepted' => true,
        ]);

        return $this->resumeUrl($session);
    }

    public function studentOwnsSession(QuizSession $session, ?Student $student = null): bool
    {
        $student = $student ?? $this->resolveStudent();
        if (! $student || ! $student->index_number) {
            return false;
        }

        return strtoupper(trim((string) $student->index_number))
            === strtoupper(trim((string) $session->student_index));
    }

    /**
     * Resolve an active attempt from session storage or ?token= query (same pattern as results).
     */
    public function resolveActiveSession(?string $token): ?QuizSession
    {
        if (! is_string($token) || trim($token) === '') {
            return null;
        }

        return QuizSession::query()
            ->where('session_token', trim($token))
            ->whereNull('ended_at')
            ->first();
    }

    public function syncActiveSession(QuizSession $session): void
    {
        session([
            'quiz_id' => $session->quiz_id,
            'index_number' => $session->student_index,
            'quiz_session_token' => $session->session_token,
            'rules_accepted' => true,
        ]);
    }
}
