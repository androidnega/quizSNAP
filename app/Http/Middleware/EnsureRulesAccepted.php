<?php

namespace App\Http\Middleware;

use App\Models\Quiz;
use App\Models\QuizAcceptance;
use App\Models\QuizSession;
use App\Models\Student;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRulesAccepted
{
    /**
     * Ensure the student has accepted quiz rules before accessing login, course, proctoring, or quiz.
     * Session check is used for UX speed; database (quiz_acceptance) is the source of truth for enforcement
     * when both index_number and quiz_id are known (quiz entry).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $queryToken = $request->query('token');
        if (is_string($queryToken) && trim($queryToken) !== '') {
            $activeSession = QuizSession::where('session_token', trim($queryToken))
                ->whereNull('ended_at')
                ->first();
            if ($activeSession) {
                session([
                    'quiz_session_token' => $activeSession->session_token,
                    'quiz_id' => $activeSession->quiz_id,
                    'index_number' => $activeSession->student_index,
                    'rules_accepted' => true,
                ]);

                return $next($request);
            }
        }

        $sessionToken = session('quiz_session_token');
        if (is_string($sessionToken) && $sessionToken !== '') {
            $activeSession = QuizSession::where('session_token', $sessionToken)
                ->whereNull('ended_at')
                ->first();
            if ($activeSession) {
                session([
                    'quiz_id' => $activeSession->quiz_id,
                    'index_number' => $activeSession->student_index,
                    'rules_accepted' => true,
                ]);

                return $next($request);
            }
        }

        $quizId = $this->resolveQuizId($request);
        $indexNumber = $this->normalizedIndex(session('student_index') ?? session('index_number'));
        if ($indexNumber === null && session('student_id')) {
            $indexNumber = $this->normalizedIndex(Student::find(session('student_id'))?->index_number);
        }
        if ($quizId !== null && $indexNumber !== null) {
            if (! $this->hasAcceptedRules((int) $quizId, $indexNumber)) {
                $quiz = Quiz::where('id', $quizId)->first();
                if ($quiz && $quiz->link_token) {
                    return redirect()->route('student.rules.show.quiz', ['token' => $quiz->link_token])
                        ->with('error', 'Error');
                }

                return redirect()->route('student.rules.show')
                    ->with('error', 'Error');
            }

            session([
                'rules_accepted' => true,
                'index_number' => $indexNumber,
                'quiz_id' => (int) $quizId,
            ]);

            return $next($request);
        }

        // Fast path: session only when we cannot yet check DB (no index or no quiz)
        if (session('rules_accepted')) {
            return $next($request);
        }

        if ($quizId) {
            $quiz = Quiz::where('id', $quizId)->first();
            if ($quiz && $quiz->link_token) {
                return redirect()->route('student.rules.show.quiz', ['token' => $quiz->link_token])
                    ->with('error', 'Error');
            }
        }

        return redirect()->route('student.rules.show')
            ->with('error', 'Error');
    }

    /**
     * Get quiz ID from request (query string or from session token).
     */
    private function resolveQuizId(Request $request): ?int
    {
        $quizFromQuery = $request->query('quiz');
        if ($quizFromQuery !== null && $quizFromQuery !== '') {
            return (int) $quizFromQuery;
        }

        $quizFromSession = session('quiz_id') ?? session('quiz_id_for_login');
        if ($quizFromSession !== null && $quizFromSession !== '') {
            return (int) $quizFromSession;
        }

        $token = $request->route('token') ?? session('quiz_session_token');
        if ($token) {
            $session = QuizSession::where('session_token', $token)->first();
            if ($session) {
                return (int) $session->quiz_id;
            }
        }

        $quizToken = session('quiz_link_token');
        if ($quizToken) {
            $quiz = Quiz::where('link_token', $quizToken)->first();
            if ($quiz) {
                return (int) $quiz->id;
            }
        }

        return null;
    }

    private function normalizedIndex(mixed $indexNumber): ?string
    {
        if (! is_string($indexNumber) && ! is_numeric($indexNumber)) {
            return null;
        }

        $normalized = strtoupper(trim((string) $indexNumber));

        return $normalized !== '' ? $normalized : null;
    }

    private function hasAcceptedRules(int $quizId, string $indexNumber): bool
    {
        return QuizAcceptance::query()
            ->where('quiz_id', $quizId)
            ->whereRaw('UPPER(TRIM(index_number)) = ?', [$indexNumber])
            ->exists();
    }
}
