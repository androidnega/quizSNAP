<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAcceptance;
use App\Models\QuizSession;
use App\Models\Setting;
use App\Models\Student;
use App\Services\QuizLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuizRulesController extends Controller
{
    public function __construct(
        private readonly QuizLinkService $quizLinks,
    ) {}

    /**
     * Show quiz rules & warning screen. Optional token (link_token) for context; generic rules when none.
     * When quiz link is invalid or expired, show link-expired view.
     * When quiz has a future starts_at, redirect to countdown page.
     */
    public function show(Request $request): View|RedirectResponse
    {
        $token = $request->route('token') ?? $request->query('t') ?? $request->query('token');
        $quiz = null;

        if ($token) {
            $quiz = $this->quizLinks->findByToken($token);
            $student = $this->quizLinks->resolveStudent();
            $indexNumber = $this->quizLinks->normalizedIndex($student);

            if (! $quiz || ! $this->quizLinks->isLinkOpen($quiz, $indexNumber)) {
                return view('student.link-expired');
            }

            if ($redirect = $this->redirectExistingStudentAttempt($quiz, $student, $indexNumber)) {
                return $redirect;
            }

            if ($quiz->starts_at && $quiz->starts_at->isFuture()) {
                return redirect()->route('student.quiz-will-start', ['token' => $quiz->link_token]);
            }

            $this->quizLinks->rememberQuizContext($quiz);

            if (! $request->route('token')) {
                return redirect()->route('student.rules.show.quiz', ['token' => $quiz->link_token]);
            }
        }

        $allowedDevices = $quiz ? (function () use ($quiz) {
            $quiz->loadMissing('classGroup');

            return $quiz->getEffectiveAllowedDevices();
        })() : 'desktop';
        $mobileAllowed = in_array($allowedDevices, ['mobile', 'both'], true);

        return view('student.quiz-rules', compact('quiz', 'mobileAllowed'));
    }

    /**
     * Show "Quiz will start at X" countdown page when quiz has a future starts_at.
     * When countdown reaches zero, student can proceed to rules.
     */
    public function quizWillStart(Request $request): View|RedirectResponse
    {
        $token = $request->route('token');
        $quiz = $this->quizLinks->findByToken($token);
        $student = $this->quizLinks->resolveStudent();
        $indexNumber = $this->quizLinks->normalizedIndex($student);

        if (! $quiz || ! $this->quizLinks->isLinkOpen($quiz, $indexNumber)) {
            return view('student.link-expired');
        }

        if ($redirect = $this->redirectExistingStudentAttempt($quiz, $student, $indexNumber)) {
            return $redirect;
        }

        if (! $quiz->starts_at || $quiz->starts_at->isPast()) {
            return redirect()->route('student.rules.show.quiz', ['token' => $quiz->link_token]);
        }

        $this->quizLinks->rememberQuizContext($quiz);

        return view('student.quiz-will-start', compact('quiz'));
    }

    /**
     * Store acceptance (dos & don'ts accepted).
     * If student is already logged in, skip login form and go directly to proctoring.
     * Otherwise, store quiz_id in session so login validates index against this quiz's class group.
     */
    public function accept(Request $request): JsonResponse
    {
        $quizId = $request->input('quiz_id') ?: session('quiz_id_for_login');
        if (! $quizId && session('quiz_link_token')) {
            $quizId = Quiz::where('link_token', session('quiz_link_token'))->value('id');
        }
        $sessionData = ['rules_accepted' => true];

        if (! $quizId) {
            return response()->json([
                'success' => false,
                'message' => 'Quiz session was not found. Please reopen the quiz link and try again.',
            ], 422);
        }

        $quiz = Quiz::with('classGroup')->find($quizId);
        $student = $this->quizLinks->resolveStudent();
        $indexNumber = $this->quizLinks->normalizedIndex($student);

        if (! $quiz || ! $quiz->isAvailableForStudent(false, $indexNumber)) {
            return response()->json([
                'success' => false,
                'message' => 'This quiz is not available right now. Please reopen the quiz link.',
            ], 422);
        }
        if ($quiz->starts_at && $quiz->starts_at->isFuture()) {
            return response()->json([
                'success' => true,
                'redirect' => route('student.quiz-will-start', ['token' => $quiz->link_token]),
            ]);
        }

        if ($student && $student->index_number) {
            $allowed = $this->quizLinks->isRegisteredForQuiz($quiz, $student);
            if ($allowed) {
                $existingSession = QuizSession::where('quiz_id', $quiz->id)
                    ->whereRaw('UPPER(TRIM(student_index)) = ?', [strtoupper($student->index_number)])
                    ->latest('id')
                    ->first();

                if ($existingSession && $existingSession->ended_at !== null) {
                    session(['quiz_session_token' => $existingSession->session_token]);

                    return response()->json([
                        'success' => true,
                        'redirect' => route('student.result', ['token' => $existingSession->session_token]),
                    ]);
                }

                if ($existingSession && $this->isIpDeviceRestrictionEnabled()) {
                    $ip = $request->ip();
                    $ipUsedByOther = QuizSession::where('quiz_id', $quiz->id)
                        ->where('ip_address', $ip)
                        ->whereRaw("ip_address NOT LIKE 'reset-%'")
                        ->whereRaw('UPPER(TRIM(student_index)) != ?', [strtoupper($student->index_number)])
                        ->exists();

                    if ($ipUsedByOther) {
                        return response()->json([
                            'success' => false,
                            'message' => 'This IP address has already been used for this quiz by another student. Access denied.',
                        ], 422);
                    }
                }

                QuizAcceptance::updateOrCreate(
                    [
                        'quiz_id' => $quiz->id,
                        'index_number' => $student->index_number,
                    ],
                    [
                        'ip_address' => $request->ip(),
                        'accepted_at' => now(),
                    ]
                );

                session([
                    'quiz_id' => $quiz->id,
                    'index_number' => $student->index_number,
                    'rules_accepted' => true,
                ]);
                if ($existingSession) {
                    session(['quiz_session_token' => $existingSession->session_token]);
                }
                session()->forget('eligible_courses');

                return response()->json([
                    'success' => true,
                    'redirect' => $existingSession
                        ? ($existingSession->start_time !== null ? route('student.quiz.show') : route('student.quiz.ready'))
                        : route('student.proctoring.capture'),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Your index number is not registered for this quiz class group.',
            ], 422);
        }

        $pendingIndex = $request->input('index_number') ?? session('student_index') ?? 'pending';
        QuizAcceptance::create([
            'quiz_id' => $quiz->id,
            'index_number' => $pendingIndex,
            'ip_address' => $request->ip(),
            'accepted_at' => now(),
        ]);
        $sessionData['quiz_id_for_login'] = $quiz->id;
        $sessionData['quiz_link_token'] = $quiz->link_token;

        session($sessionData);
        session()->forget('eligible_courses');

        return response()->json([
            'success' => true,
            'redirect' => route('student.login.form'),
        ]);
    }

    private function redirectExistingStudentAttempt(Quiz $quiz, ?Student $student, ?string $indexNumber): ?RedirectResponse
    {
        if (! $student || ! $indexNumber || ! $this->quizLinks->isRegisteredForQuiz($quiz, $student)) {
            return null;
        }

        $session = $this->quizLinks->latestSession($quiz, $indexNumber);
        if ($session) {
            $this->quizLinks->rememberQuizContext($quiz, true);

            return redirect()->to($this->quizLinks->resumeRoute($session));
        }

        if ($this->quizLinks->hasAcceptedRules($quiz, $indexNumber)) {
            $this->quizLinks->rememberQuizContext($quiz, true);
            session([
                'quiz_id' => $quiz->id,
                'index_number' => $student->index_number,
            ]);

            return redirect()->route('student.proctoring.capture');
        }

        return null;
    }

    private function isIpDeviceRestrictionEnabled(): bool
    {
        return Setting::getValue(Setting::KEY_DISABLE_IP_DEVICE_RESTRICTIONS, '0') !== '1';
    }
}
