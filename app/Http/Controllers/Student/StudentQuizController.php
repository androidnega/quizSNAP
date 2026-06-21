<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Events\DataUpdated;
use App\Jobs\GenerateWrongAnswerExplanationsJob;
use App\Jobs\SendQuizResultReadyNotification;
use App\Services\StudentNotificationService;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\QuizViolation;
use App\Models\Result;
use App\Models\Setting;
use App\Models\Student;
use App\Support\UserFriendlyMessages;
use App\Services\AiQuestionService;
use App\Services\QuizConcurrencyService;
use App\Services\QuizLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StudentQuizController extends Controller
{
    private const MAX_QUIZ_VIOLATION_CAPTURES = 5;
    private const NORMAL_VIOLATION_LIMIT = 10;
    private const HEAD_DIRECTION_LIMIT = 12;

    /** @var array{tab_switch_limit: int, out_of_frame_seconds: int, multiple_faces_seconds: int}|null */
    private ?array $proctoringThresholds = null;

    public function __construct(
        private readonly QuizConcurrencyService $concurrency,
        private readonly QuizLinkService $quizLinks,
    ) {}

    /** @var array<string, bool>|null */
    private ?array $proctoringFlags = null;

    /**
     * @return array<string, bool>
     */
    private function proctoringFlags(): array
    {
        if ($this->proctoringFlags === null) {
            $this->proctoringFlags = Setting::getProctoringFlags();
        }

        return $this->proctoringFlags;
    }

    /**
     * @return array{tab_switch_limit: int, out_of_frame_seconds: int, multiple_faces_seconds: int}
     */
    private function proctoringThresholds(): array
    {
        if ($this->proctoringThresholds === null) {
            $this->proctoringThresholds = Setting::getProctoringThresholds();
        }

        return $this->proctoringThresholds;
    }

    /**
     * @param  array<string, bool>  $flags
     */
    private function isFullscreenEnforcementEnabled(array $flags): bool
    {
        return ($flags[Setting::KEY_PROCTORING_CAMERA_REQUIRED] ?? true)
            || ($flags[Setting::KEY_PROCTORING_FACE_MONITOR] ?? true)
            || ($flags[Setting::KEY_PROCTORING_TAB_SWITCH] ?? true);
    }

    private function broadcastDataUpdatedSafe(string $type): void
    {
        try {
            broadcast(new DataUpdated($type))->toOthers();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function syncQuizSessionContext(QuizSession $session): void
    {
        $this->quizLinks->syncActiveSession($session);
    }

    private function resolveQuizSessionToken(Request $request): ?string
    {
        $token = session('quiz_session_token') ?? $request->query('token');

        return is_string($token) && trim($token) !== '' ? trim($token) : null;
    }

    /**
     * @return QuizSession|RedirectResponse
     */
    private function requireActiveQuizSession(Request $request)
    {
        $token = $this->resolveQuizSessionToken($request);
        if (! $token) {
            return redirect()->route('student.landing')->with('error', UserFriendlyMessages::GENERIC);
        }

        $session = $this->quizLinks->resolveActiveSession($token);
        if (! $session) {
            return redirect()->route('student.landing')->with('error', UserFriendlyMessages::GENERIC);
        }

        $this->syncQuizSessionContext($session);

        return $session;
    }

    private function enforceIpForQuizSession(Request $request, QuizSession $session): ?RedirectResponse
    {
        if (! $this->isIpDeviceRestrictionEnabled() || $session->ip_address === $request->ip()) {
            return null;
        }

        if ($this->quizLinks->studentOwnsSession($session) && $session->start_time !== null) {
            if (! str_starts_with((string) $session->ip_address, 'reset-')) {
                $newIp = $request->ip();
                $ipTakenByOther = QuizSession::query()
                    ->where('quiz_id', $session->quiz_id)
                    ->where('ip_address', $newIp)
                    ->where('id', '!=', $session->id)
                    ->whereNull('ended_at')
                    ->whereRaw("ip_address NOT LIKE 'reset-%'")
                    ->exists();

                if (! $ipTakenByOther) {
                    try {
                        $session->update(['ip_address' => $newIp]);
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }
            }

            return null;
        }

        QuizViolation::create([
            'quiz_session_id' => $session->id,
            'type' => 'multiple_ip',
            'severity' => QuizViolation::severityForType('multiple_ip'),
            'metadata' => json_encode(['expected' => $session->ip_address, 'got' => $request->ip()]),
            'occurred_at' => now(),
        ]);

        return redirect()->route('student.landing')->with('error', UserFriendlyMessages::GENERIC);
    }
    /**
     * System readiness screen after pre-quiz face capture.
     */
    public function ready(Request $request): View|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
    {
        $resolved = $this->requireActiveQuizSession($request);
        if ($resolved instanceof RedirectResponse) {
            return $resolved;
        }
        $session = $resolved;
        $session->loadMissing(['quiz.course', 'quiz.classGroup']);
        if ($session->ended_at) {
            return redirect()->to($this->quizCompleteUrl());
        }
        if ($session->start_time !== null) {
            return redirect()->to($this->quizLinks->resumeUrl($session));
        }
        $questionCount = is_array($session->assigned_question_ids)
            ? count($session->assigned_question_ids)
            : 0;
        $session->quiz->loadMissing('classGroup');
        $allowedDevices = $session->quiz->getEffectiveAllowedDevices();
        request()->attributes->set(
            'quizAllowsMobile',
            in_array($allowedDevices, [Quiz::ALLOWED_DEVICES_BOTH, Quiz::ALLOWED_DEVICES_MOBILE], true)
        );

        return response()
            ->view('student.quiz-ready', [
                'session' => $session,
                'courseName' => $session->quiz->course?->name ?? $session->quiz->title ?? 'Quiz',
                'durationMinutes' => (int) ($session->quiz->duration_minutes ?? 0),
                'questionCount' => $questionCount,
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    /**
     * Handle mistaken GET to /quiz/session/start (Safari, WhatsApp in-app browser, bookmarks, prefetch).
     */
    public function startSessionRedirect(Request $request): RedirectResponse
    {
        $resolved = $this->requireActiveQuizSession($request);
        if ($resolved instanceof RedirectResponse) {
            return $resolved;
        }
        $session = $resolved;
        if ($session->ended_at) {
            return redirect()->to($this->quizCompleteUrl());
        }
        if ($session->start_time !== null) {
            return redirect()->to($this->quizLinks->resumeUrl($session));
        }

        return redirect()->to($this->quizLinks->resumeUrl($session));
    }

    /**
     * Start quiz session with camera verification.
     * Marks camera_verified = true and camera_started_at = now().
     */
    public function startSession(Request $request): JsonResponse|RedirectResponse
    {
        $resolved = $this->requireActiveQuizSession($request);
        if ($resolved instanceof RedirectResponse) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'No active quiz session.'], 401);
            }

            return $resolved;
        }
        $session = $resolved;
        if ($session->ended_at) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Session already ended.'], 403);
            }
            return redirect()->to($this->quizCompleteUrl());
        }
        if ($session->start_time !== null) {
            $redirect = $this->quizLinks->resumeUrl($session);
            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'redirect' => $redirect]);
            }
            return redirect()->to($redirect);
        }
        $session->update([
            'camera_verified' => true,
            'camera_started_at' => $session->camera_started_at ?? now(),
            'start_time' => now(),
        ]);
        $this->broadcastDataUpdatedSafe('sessions');
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'redirect' => $this->quizLinks->resumeUrl($session),
            ]);
        }
        return redirect()->to($this->quizLinks->resumeUrl($session));
    }

    /**
     * Show quiz interface (StudentQuiz): timer, questions, auto-save.
     * Session token resolved from session (not URL). Timer starts when the student submits quiz-ready.
     * Requires camera_verified = true before allowing quiz to start.
     */
    public function show(Request $request): View|JsonResponse|\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
    {
        $resolved = $this->requireActiveQuizSession($request);
        if ($resolved instanceof RedirectResponse) {
            return $resolved;
        }
        $session = $resolved->load(['quiz', 'quiz.classGroup']);
        $token = (string) $session->session_token;
        if ($session->ended_at) {
            return redirect()->to($this->quizCompleteUrl());
        }
        if ($session->device_type === null && $session->user_agent === null) {
            $ua = $request->userAgent();
            $device = QuizSession::parseUserAgent($ua);
            $session->update([
                'user_agent' => $ua ? substr($ua, 0, 1024) : null,
                'device_type' => $device['device_type'],
                'device_name' => $device['device_name'],
            ]);
        }
        // Enforce pre-capture gate only when camera is required by settings.
        if (! $session->camera_verified && $this->isProctoringCameraRequired()) {
            if (! empty($session->pre_face_image)) {
                $session->update([
                    'camera_verified' => true,
                    'camera_started_at' => $session->camera_started_at ?? now(),
                ]);
            } else {
                return redirect()->to($this->quizLinks->proctoringCaptureUrl((int) $session->quiz_id, $token))
                    ->with('error', UserFriendlyMessages::GENERIC);
            }
        }
        if (!$session->camera_verified && !$this->isProctoringCameraRequired()) {
            $session->update([
                'camera_verified' => true,
                'camera_started_at' => now(),
            ]);
        }
        if ($session->start_time === null) {
            return redirect()->to($this->quizLinks->resumeUrl($session));
        }
        if ($ipRedirect = $this->enforceIpForQuizSession($request, $session)) {
            return $ipRedirect;
        }
        $questionIds = $session->assigned_question_ids ?? [];
        $questions = collect();
        $shuffledOptionsByQuestion = $session->shuffled_question_options ?? [];
        if (!empty($questionIds)) {
            $ids = array_map('intval', $questionIds);
            $questions = Question::whereIn('id', $ids)
                ->select(['id', 'type', 'text', 'options', 'points'])
                ->get();
            $questions = $questions->sortBy(fn ($q) => array_search($q->id, $ids))->values();
        }
        $durationSeconds = $session->quiz->duration_minutes * 60;
        $elapsed = now()->diffInSeconds($session->start_time);
        $remaining = max(0, $durationSeconds - $elapsed);
        if ($session->quiz->ends_at && $session->quiz->ends_at->isPast()) {
            return redirect()->to($this->resultUrlWithToken($token));
        }
        if ($remaining <= 0) {
            if ($session->ended_at === null) {
                $session->update([
                    'auto_submitted' => true,
                    'submission_reason' => 'time_expired',
                ]);
                $this->finalizeQuiz($session->fresh());
            }

            return redirect()->to($this->resultUrlWithToken($token));
        }
        $savedAnswers = $session->decryptedAnswersByQuestionId();
        $totalQuestions = $questions->count();
        $answeredCount = $questions->filter(function ($q) use ($savedAnswers) {
            $a = $savedAnswers[$q->id] ?? '';
            return trim((string) $a) !== '';
        })->count();
        $perPage = $totalQuestions <= 20 ? 10 : 20;
        $totalPages = $totalQuestions > 0 ? (int) ceil($totalQuestions / $perPage) : 1;
        $proctoring = $this->proctoringFlags();
        $proctoringCameraRequired = $proctoring[Setting::KEY_PROCTORING_CAMERA_REQUIRED];
        $proctoringFaceMonitor = $proctoring[Setting::KEY_PROCTORING_FACE_MONITOR];
        $proctoringTabSwitch = $proctoring[Setting::KEY_PROCTORING_TAB_SWITCH];
        $proctoringObjectDetect = $proctoring[Setting::KEY_PROCTORING_OBJECT_DETECT];
        $proctoringBlockRightClick = $proctoring[Setting::KEY_PROCTORING_BLOCK_RIGHT_CLICK];
        $proctoringBlockCopyPaste = $proctoring[Setting::KEY_PROCTORING_BLOCK_COPY_PASTE];
        $fullscreenEnforcement = $this->isFullscreenEnforcementEnabled($proctoring);
        $proctoringThresholds = $this->proctoringThresholds();
        $matchedStudent = Student::findByIndex($session->student_index, ['index_number', 'student_name']);
        $matchedStudentName = $matchedStudent && trim((string) $matchedStudent->student_name) !== ''
            ? trim((string) $matchedStudent->student_name)
            : null;
        $studentNameLinked = $matchedStudentName !== null
            && strtoupper(trim((string) $matchedStudent->index_number)) === strtoupper(trim((string) $session->student_index));
        $violationAggregates = $this->concurrency->violationAggregates($session);
        $outOfFrameCount = (int) ($violationAggregates['by_type']['face_out_of_frame'] ?? 0);
        $headTurnCount = (int) ($violationAggregates['by_type']['head_turn'] ?? 0);
        $normalViolationCount = $outOfFrameCount;

        // Single source of truth: quiz effective allowed devices (class group → quiz → desktop).
        $session->quiz->loadMissing('classGroup');
        $allowedDevices = $session->quiz->getEffectiveAllowedDevices();
        $isMobile = $this->isMobileRequest($request);
        request()->attributes->set(
            'quizAllowsMobile',
            in_array($allowedDevices, [Quiz::ALLOWED_DEVICES_BOTH, Quiz::ALLOWED_DEVICES_MOBILE], true)
        );

        // Mobile-only quiz opened on desktop: show notice to use phone
        if ($allowedDevices === Quiz::ALLOWED_DEVICES_MOBILE && ! $isMobile) {
            return response()
                ->view('student.quiz-mobile-only-notice', ['session' => $session])
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                ->header('Pragma', 'no-cache');
        }

        // Both allowed and user is on mobile: serve mobile blade (one question per page, live feed on top)
        if (($allowedDevices === Quiz::ALLOWED_DEVICES_BOTH || $allowedDevices === Quiz::ALLOWED_DEVICES_MOBILE) && $isMobile) {
            $mobileTotalPages = $totalQuestions > 0 ? $totalQuestions : 1;
            $viewData = [
                'session' => $session,
                'questions' => $questions,
                'shuffledOptionsByQuestion' => $shuffledOptionsByQuestion,
                'savedAnswers' => $savedAnswers,
                'answeredCount' => $answeredCount,
                'durationSeconds' => $durationSeconds,
                'remainingSeconds' => $remaining,
                'perPage' => 1,
                'totalPages' => $mobileTotalPages,
                'proctoringCameraRequired' => $proctoringCameraRequired,
                'proctoringFaceMonitor' => $proctoringFaceMonitor,
                'proctoringTabSwitch' => $proctoringTabSwitch,
                'proctoringObjectDetect' => $proctoringObjectDetect,
                'proctoringBlockRightClick' => $proctoringBlockRightClick,
                'proctoringBlockCopyPaste' => $proctoringBlockCopyPaste,
                'fullscreenEnforcement' => $fullscreenEnforcement,
                'matchedStudentName' => $matchedStudentName,
                'studentNameLinked' => $studentNameLinked,
                'outOfFrameCount' => $outOfFrameCount,
                'headTurnCount' => $headTurnCount,
                'normalViolationCount' => $normalViolationCount,
                'proctoringTabSwitchLimit' => $proctoringThresholds['tab_switch_limit'],
                'proctoringOutOfFrameSeconds' => $proctoringThresholds['out_of_frame_seconds'],
                'proctoringMultipleFacesSeconds' => $proctoringThresholds['multiple_faces_seconds'],
            ];
            return response()
                ->view('student.quiz-mobile', $viewData)
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                ->header('Pragma', 'no-cache');
        }

        return response()
            ->view('student.quiz', [
                'session' => $session,
                'questions' => $questions,
                'shuffledOptionsByQuestion' => $shuffledOptionsByQuestion,
                'savedAnswers' => $savedAnswers,
                'answeredCount' => $answeredCount,
                'durationSeconds' => $durationSeconds,
                'remainingSeconds' => $remaining,
                'perPage' => $perPage,
                'totalPages' => $totalPages,
                'proctoringCameraRequired' => $proctoringCameraRequired,
                'proctoringFaceMonitor' => $proctoringFaceMonitor,
                'proctoringTabSwitch' => $proctoringTabSwitch,
                'proctoringObjectDetect' => $proctoringObjectDetect,
                'proctoringBlockRightClick' => $proctoringBlockRightClick,
                'proctoringBlockCopyPaste' => $proctoringBlockCopyPaste,
                'fullscreenEnforcement' => $fullscreenEnforcement,
                'matchedStudentName' => $matchedStudentName,
                'studentNameLinked' => $studentNameLinked,
                'outOfFrameCount' => $outOfFrameCount,
                'headTurnCount' => $headTurnCount,
                'normalViolationCount' => $normalViolationCount,
                'allowedDevices' => $allowedDevices,
                'proctoringTabSwitchLimit' => $proctoringThresholds['tab_switch_limit'],
                'proctoringOutOfFrameSeconds' => $proctoringThresholds['out_of_frame_seconds'],
                'proctoringMultipleFacesSeconds' => $proctoringThresholds['multiple_faces_seconds'],
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    /**
     * Server time sync for quiz timer: returns server time, session start, and duration so client can correct drift.
     */
    public function timeSync(Request $request): JsonResponse
    {
        $token = session('quiz_session_token');
        if (!$token) {
            return response()->json(['error' => 'No session'], 401);
        }
        $session = QuizSession::with('quiz')->where('session_token', $token)->first();
        if (!$session || $session->ended_at) {
            return response()->json(['error' => 'Session ended or invalid'], 404);
        }
        $start = $session->start_time ? $session->start_time->timestamp : now()->timestamp;
        $durationSeconds = $session->quiz->duration_minutes * 60;
        $serverTime = now()->timestamp;
        $remaining = max(0, $durationSeconds - ($serverTime - $start));
        if ($session->quiz->ends_at && $session->quiz->ends_at->timestamp < $serverTime) {
            $remaining = 0;
        }
        return response()->json([
            'server_time' => $serverTime,
            'start_time' => $start,
            'duration_seconds' => $durationSeconds,
            'remaining_seconds' => (int) $remaining,
        ]);
    }

    /**
     * Auto-save single answer. Session resolved from HttpOnly session only.
     * Rejects if session is auto-submitted.
     */
    public function saveAnswer(Request $request): JsonResponse
    {
        $token = session('quiz_session_token');
        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Session expired.'], 401);
        }
        $request->validate([
            'question_id' => 'required|exists:questions,id',
            'answer' => 'nullable|string',
        ]);
        $session = QuizSession::where('session_token', $token)->firstOrFail();
        if ($session->ended_at) {
            return response()->json(['success' => false, 'message' => 'Quiz ended.'], 403);
        }
        if ($session->auto_submitted) {
            return response()->json(['success' => false, 'message' => 'Quiz was auto-submitted due to violations.'], 403);
        }
        if ($this->isIpDeviceRestrictionEnabled() && $session->ip_address !== $request->ip()) {
            return response()->json(['success' => false], 403);
        }
        Answer::updateOrCreate(
            [
                'quiz_session_id' => $session->id,
                'question_id' => $request->question_id,
            ],
            [
                'student_answer' => $request->answer ?? '',
                'answered_at' => now(),
            ]
        );
        return response()->json(['success' => true]);
    }

    /**
     * Save multiple answers in one request. Session resolved from HttpOnly session only.
     * Rejects if session is auto-submitted.
     */
    public function saveAnswersBatch(Request $request): JsonResponse
    {
        $token = session('quiz_session_token');
        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Session expired.'], 401);
        }
        $request->validate([
            'answers' => 'required|array|max:80',
            'answers.*.question_id' => 'required|exists:questions,id',
            'answers.*.answer' => 'nullable|string',
        ]);
        $session = QuizSession::where('session_token', $token)->firstOrFail();
        if ($session->ended_at) {
            return response()->json(['success' => false, 'message' => 'Quiz ended.'], 403);
        }
        if ($session->auto_submitted) {
            return response()->json(['success' => false, 'message' => 'Quiz was auto-submitted due to violations.'], 403);
        }
        if ($this->isIpDeviceRestrictionEnabled() && $session->ip_address !== $request->ip()) {
            return response()->json(['success' => false], 403);
        }
        $now = now();
        $rows = [];
        foreach ($request->answers as $item) {
            $rows[] = [
                'quiz_session_id' => $session->id,
                'question_id' => (int) $item['question_id'],
                'student_answer' => $this->encryptAnswerValue($item['answer'] ?? ''),
                'answered_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if ($rows !== []) {
            Answer::upsert(
                $rows,
                ['quiz_session_id', 'question_id'],
                ['student_answer', 'answered_at', 'updated_at']
            );
        }

        return response()->json(['success' => true]);
    }

    private function encryptAnswerValue(string $plain): string
    {
        if ($plain === '') {
            return '';
        }

        return Crypt::encryptString($plain);
    }

    /**
     * Capture violation image and store on server.
     * Creates or updates violation record with image URL.
     */
    public function captureViolation(Request $request): JsonResponse
    {
        $token = session('quiz_session_token');
        if (!$token) {
            return response()->json(['success' => false, 'message' => 'No active session.'], 401);
        }

        $request->validate([
            'session_id' => 'required|exists:quiz_sessions,id',
            'violation_type' => 'required|string',
            'image_base64' => 'required|string',
            'metadata' => 'nullable',
        ]);

        $session = QuizSession::where('session_token', $token)->where('id', $request->session_id)->first();
        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Session not found or invalid.'], 404);
        }

        if ($session->ended_at) {
            return response()->json(['success' => false, 'message' => 'Session already ended.'], 403);
        }

        $violationType = (string) $request->violation_type;
        if (!$this->isProctoringTypeEnabled($violationType)) {
            return response()->json([
                'success' => true,
                'captured' => false,
                'ignored_by_setting' => true,
            ]);
        }
        $faceLossTypes = ['no_face_during_quiz', 'face_out_of_frame'];
        $isFaceLossCapture = in_array($violationType, $faceLossTypes, true);
        $metadata = $this->normalizeViolationMetadata($request->input('metadata'));
        if ($violationType === 'face_out_of_frame') {
            $faceCount = isset($metadata['face_count']) ? (int) $metadata['face_count'] : 0;
            $faceCountAtCapture = isset($metadata['face_count_at_capture']) ? (int) $metadata['face_count_at_capture'] : 0;
            if ($faceCount > 0 || $faceCountAtCapture > 0) {
                return response()->json([
                    'success' => true,
                    'captured' => false,
                    'rejected' => true,
                    'message' => 'Out-of-frame evidence requires face count zero.',
                ]);
            }
        }
        // Global cap on stored violation images per session (all types combined),
        // but always allow captures for critical events like phone_detected or multiple faces.
        $capturedCount = $session->violations()->whereNotNull('image_url')->count();
        $isCriticalCapture = in_array($violationType, ['phone_detected', 'multiple_faces_during_quiz', 'multiple_faces_pre_quiz'], true);
        if (! $isCriticalCapture && $capturedCount >= self::MAX_QUIZ_VIOLATION_CAPTURES) {
            return response()->json([
                'success' => true,
                'image_url' => null,
                'captured' => false,
                'limit_reached' => true,
                'max_captures' => self::MAX_QUIZ_VIOLATION_CAPTURES,
            ]);
        }

        $imageUrl = null;
        $data = $request->image_base64;
        $capturedAt = now();

        if (!Str::startsWith($data, 'data:image')) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid capture payload.',
            ], 422);
        }

        try {
            $studentIndex = $session->student_index ?? 'unknown';
            $imageUrl = $this->storeViolationCaptureLocally($session->id, $data, $studentIndex);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload image.',
            ], 500);
        }

        // Create or update violation record
        $severity = QuizViolation::severityForType($violationType);
        $metadata['captured_at'] = $capturedAt->toIso8601String();
        $outOfFrameDuration = isset($metadata['out_of_frame_duration']) || isset($metadata['out_of_frame_duration_ms'])
            ? (int) ($metadata['out_of_frame_duration'] ?? $metadata['out_of_frame_duration_ms'] ?? 0)
            : null;
        $evidenceTimestamp = null;
        if (! empty($metadata['evidence_timestamp'])) {
            try {
                $evidenceTimestamp = \Carbon\Carbon::parse($metadata['evidence_timestamp']);
            } catch (\Throwable $e) {
                $evidenceTimestamp = null;
            }
        }

        $recentViolation = QuizViolation::query()
            ->where('quiz_session_id', $session->id)
            ->where('type', $violationType)
            ->whereNull('image_url')
            ->where('occurred_at', '>=', now()->subSeconds(15))
            ->orderByDesc('occurred_at')
            ->first();

        if ($recentViolation) {
            $existingMeta = $this->normalizeViolationMetadata($recentViolation->metadata);
            $updatePayload = [
                'severity' => $severity,
                'metadata' => json_encode(array_merge($existingMeta, $metadata)),
                'image_url' => $imageUrl,
            ];
            if ($outOfFrameDuration !== null) {
                $updatePayload['out_of_frame_duration'] = $outOfFrameDuration;
            }
            if ($evidenceTimestamp !== null) {
                $updatePayload['evidence_timestamp'] = $evidenceTimestamp;
            }
            $recentViolation->update($updatePayload);
            $savedViolation = $recentViolation;
        } else {
            $createPayload = [
                'quiz_session_id' => $session->id,
                'type' => $violationType,
                'severity' => $severity,
                'metadata' => json_encode($metadata),
                'image_url' => $imageUrl,
                'occurred_at' => $capturedAt,
            ];
            if ($outOfFrameDuration !== null) {
                $createPayload['out_of_frame_duration'] = $outOfFrameDuration;
            }
            if ($evidenceTimestamp !== null) {
                $createPayload['evidence_timestamp'] = $evidenceTimestamp;
            }
            $savedViolation = QuizViolation::create($createPayload);
        }

        // Check if session should be marked as risky
        $this->checkAndMarkRiskySession($session);

        return response()->json([
            'success' => true,
            'image_url' => $imageUrl,
            'captured' => true,
            'violation_id' => $savedViolation?->id,
            'remaining_captures' => max(
                0,
                self::MAX_QUIZ_VIOLATION_CAPTURES - ($capturedCount + 1)
            ),
        ]);
    }

    /**
     * Record violation. Right-click = warn only (no auto-submit).
     * Auto-submit: (1) critical (copy_paste, multiple_ip) on first, or (2) tab switch/blur: first time = 20s delay; second time = immediate.
     */
    public function recordViolation(Request $request): JsonResponse
    {
        $token = session('quiz_session_token');
        if (!$token) {
            return response()->json(['success' => false], 401);
        }
        $request->validate([
            'type' => 'required|string|in:blur,tab_switch,copy_paste,right_click,window_resize,screenshot_attempt,camera_disconnected,no_face,multiple_faces,multiple_faces_pre_quiz,multiple_faces_during_quiz,random_snapshot,phone_detected,external_audio,no_blink,head_turn,brief_face_loss,challenge_failed,static_face_detected,no_face_during_quiz,face_out_of_frame,face_lost_repeatedly,other',
        ]);
        $session = QuizSession::where('session_token', $token)->firstOrFail();
        if ($session->ended_at) {
            return response()->json(['success' => true]);
        }
        if ($session->device_type === null || $session->user_agent === null) {
            $ua = $request->userAgent();
            $device = QuizSession::parseUserAgent($ua);
            $session->update([
                'user_agent' => $ua ? substr($ua, 0, 1024) : null,
                'device_type' => $device['device_type'],
                'device_name' => $device['device_name'],
            ]);
        }
        $type = $request->type;
        if (!$this->isProctoringTypeEnabled($type)) {
            return response()->json([
                'success' => true,
                'auto_submitted' => false,
                'ignored_by_setting' => true,
            ]);
        }
        $severity = QuizViolation::severityForType($type);
        $metadata = $this->normalizeViolationMetadata($request->input('metadata'));
        if ($type === 'tab_switch') {
            $keywords = $this->detectAiKeywordsInMetadata($metadata);
            if (!empty($keywords)) {
                $metadata['ai_related_keywords_detected'] = $keywords;
            }
            if (!isset($metadata['external_url_capture_supported'])) {
                // Browsers do not expose the exact URL/text from other tabs/apps.
                $metadata['external_url_capture_supported'] = false;
            }
        }
        $metadata['logged_at'] = now()->toIso8601String();
        $createPayload = [
            'quiz_session_id' => $session->id,
            'type' => $type,
            'severity' => $severity,
            'metadata' => json_encode($metadata),
            'occurred_at' => now(),
        ];
        if ($type === 'face_out_of_frame') {
            if (isset($metadata['out_of_frame_duration']) || isset($metadata['out_of_frame_duration_ms'])) {
                $createPayload['out_of_frame_duration'] = (int) ($metadata['out_of_frame_duration'] ?? $metadata['out_of_frame_duration_ms'] ?? 0);
            }
            if (! empty($metadata['evidence_timestamp'])) {
                try {
                    $createPayload['evidence_timestamp'] = \Carbon\Carbon::parse($metadata['evidence_timestamp']);
                } catch (\Throwable $e) {
                    // leave unset
                }
            }
        }
        QuizViolation::create($createPayload);
        $violationAggregates = $this->concurrency->violationAggregates($session);
        $violationByType = $violationAggregates['by_type'];
        $outOfFrameCount = (int) ($violationByType['face_out_of_frame'] ?? 0);
        $focusLeaveCount = (int) ($violationByType['tab_switch'] ?? 0) + (int) ($violationByType['blur'] ?? 0);
        $thresholds = $this->proctoringThresholds();
        $tabSwitchLimit = $thresholds['tab_switch_limit'];
        $outOfFrameCriticalMs = $thresholds['out_of_frame_seconds'] * 1000;
        $multipleFacesCriticalMs = $thresholds['multiple_faces_seconds'] * 1000;
        $autoSubmitted = false;
        $immediateCriticalTypes = [
            'phone_detected',
            'screenshot_attempt',
            'window_resize',
            'copy_paste',
            'multiple_ip',
        ];
        // Tab switch / blur: warn until configured limit, then auto-submit.
        if (in_array($type, ['tab_switch', 'blur'], true)) {
            if ($focusLeaveCount >= $tabSwitchLimit) {
                $session->update([
                    'post_face_skipped_at' => now(),
                    'post_face_skipped_reason' => 'auto_submit',
                    'auto_submit_after' => null,
                    'auto_submitted' => true,
                    'submission_reason' => 'critical_violation_auto_submit',
                ]);
                $this->finalizeQuiz($session);
                $autoSubmitted = true;
            }
        } elseif (in_array($type, $immediateCriticalTypes, true)) {
            // Critical violations: immediate auto-submit on first occurrence (mobile & desktop).
            $session->update([
                'post_face_skipped_at' => now(),
                'post_face_skipped_reason' => 'auto_submit',
                'auto_submit_after' => null,
                'auto_submitted' => true,
                'submission_reason' => 'critical_violation_auto_submit',
            ]);
            $this->finalizeQuiz($session);
            $autoSubmitted = true;
        }
        // Out-of-frame continuously for configured seconds: immediate auto-submit.
        if (!$autoSubmitted && $type === 'face_out_of_frame') {
            $durationMs = (int) ($metadata['out_of_frame_duration_ms'] ?? $metadata['out_of_frame_duration'] ?? 0);
            if ($durationMs > 0 && $durationMs < 1000) {
                $durationMs *= 1000; // value was in seconds
            }
            if ($durationMs >= $outOfFrameCriticalMs) {
                $session->update([
                    'post_face_skipped_at' => now(),
                    'post_face_skipped_reason' => 'auto_submit',
                    'auto_submit_after' => null,
                    'auto_submitted' => true,
                    'submission_reason' => 'critical_violation_auto_submit',
                ]);
                $this->finalizeQuiz($session);
                $autoSubmitted = true;
            }
        }
        // Multiple faces continuously for configured seconds: immediate auto-submit.
        if (!$autoSubmitted && in_array($type, ['multiple_faces_during_quiz', 'multiple_faces'], true)) {
            $durationMs = (int) ($metadata['multiple_faces_duration_ms'] ?? $metadata['multiple_faces_duration'] ?? 0);
            if ($durationMs > 0 && $durationMs < 1000) {
                $durationMs *= 1000;
            }
            if ($durationMs >= $multipleFacesCriticalMs) {
                $session->update([
                    'post_face_skipped_at' => now(),
                    'post_face_skipped_reason' => 'auto_submit',
                    'auto_submit_after' => null,
                    'auto_submitted' => true,
                    'submission_reason' => 'critical_violation_auto_submit',
                ]);
                $this->finalizeQuiz($session);
                $autoSubmitted = true;
            }
        }

        // Out-of-frame: auto-submit after repeated face_out_of_frame events (legacy fallback).
        if (!$autoSubmitted && $type === 'face_out_of_frame') {
            if ($outOfFrameCount >= self::NORMAL_VIOLATION_LIMIT) {
                $session->update([
                    'post_face_skipped_at' => now(),
                    'post_face_skipped_reason' => 'auto_submit',
                    'auto_submit_after' => null,
                    'auto_submitted' => true,
                    'submission_reason' => 'withheld_due_to_violations',
                ]);
                $this->finalizeQuiz($session);
                $autoSubmitted = true;
            }
        }

        // Head turn (left/right/up/down): violation-only, never auto-submit; logged in examiner session.

        // Normal warning threshold across configured warning-level proctoring events.
        if (!$autoSubmitted) {
            if ($outOfFrameCount >= self::NORMAL_VIOLATION_LIMIT) {
                $session->update([
                    'post_face_skipped_at' => now(),
                    'post_face_skipped_reason' => 'auto_submit',
                    'auto_submit_after' => null,
                    'auto_submitted' => true,
                    'submission_reason' => 'withheld_due_to_violations',
                ]);
                $this->finalizeQuiz($session);
                $autoSubmitted = true;
            }
        }

        // Check if session should be marked as risky
        $this->checkAndMarkRiskySession($session, $violationAggregates['by_severity']);

        $response = ['success' => true, 'auto_submitted' => $autoSubmitted];
        $warningTypes = ['camera_disconnected', 'no_face', 'challenge_failed', 'face_out_of_frame', 'head_turn', 'static_face_detected', 'brief_face_loss'];
        if (!$autoSubmitted && in_array($type, ['tab_switch', 'blur'], true)) {
            $response['show_major_warning'] = true;
            $response['tab_switch_strikes'] = $focusLeaveCount;
            $response['tab_switch_remaining'] = max(0, $tabSwitchLimit - $focusLeaveCount);
            $response['tab_switch_limit'] = $tabSwitchLimit;
        } elseif (!$autoSubmitted && in_array($type, $warningTypes, true)) {
            $response['show_major_warning'] = true;
        }
        if ($autoSubmitted) {
            $response['redirect'] = $this->quizCompleteUrl();
        }
        $response['out_of_frame_count'] = $outOfFrameCount;
        $response['escalation'] = $outOfFrameCount > 3;
        $response['remaining_warnings'] = max(0, self::NORMAL_VIOLATION_LIMIT - $outOfFrameCount);
        $response['auto_submit_on_next'] = $outOfFrameCount === (self::NORMAL_VIOLATION_LIMIT - 1);
        $response['normal_violation_count'] = $outOfFrameCount;
        $response['normal_violation_limit'] = self::NORMAL_VIOLATION_LIMIT;
        $response['head_turn_count'] = (int) ($violationByType['head_turn'] ?? 0);
        $response['head_turn_limit'] = self::HEAD_DIRECTION_LIMIT;

        return response()->json($response);
    }

    /**
     * Auto-submit quiz due to violations.
     */
    public function autoSubmit(Request $request): JsonResponse
    {
        $token = session('quiz_session_token');
        if (!$token) {
            return response()->json(['success' => false, 'message' => 'No active session.'], 401);
        }

        $request->validate([
            'session_id' => 'required|exists:quiz_sessions,id',
            'reason' => 'required|string',
            'violation_summary' => 'nullable|array',
            'final_snapshot' => 'nullable|string',
        ]);

        $session = QuizSession::where('session_token', $token)->where('id', $request->session_id)->first();
        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Session not found or invalid.'], 404);
        }

        if ($session->ended_at) {
            return response()->json(['success' => true, 'redirect' => $this->quizCompleteUrl()]);
        }

        // Final snapshot (optional; stored locally when provided)
        if ($request->final_snapshot && Str::startsWith($request->final_snapshot, 'data:image')) {
            try {
                $this->storeViolationCaptureLocally(
                    $session->id,
                    $request->final_snapshot,
                    $session->student_index ?? 'unknown'
                );
            } catch (\Throwable $e) {
                report($e);
            }
        }

        // Update session with violation counts and auto-submit status
        $violationSummary = $request->violation_summary ?? [];
        $minorCount = isset($violationSummary['minor_count']) ? (int) $violationSummary['minor_count'] : (int) ($session->minor_violations ?? 0);
        $majorCount = isset($violationSummary['major_count']) ? (int) $violationSummary['major_count'] : (int) ($session->major_violations ?? 0);
        $outOfFrameCount = $session->violations()->where('type', 'face_out_of_frame')->count();
        $hasOutOfFrameEvidence = $outOfFrameCount > 0;
        $withholdDueToViolations = ($outOfFrameCount > 3 && $hasOutOfFrameEvidence)
            || $this->isCriticalAutoSubmitReason((string) $request->reason);
        $submissionReason = $withholdDueToViolations ? 'withheld_due_to_violations' : trim((string) $request->reason);
        if ($submissionReason === '') {
            $submissionReason = 'auto_submit';
        }

        $session->update([
            'minor_violations' => $minorCount,
            'major_violations' => $majorCount,
            'auto_submitted' => true,
            'submission_reason' => $submissionReason,
            'post_face_skipped_at' => now(),
            'post_face_skipped_reason' => 'auto_submit',
        ]);

        // Finalize quiz
        $this->finalizeQuiz($session);

        return response()->json([
            'success' => true,
            'redirect' => $this->quizCompleteUrl(),
            'withheld' => $withholdDueToViolations,
            'out_of_frame_count' => $outOfFrameCount,
        ]);
    }

    /**
     * Check violation count and update counters.
     */
    protected function checkAndMarkRiskySession(QuizSession $session, ?array $severityCounts = null): void
    {
        $counts = $severityCounts ?? $this->concurrency->violationCountsBySeverity($session);

        $session->update([
            'minor_violations' => $counts['warning'],
            'major_violations' => $counts['critical'],
        ]);
    }

    /**
     * Heartbeat when user returns to the quiz tab. Clears the 20-second auto-submit countdown; returns flag to show "next time immediate" popup.
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $token = session('quiz_session_token');
        if (!$token) {
            return response()->json(['success' => false], 401);
        }
        $session = QuizSession::where('session_token', $token)->first();
        if (!$session || $session->ended_at) {
            return response()->json(['success' => true]);
        }
        $session->load('quiz:id,is_paused,operations_broadcast_message');
        if ($session->quiz?->is_paused) {
            return response()->json([
                'success' => true,
                'paused' => true,
                'broadcast_message' => $session->quiz->operations_broadcast_message,
            ]);
        }
        $this->syncQuizSessionContext($session);
        $hadScheduledSubmit = $session->auto_submit_after !== null;
        $needsDeviceCapture = $session->device_type === null || $session->user_agent === null;
        if ($hadScheduledSubmit || $needsDeviceCapture) {
            $updatePayload = ['auto_submit_after' => null];
            if ($needsDeviceCapture) {
                $ua = $request->userAgent();
                $device = QuizSession::parseUserAgent($ua);
                $updatePayload['user_agent'] = $ua ? substr($ua, 0, 1024) : null;
                $updatePayload['device_type'] = $device['device_type'];
                $updatePayload['device_name'] = $device['device_name'];
            }
            $session->update($updatePayload);
        }
        $this->concurrency->touchHeartbeat($session->id);

        $broadcastKey = 'session_heartbeat_broadcast:' . $session->id;
        if (! \Illuminate\Support\Facades\Cache::has($broadcastKey)) {
            \Illuminate\Support\Facades\Cache::put($broadcastKey, true, 15);
            $this->broadcastDataUpdatedSafe('sessions');
        }

        return response()->json([
            'success' => true,
            'show_tab_switch_warning' => $hadScheduledSubmit,
        ]);
    }

    /**
     * Finalize quiz: compute score, create result. Session resolved from HttpOnly session only.
     */
    public function finalize(Request $request): JsonResponse
    {
        $token = session('quiz_session_token');
        if (!$token) {
            return response()->json(['success' => false], 401);
        }
        $session = QuizSession::with('quiz')->where('session_token', $token)->firstOrFail();
        if ($session->ended_at) {
            return response()->json(['success' => true, 'redirect' => $this->quizCompleteUrl()]);
        }
        if ($this->isIpDeviceRestrictionEnabled() && $session->ip_address !== $request->ip()) {
            return response()->json(['success' => false], 403);
        }
        $reason = trim((string) $request->input('submission_reason', ''));
        if (
            $reason !== 'time_expired'
            && $this->isProctoringCameraRequired()
            && ! $session->post_face_image
            && ! $session->post_face_skipped_reason
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Post-quiz photo is required. Please capture your photo before submitting.',
            ], 403);
        }
        if ($reason === 'time_expired' && ! $session->auto_submitted) {
            $session->update([
                'auto_submitted' => true,
                'submission_reason' => 'time_expired',
            ]);
        }

        $this->finalizeQuiz($session->fresh());

        return response()->json([
            'success' => true,
            'redirect' => $this->quizCompleteUrl(),
        ]);
    }

    /**
     * Quiz complete page. If logged in as owner, redirect to result. Otherwise show friendly message.
     */
    public function quizComplete(): View|\Illuminate\Http\RedirectResponse
    {
        $token = session('quiz_session_token');
        $studentId = session('student_id');
        $student = $studentId ? Student::find($studentId) : null;
        $session = $token && is_string($token)
            ? QuizSession::where('session_token', $token)->first()
            : null;
        $isOwner = $student && $session && strtoupper(trim((string) $student->index_number)) === strtoupper(trim((string) $session->student_index));

        if ($isOwner) {
            return redirect()->route('student.result', ['token' => $token]);
        }

        return view('student.quiz-complete', [
            'isLoggedIn' => (bool) $student,
            'resultUrl' => $session ? route('student.result', ['token' => $token]) : null,
        ]);
    }

    /**
     * URL to show after quiz submit/auto-submit (no marks or review; student must log in).
     */
    private function quizCompleteUrl(): string
    {
        return route('student.quiz.complete');
    }

    /**
     * Result page URL with session token (for logged-in owner only; otherwise redirect to login prompt).
     */
    private function resultUrlWithToken(string $sessionToken): string
    {
        return route('student.result') . '?token=' . urlencode($sessionToken);
    }

    /**
     * Public entry point for finalizing a session (e.g. from scheduler/command).
     */
    public function finalizeQuizSession(QuizSession $session): void
    {
        $this->finalizeQuiz($session);
    }

    /**
     * Finalize quiz: score using assigned snapshot only. Do not re-query live questions table.
     */
    protected function finalizeQuiz(QuizSession $session): void
    {
        if ($session->ended_at === null) {
            $session->update(['ended_at' => now()]);
        }
        $this->concurrency->clearLiveSession((int) $session->id);
        if (! $session->participatedInExam()) {
            return;
        }
        $assignedIds = collect($session->assigned_question_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();
        $answeredIds = $session->answers()
            ->pluck('question_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();
        // Union: score every question the student was assigned or answered (so all questions are graded)
        $lockedIds = $assignedIds->merge($answeredIds)->unique()->values();
        if ($lockedIds->isEmpty()) {
            $lockedIds = $assignedIds->isEmpty() ? $answeredIds : $assignedIds;
        }
        $lockedIdsArray = $lockedIds->all();
        $correctAnswersSnapshot = $session->assigned_correct_answers ?? [];
        $total = count($lockedIdsArray);
        $correct = 0;

        if ($total > 0) {
            $answersByQuestion = $session->decryptedAnswersByQuestionId($lockedIdsArray);
            $questionsById = Question::whereIn('id', $lockedIdsArray)->get()->keyBy('id');

            foreach ($lockedIdsArray as $qid) {
                $correctAnswer = $correctAnswersSnapshot[$qid] ?? $correctAnswersSnapshot[(string) $qid] ?? null;
                $studentAnswer = $answersByQuestion[$qid] ?? $answersByQuestion[(string) $qid] ?? '';

                // Unanswered = wrong
                if (trim((string) $studentAnswer) === '') {
                    continue;
                }
                if ($correctAnswer === null) {
                    continue;
                }

                $question = $questionsById->get($qid);
                $type = $question?->type ?? 'mcq';
                if (\App\Support\QuestionTypes::answersMatch((string) $studentAnswer, (string) $correctAnswer, (string) $type)) {
                    $correct++;
                }
            }
        }
        
        $violationsCount = $session->violations()->count();
        
        // Ensure score doesn't exceed 100% and correct count doesn't exceed total
        $correct = min($correct, $total);
        $score = $total > 0 ? round(100 * $correct / $total, 2) : 0;
        $score = min($score, 100.00); // Cap at 100%
        
        Result::updateOrCreate([
            'quiz_session_id' => $session->id,
        ], [
            'score' => $score,
            'total_questions' => $total,
            'correct_count' => $correct,
            'violations_count' => $violationsCount,
            'submitted_at' => now(),
        ]);
        $this->broadcastDataUpdatedSafe('dashboard');
        $this->broadcastDataUpdatedSafe('sessions');
        try {
            SendQuizResultReadyNotification::dispatch($session->id);
        } catch (\Throwable $e) {
            report($e);
        }

        $session->refresh();
        if ($session->isResultWithheld()) {
            try {
                app(StudentNotificationService::class)->notifyResultHeld($session);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    private function isIpDeviceRestrictionEnabled(): bool
    {
        return Setting::getValue(Setting::KEY_DISABLE_IP_DEVICE_RESTRICTIONS, '0') !== '1';
    }

    private function isProctoringCameraRequired(): bool
    {
        return $this->proctoringFlags()[Setting::KEY_PROCTORING_CAMERA_REQUIRED];
    }

    /**
     * Detect if the request is from a mobile device (phone/tablet) for quiz device gating.
     */
    private function isMobileRequest(Request $request): bool
    {
        $ua = $request->userAgent() ?? '';
        return (bool) preg_match('/Mobile|Android|iPhone|iPad|iPod|webOS|BlackBerry|IEMobile|Opera Mini|Tablet|Silk|Kindle|MiuiBrowser|SamsungBrowser/i', $ua);
    }

    private function isProctoringTypeEnabled(string $type): bool
    {
        $flags = $this->proctoringFlags();
        $tabSwitchEnabled = $flags[Setting::KEY_PROCTORING_TAB_SWITCH];
        $faceMonitorEnabled = $flags[Setting::KEY_PROCTORING_FACE_MONITOR];
        $objectDetectEnabled = $flags[Setting::KEY_PROCTORING_OBJECT_DETECT];
        $rightClickBlocked = $flags[Setting::KEY_PROCTORING_BLOCK_RIGHT_CLICK];
        $copyPasteBlocked = $flags[Setting::KEY_PROCTORING_BLOCK_COPY_PASTE];
        $cameraRequired = $flags[Setting::KEY_PROCTORING_CAMERA_REQUIRED];

        if (in_array($type, ['tab_switch', 'blur', 'window_resize', 'screenshot_attempt'], true)) {
            return $tabSwitchEnabled;
        }
        if ($type === 'right_click') {
            return $rightClickBlocked;
        }
        if ($type === 'copy_paste') {
            return $copyPasteBlocked;
        }
        if ($type === 'phone_detected') {
            return $cameraRequired && $objectDetectEnabled;
        }
        if (in_array($type, [
            'camera_disconnected',
            'no_face',
            'multiple_faces',
            'multiple_faces_pre_quiz',
            'multiple_faces_during_quiz',
            'random_snapshot',
            'external_audio',
            'no_blink',
            'head_turn',
            'brief_face_loss',
            'challenge_failed',
            'static_face_detected',
            'no_face_during_quiz',
            'face_out_of_frame',
            'face_lost_repeatedly',
        ], true)) {
            return $cameraRequired && $faceMonitorEnabled;
        }

        return true;
    }

    private function isCriticalAutoSubmitReason(string $reason): bool
    {
        $normalized = Str::lower(trim($reason));
        if ($normalized === '') {
            return false;
        }

        if (str_contains($normalized, 'critical')) {
            return true;
        }

        foreach ([
            'copy_paste',
            'multiple_ip',
            'screenshot_attempt',
            'phone_detected',
            'tab_switch',
            'window_resize',
            'multiple_faces',
            'multiple_faces_during_quiz',
            'blur',
        ] as $needle) {
            if (str_contains($normalized, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Store violation image on server under violations/{index_number}/{date}_{time}_{session_id}_{random}.jpg.
     * Ensures violations directory exists and is logged by student index and date/time.
     */
    private function storeViolationCaptureLocally(int $sessionId, string $dataUrl, string $studentIndex = 'unknown'): string
    {
        $parts = explode(',', $dataUrl, 2);
        if (count($parts) !== 2) {
            throw new \RuntimeException('Invalid data URL');
        }
        $binary = base64_decode($parts[1], true);
        if ($binary === false) {
            throw new \RuntimeException('Failed to decode image');
        }

        $safeIndex = preg_replace('/[^a-zA-Z0-9_-]/', '_', trim((string) $studentIndex)) ?: 'unknown';
        $now = now();
        $fileName = $now->format('Y-m-d') . '_' . $now->format('His') . '_s' . $sessionId . '_' . Str::random(8) . '.jpg';
        $path = 'violations/' . $safeIndex . '/' . $fileName;
        $disk = Storage::disk('public');
        $disk->makeDirectory('violations/' . $safeIndex);
        $disk->put($path, $binary);

        return $path;
    }

    private function normalizeViolationMetadata(mixed $metadata): array
    {
        if (is_array($metadata)) {
            return $metadata;
        }
        if (is_string($metadata)) {
            $trimmed = trim($metadata);
            if ($trimmed === '') {
                return [];
            }
            $decoded = json_decode($trimmed, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
            return ['message' => $trimmed];
        }
        return [];
    }

    private function detectAiKeywordsInMetadata(array $metadata): array
    {
        $haystack = Str::lower(json_encode($metadata) ?: '');
        if ($haystack === '') {
            return [];
        }
        $keywords = ['chatgpt', 'openai', 'deepseek', 'gemini', 'google', 'ngrok', 'claude', 'copilot', 'perplexity'];
        $matched = [];
        foreach ($keywords as $keyword) {
            if (str_contains($haystack, $keyword)) {
                $matched[] = $keyword;
            }
        }
        return array_values(array_unique($matched));
    }

    /**
     * Result page. Marks and review are shown only when the visitor is logged in as the student who took the quiz.
     * Otherwise show "log in to see results". Session token from session or query (?token=).
     */
    public function result(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $token = session('quiz_session_token') ?? $request->query('token');
        if (!$token || !is_string($token)) {
            return redirect()->route('student.landing')->with('error', UserFriendlyMessages::NOT_FOUND);
        }
        $session = QuizSession::with(['quiz', 'result', 'answers.question'])->where('session_token', $token)->first();
        if (!$session) {
            return redirect()->route('student.landing')->with('error', UserFriendlyMessages::NOT_FOUND);
        }

        $studentId = session('student_id');
        $student = $studentId ? Student::find($studentId) : null;
        $isOwner = $student && strtoupper(trim((string) $student->index_number)) === strtoupper(trim((string) $session->student_index));
        $sessionToken = session('quiz_session_token');
        $isSameBrowserSession = is_string($sessionToken) && hash_equals($sessionToken, (string) $token);

        if (!$isOwner && !$isSameBrowserSession) {
            return view('student.quiz-complete', [
                'isLoggedIn' => (bool) $student,
            ]);
        }

        if (!session('quiz_session_token')) {
            session(['quiz_session_token' => $token]);
        }

        $assignedCorrect = $session->assigned_correct_answers ?? [];
        $assignedIds = collect($session->assigned_question_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();
        $reviewQuestions = collect();
        if (!empty($assignedIds)) {
            $reviewQuestions = Question::whereIn('id', $assignedIds)->get()
                ->sortBy(fn ($q) => array_search((int) $q->id, $assignedIds, true))
                ->values();
        } else {
            $reviewQuestions = $session->answers
                ->pluck('question')
                ->filter()
                ->values();
        }
        $aiService = app(AiQuestionService::class);
        $needsExplanations = false;
        foreach ($session->answers as $answer) {
            $q = $answer->question;
            if (!$q) {
                continue;
            }
            $sessionCorrect = $assignedCorrect[$q->id] ?? $assignedCorrect[(string) $q->id] ?? $q->correct_answer;
            $correct = trim((string) $answer->student_answer) === trim((string) $sessionCorrect);
            if ($correct) {
                continue;
            }
            if (!empty($q->explanation_wrong) || !empty($answer->explanation_wrong)) {
                continue;
            }
            $needsExplanations = true;
            break;
        }
        if ($needsExplanations && $aiService->hasApiKey()) {
            GenerateWrongAnswerExplanationsJob::dispatch($session->id);
        }

        $session->load(['quiz', 'result', 'answers.question']);
        return view('student.result', [
            'session' => $session,
            'resultUrl' => $this->resultUrlWithToken($token),
            'reviewQuestions' => $reviewQuestions,
        ]);
    }
}
