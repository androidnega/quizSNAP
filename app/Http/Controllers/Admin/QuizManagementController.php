<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Http\Controllers\Concerns\BroadcastsDataUpdatesSafely;
use App\Models\AcademicClass;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\AcademicYear;
use App\Models\QuizCategory;
use App\Models\Semester;
use App\Models\StudentLevel;
use App\Models\FaceImageViewLog;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\Question;
use App\Models\Result;
use App\Jobs\GenerateQuizQuestionsJob;
use App\Jobs\SendQuizResultReadyNotification;
use App\Models\QuestionPool;
use App\Models\Setting;
use App\Models\User;
use App\Exports\QuizScoresExport;
use App\Services\AiQuestionService;
use App\Services\AiQuizGenerationProgress;
use App\Services\AiQuizTokenService;
use App\Support\UserFriendlyMessages;
use App\Services\AiTopicExtractorService;
use App\Services\LocalUploadService;
use App\Services\QuizBackupService;
use App\Services\StudentNotificationService;
use App\Services\DocumentTextExtractor;
use App\Support\QuestionTypes;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class QuizManagementController extends Controller
{
    use BroadcastsDataUpdatesSafely;
    use InteractsWithAdminSession;
    /** Course IDs the current examiner is assigned to (all for super_admin). */
    private function assignedCourseIds(): array
    {
        $user = $this->adminUser();
        return $user ? $user->assignedCourseIds() : [];
    }

    /** Class group IDs the current examiner owns (all for super_admin). */
    private function classGroupIds(): array
    {
        $user = $this->adminUser();
        return $user ? $user->classGroupIds() : [];
    }

    public function index(Request $request): View
    {
        $user = $this->adminUser();
        $classGroupIds = $this->classGroupIds();
        $tab = $request->query('tab', 'active');
        $query = Quiz::with(['course', 'classGroup.level', 'academicClass'])
            ->withCount([
                'questions',
                'sessions as sessions_started_count' => fn ($q) => $q->whereNotNull('start_time'),
            ])
            ->orderByDesc('created_at');

        if ($user && $user->isSuperAdmin()) {
            // Super Admin sees all quizzes
        } elseif ($user && $user->isExaminer()) {
            // Data isolation: examiners see only quizzes they created
            $query->where('examiner_id', $user->id);
        } else {
            $query->where(function ($q) use ($classGroupIds, $user) {
                if (!empty($classGroupIds)) {
                    $q->whereIn('class_group_id', $classGroupIds);
                }
                if ($user && $user->id) {
                    $q->orWhere('examiner_id', $user->id);
                }
                if (empty($classGroupIds) && (!$user || !$user->id)) {
                    $q->whereRaw('1=0');
                }
            });
        }

        if ($tab === 'ended') {
            $query->ended();
        } else {
            $query->active();
        }

        $quizzes = $query->paginate(15)->withQueryString();
        return view('admin.quizzes.index', compact('quizzes', 'tab'));
    }

    public function create(): View
    {
        $this->authorize('create', Quiz::class);
        $user = $this->adminUser();
        $classGroupIds = $this->classGroupIds();
        $classGroups = ClassGroup::with(['courses' => fn ($q) => $q->withPivot('examiner_id'), 'level'])
            ->whereIn('id', $classGroupIds)
            ->withCount('students')
            ->orderBy('name')
            ->get()
            ->filter(fn (ClassGroup $g) => $g->students_count > 0);

        // Data isolation: examiners see only their assigned courses per group
        if ($user?->isExaminer()) {
            $classGroups = $classGroups->map(function (ClassGroup $g) use ($user) {
                $g->setRelation('courses', $g->courses->filter(fn ($c) => (int) ($c->pivot->examiner_id ?? 0) === (int) $user->id)->values());
                return $g;
            })->filter(fn (ClassGroup $g) => $g->courses->isNotEmpty());
        }
        $aiApiAvailable = app(AiQuestionService::class)->hasApiKey();
        $aiGenerationEnabled = AiQuestionService::isGenerationEnabled();
        $aiTokenStatus = $user ? app(AiQuizTokenService::class)->getStatus($user) : null;
        if ($classGroups->isEmpty()) {
            session()->flash('error', 'Error');
        }
        // QuizSnap academic structure for cascading selects
        $quizCategories = QuizCategory::ordered();
        $levels = StudentLevel::ordered();
        $semesters = \App\Models\Semester::ordered();
        $academicYears = AcademicYear::orderBy('year', 'desc')->get();
        return view('admin.quizzes.create', compact('classGroups', 'aiApiAvailable', 'aiGenerationEnabled', 'aiTokenStatus', 'quizCategories', 'levels', 'semesters', 'academicYears'));
    }

    /**
     * Validate pasted AI JSON (Phase 3 – ChatGPT/manual flow). Returns JSON with valid and errors.
     */
    public function validateAiJson(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            // Allow large AI JSON payloads (200k+ characters)
            'ai_json' => 'required|string|max:200000',
            'number_of_questions' => 'required|integer|min:1|max:250',
            'include_mcq' => 'nullable|boolean',
            'include_true_false' => 'nullable|boolean',
            'include_fill_in' => 'nullable|boolean',
            'mcq_count' => 'nullable|integer|min:0|max:250',
            'true_false_count' => 'nullable|integer|min:0|max:250',
            'fill_in_count' => 'nullable|integer|min:0|max:250',
        ]);
        $aiService = app(AiQuestionService::class);
        $typeCounts = $this->parseQuestionTypeCountsFromRequest($request);
        $result = $aiService->validateAiJson(
            $request->input('ai_json'),
            (int) $request->number_of_questions,
            $typeCounts
        );
        return response()->json([
            'valid' => $result['valid'],
            'errors' => $result['errors'],
        ]);
    }

    /**
     * Extract quiz topics from an optional course outline (AJAX on create form).
     */
    public function extractTopics(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $this->adminUser();
        if (! $user) {
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        $request->validate([
            'source_script' => 'nullable|string|max:100000',
            'source_outline' => 'nullable|file|mimes:txt,pdf,docx|max:10240',
            'course_id' => 'nullable|exists:courses,id',
        ]);

        $aiService = app(AiQuestionService::class);
        if (! $aiService->hasApiKey()) {
            return response()->json([
                'error' => 'AI is unavailable. Add a DeepSeek API key in Dashboard → Settings → AI.',
            ], 422);
        }

        $courseCode = null;
        $courseName = null;
        if ($request->filled('course_id')) {
            $course = Course::find((int) $request->course_id);
            if ($course) {
                $courseCode = $course->code;
                $courseName = $course->name;
            }
        }

        $extractor = app(AiTopicExtractorService::class);
        if ($request->hasFile('source_outline')) {
            $result = $extractor->extractFromFile($request->file('source_outline'), $courseCode, $courseName);
        } elseif ($request->filled('source_script') && trim((string) $request->source_script) !== '') {
            $result = $extractor->extractFromText(trim((string) $request->source_script), $courseCode, $courseName);
        } else {
            return response()->json(['error' => 'Upload an outline file or paste outline text first.'], 422);
        }

        if ($result['error'] !== null) {
            return response()->json(['error' => $result['error']], 422);
        }

        return response()->json(['topics' => $result['topics']]);
    }

    /**
     * Poll background AI generation progress for a quiz.
     */
    public function aiGenerationStatus(Quiz $quiz): \Illuminate\Http\JsonResponse
    {
        $this->authorize('view', $quiz);
        $progress = AiQuizGenerationProgress::get((int) $quiz->id);
        if ($progress === null) {
            $poolCount = $quiz->questionPools()->count();
            $target = max(1, (int) $quiz->number_of_questions);

            return response()->json([
                'status' => $poolCount > 0 ? 'completed' : 'idle',
                'generated' => $poolCount,
                'target' => $target,
                'percent' => min(100, (int) round(($poolCount / $target) * 100)),
                'message' => null,
            ]);
        }

        if (($progress['status'] ?? '') === 'running') {
            $progress['generated'] = $quiz->questionPools()->count();
        }

        $target = max(1, (int) ($progress['target'] ?? $quiz->number_of_questions));
        $generated = (int) ($progress['generated'] ?? 0);
        $progress['percent'] = min(100, (int) round(($generated / $target) * 100));

        return response()->json($progress);
    }

    private function wantsJsonResponse(Request $request): bool
    {
        return $request->expectsJson() || $request->ajax();
    }

    /** @return RedirectResponse|JsonResponse */
    private function createQuizFormError(Request $request, string $error, ?array $fieldErrors = null, int $status = 422): RedirectResponse|JsonResponse
    {
        if ($this->wantsJsonResponse($request)) {
            return response()->json([
                'error' => $error,
                'errors' => $fieldErrors ?? ['form' => [$error]],
            ], $status);
        }

        $redirect = redirect()->route($this->staffRoutePrefix() . '.quizzes.create')->withInput();
        if ($fieldErrors !== null) {
            return $redirect->withErrors($fieldErrors);
        }

        return $redirect->with('error', $error);
    }

    /** @return RedirectResponse|JsonResponse */
    private function aiQuizCreatedResponse(Request $request, Quiz $quiz, int $target, string $message): RedirectResponse|JsonResponse
    {
        $statusUrl = route($this->staffRoutePrefix() . '.quizzes.ai-generation-status', $quiz);
        $redirectUrl = route($this->staffRoutePrefix() . '.quizzes.show', ['quiz' => $quiz->id]);

        if ($this->wantsJsonResponse($request)) {
            return response()->json([
                'success' => true,
                'quiz_id' => $quiz->id,
                'target' => $target,
                'generated' => 0,
                'percent' => 0,
                'status' => 'running',
                'status_url' => $statusUrl,
                'redirect_url' => $redirectUrl,
                'message' => $message,
            ]);
        }

        return redirect()->to($redirectUrl)->with('success', $message);
    }

    /** @return array<string, int> */
    private function parseQuestionTypeCountsFromRequest(Request $request): array
    {
        $counts = [];
        if ($request->boolean('include_mcq', true)) {
            $counts[QuestionTypes::MCQ] = max(0, (int) $request->input('mcq_count', 0));
        }
        if ($request->boolean('include_true_false')) {
            $counts[QuestionTypes::TRUE_FALSE] = max(0, (int) $request->input('true_false_count', 0));
        }
        if ($request->boolean('include_fill_in')) {
            $counts[QuestionTypes::FILL_IN] = max(0, (int) $request->input('fill_in_count', 0));
        }

        return QuestionTypes::normalizeCounts($counts);
    }

    /** @return array<string, int>|null Returns error message string when invalid */
    private function questionTypeCountsOrError(Request $request): array|string|null
    {
        $counts = $this->parseQuestionTypeCountsFromRequest($request);
        if (QuestionTypes::total($counts) < 1) {
            return 'Select at least one question type (MCQ, True/False, or Fill in) and set counts greater than 0.';
        }
        $expectedTotal = (int) $request->input('number_of_questions', 0);
        if ($expectedTotal > 0 && QuestionTypes::total($counts) !== $expectedTotal) {
            return 'Question type counts must add up to the pool total (' . $expectedTotal . ').';
        }

        return $counts;
    }

    /** @return list<int> */
    private function resolveClassGroupIdsFromRequest(Request $request): array
    {
        $ids = $request->input('class_group_ids', []);
        if (! is_array($ids)) {
            $ids = $ids !== null && $ids !== '' ? [(int) $ids] : [];
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === [] && $request->filled('class_group_id')) {
            $ids = [(int) $request->class_group_id];
        }

        return $ids;
    }

    private function validateClassGroupForQuiz(User $user, ClassGroup $classGroup, int $courseId): ?string
    {
        if (! $classGroup->hasStudents()) {
            return 'Class group "' . ($classGroup->display_name ?? $classGroup->name) . '" has no students.';
        }
        if (! $classGroup->courses()->where('courses.id', $courseId)->exists()) {
            return 'The selected course is not attached to "' . ($classGroup->display_name ?? $classGroup->name) . '".';
        }
        if ($user->isExaminer()) {
            $teachesCourse = $classGroup->courses()
                ->where('courses.id', $courseId)
                ->wherePivot('examiner_id', $user->id)
                ->exists();
            if (! $teachesCourse) {
                return 'You are not assigned to teach this course in "' . ($classGroup->display_name ?? $classGroup->name) . '".';
            }
        } elseif (! $user->isSuperAdmin() && ! $user->isCoordinator()) {
            return UserFriendlyMessages::GENERIC;
        }

        return null;
    }

    /** @param array<string, mixed> $baseCreateData */
    private function quizCreateDataForClassGroup(array $baseCreateData, int $classGroupId, Request $request): array
    {
        $createData = $baseCreateData;
        $createData['class_group_id'] = $classGroupId;
        if (Schema::hasColumn('quizzes', 'allowed_devices')) {
            $defaultAllowed = ClassGroup::find($classGroupId)?->getEffectiveAllowedDevices() ?? Quiz::ALLOWED_DEVICES_DESKTOP;
            $createData['allowed_devices'] = in_array($request->input('allowed_devices'), [Quiz::ALLOWED_DEVICES_DESKTOP, Quiz::ALLOWED_DEVICES_MOBILE, Quiz::ALLOWED_DEVICES_BOTH], true)
                ? $request->input('allowed_devices')
                : $defaultAllowed;
        }

        return $createData;
    }

    /** @param list<Quiz> $quizzes */
    private function quizzesCreatedResponse(Request $request, array $quizzes, string $message, bool $isAi = false, ?int $aiTarget = null): RedirectResponse|JsonResponse
    {
        if (count($quizzes) === 1 && $isAi && $aiTarget !== null) {
            return $this->aiQuizCreatedResponse($request, $quizzes[0], $aiTarget, $message);
        }

        $indexUrl = route($this->staffRoutePrefix() . '.quizzes.index');
        if (count($quizzes) === 1) {
            if ($this->wantsJsonResponse($request)) {
                return response()->json([
                    'success' => true,
                    'quiz_id' => $quizzes[0]->id,
                    'redirect_url' => route($this->staffRoutePrefix() . '.quizzes.show', ['quiz' => $quizzes[0]->id]),
                    'message' => $message,
                ]);
            }

            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', ['quiz' => $quizzes[0]->id])
                ->with('success', $message);
        }

        if ($this->wantsJsonResponse($request)) {
            return response()->json([
                'success' => true,
                'multiple' => true,
                'count' => count($quizzes),
                'redirect_url' => $indexUrl,
                'message' => $message,
            ]);
        }

        return redirect()->to($indexUrl)->with('success', $message);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        try {
            $user = $this->adminUser();
            if (!$user) {
                return $this->createQuizFormError(
                    $request,
                    'Your session has expired. Refresh the page and sign in again.',
                    null,
                    401
                );
            }

            $request->validate([
                'title' => 'required|string|max:255',
                'exam_type' => 'nullable|in:quiz,midsem,end_of_semester',
                'class_group_ids' => 'nullable|array|min:1',
                'class_group_ids.*' => 'integer|exists:class_groups,id',
                'class_group_id' => 'nullable|exists:class_groups,id',
                'course_id' => 'required|exists:courses,id',
                'academic_year_id' => 'nullable|exists:academic_years,id',
                'quiz_category_id' => 'nullable|exists:quiz_categories,id',
                'level_id' => 'nullable|exists:student_levels,id',
                'semester_id' => 'nullable|exists:semesters,id',
                'academic_class_id' => 'nullable|exists:academic_classes,id',
                'number_of_questions' => 'required|integer|min:1|max:250',
                'questions_per_student' => 'required|integer|min:1|max:250',
                'duration_minutes' => 'required|integer|min:1|max:300',
                'topics' => 'nullable|string|max:1000',
                // Allow large AI JSON payloads (200k+ characters)
                'ai_json' => 'nullable|string|max:200000',
                'is_active' => 'boolean',
                'starts_at' => 'nullable|date',
                'ends_at' => 'nullable|date|after_or_equal:starts_at',
                'result_visibility' => 'nullable|in:score_only,full_review_after_end,disabled',
                'allowed_devices' => 'nullable|in:desktop,mobile,both',
                'include_mcq' => 'nullable|boolean',
                'include_true_false' => 'nullable|boolean',
                'include_fill_in' => 'nullable|boolean',
                'mcq_count' => 'nullable|integer|min:0|max:250',
                'true_false_count' => 'nullable|integer|min:0|max:250',
                'fill_in_count' => 'nullable|integer|min:0|max:250',
                'question_source' => 'required|in:ai,json',
                'source_script' => 'nullable|string|max:100000',
                'source_outline' => 'nullable|file|mimes:txt,pdf,docx|max:10240',
            ], [
                'course_id.required' => 'Please select a course (from Class Group or QuizSnap section).',
                'course_id.exists' => 'The selected course is invalid or no longer exists.',
                'class_group_ids.min' => 'Select at least one class group.',
            ]);

            $classGroupIds = $this->resolveClassGroupIdsFromRequest($request);
            $requestCourseId = (int) $request->course_id;
            $usesQuizSnapFlow = $request->filled('academic_class_id') && $request->filled('academic_year_id');

            if (! empty($classGroupIds)) {
                $allowedGroupIds = $this->classGroupIds();
                foreach ($classGroupIds as $classGroupId) {
                    if (! $user->isSuperAdmin() && ! in_array($classGroupId, $allowedGroupIds, true)) {
                        return $this->createQuizFormError($request, UserFriendlyMessages::NOT_FOUND);
                    }
                    $classGroup = ClassGroup::find($classGroupId);
                    if (! $classGroup) {
                        return $this->createQuizFormError($request, UserFriendlyMessages::NOT_FOUND);
                    }
                    $groupError = $this->validateClassGroupForQuiz($user, $classGroup, $requestCourseId);
                    if ($groupError !== null) {
                        return $this->createQuizFormError($request, $groupError);
                    }
                }
            } elseif (! $usesQuizSnapFlow) {
                return $this->createQuizFormError(
                    $request,
                    'Select at least one class group, or use QuizSnap academic context (Category, Level, Semester, Class, Academic Year).',
                    ['class_group_ids' => ['Select at least one class group, or complete the QuizSnap academic fields.']]
                );
            }

            $topics = $request->topics;
            if (is_string($topics) && $topics !== '') {
                $topics = array_map('trim', explode(',', $topics));
                $topics = array_filter($topics);
                $topics = array_map(fn ($t) => ['name' => $t], $topics);
            } else {
                $topics = [];
            }

            $typeCountsOrError = $this->questionTypeCountsOrError($request);
            if (is_string($typeCountsOrError)) {
                return $this->createQuizFormError($request, $typeCountsOrError);
            }
            $typeCounts = $typeCountsOrError;
            $poolTotal = QuestionTypes::total($typeCounts);

            $baseCreateData = [
                'title' => $request->title,
                'exam_type' => $request->input('exam_type') ?: null,
                'class_group_id' => null,
                'course_id' => $requestCourseId,
                'academic_year_id' => $usesQuizSnapFlow ? (int) $request->academic_year_id : null,
                'quiz_category_id' => $usesQuizSnapFlow && $request->filled('quiz_category_id') ? (int) $request->quiz_category_id : null,
                'level_id' => $usesQuizSnapFlow && $request->filled('level_id') ? (int) $request->level_id : null,
                'semester_id' => $usesQuizSnapFlow && $request->filled('semester_id') ? (int) $request->semester_id : null,
                'academic_class_id' => $usesQuizSnapFlow ? (int) $request->academic_class_id : null,
                'examiner_id' => $user->id,
                'status' => \App\Models\Quiz::STATUS_DRAFT,
                'number_of_questions' => $poolTotal,
                'question_type_counts' => $typeCounts,
                'duration_minutes' => (int) $request->duration_minutes,
                'topics' => !empty($topics) ? json_encode(array_values($topics)) : null,
                'is_active' => $request->boolean('is_active', true),
                'is_published' => false,
                'starts_at' => $request->filled('starts_at') ? $request->starts_at : null,
                'ends_at' => $request->filled('ends_at') ? $request->ends_at : null,
                'result_visibility' => $request->input('result_visibility', Quiz::RESULT_VISIBILITY_FULL_REVIEW_AFTER_END),
            ];
            if (Schema::hasColumn('quizzes', 'questions_per_student')) {
                $baseCreateData['questions_per_student'] = (int) $request->questions_per_student;
            }
            if (empty($classGroupIds) && Schema::hasColumn('quizzes', 'allowed_devices')) {
                $baseCreateData['allowed_devices'] = in_array($request->input('allowed_devices'), [Quiz::ALLOWED_DEVICES_DESKTOP, Quiz::ALLOWED_DEVICES_MOBILE, Quiz::ALLOWED_DEVICES_BOTH], true)
                    ? $request->input('allowed_devices')
                    : Quiz::ALLOWED_DEVICES_DESKTOP;
            }

            $quizTargets = ! empty($classGroupIds) ? $classGroupIds : [null];

            $aiService = app(AiQuestionService::class);
            $questionSource = $request->input('question_source', 'json');

            if ($questionSource === 'json') {
                if (! $request->filled('ai_json')) {
                    return $this->createQuizFormError(
                        $request,
                        'Paste and validate your question JSON, or choose Generate with AI instead.',
                        ['ai_json' => ['Paste and validate your question JSON, or choose Generate with AI instead.']]
                    );
                }
                $expectedCount = $poolTotal;
                $result = $aiService->validateAiJson($request->input('ai_json'), $expectedCount, $typeCounts);
                if (! $result['valid']) {
                    $jsonErrors = is_array($result['errors']) ? $result['errors'] : [$result['errors']];

                    return $this->createQuizFormError(
                        $request,
                        implode(' ', $jsonErrors),
                        ['ai_json' => $jsonErrors]
                    );
                }
                $createdQuizzes = [];
                foreach ($quizTargets as $classGroupId) {
                    $createData = $classGroupId
                        ? $this->quizCreateDataForClassGroup($baseCreateData, (int) $classGroupId, $request)
                        : $baseCreateData;
                    $quiz = Quiz::createFromAttributes($createData);
                    if (! $quiz || ! $quiz->id) {
                        return $this->createQuizFormError($request, 'Failed to create quiz.');
                    }
                    $aiService->createPoolsFromValidatedJson($quiz, $result['parsed']);
                    $createdQuizzes[] = $quiz;
                }
                $generatedCount = $createdQuizzes[0]->questionPools()->count();
                $quizCount = count($createdQuizzes);
                $message = $quizCount > 1
                    ? $quizCount . ' quizzes created with ' . $generatedCount . ' question(s) each from pasted JSON. Approve them to publish.'
                    : 'Quiz created. ' . $generatedCount . ' question(s) from pasted JSON. Approve them below to publish.';
                $this->broadcastDataUpdatedSafe('quizzes');
                foreach ($createdQuizzes as $quiz) {
                    try {
                        QuizBackupService::sendIfConfigured($quiz);
                    } catch (\Throwable $e) {
                        // Do not fail the request if backup send fails
                    }
                }

                return $this->quizzesCreatedResponse($request, $createdQuizzes, $message);
            }

            try {
                Cache::forget('setting:' . \App\Models\Setting::KEY_DEEPSEEK_API);
            } catch (\Throwable $e) {
                report($e);
            }
            if (! $aiService->hasApiKey()) {
                return $this->createQuizFormError(
                    $request,
                    'AI generation is unavailable. Add a DeepSeek API key in Dashboard → Settings → AI, or choose Paste JSON instead.'
                );
            }

            $tokenService = app(AiQuizTokenService::class);
            if (! $tokenService->canUse($user)) {
                $status = $tokenService->getStatus($user);

                return $this->createQuizFormError(
                    $request,
                    $status['message'] ?? 'You cannot use AI question generation. Choose Paste JSON or contact your administrator.'
                );
            }

            $scriptText = $this->resolveOutlineTextFromRequest($request);
            $hasOutline = $scriptText !== null && trim($scriptText) !== '';
            if (empty($topics) && ! $hasOutline) {
                return $this->createQuizFormError(
                    $request,
                    'Add at least one topic, or upload/paste a course outline for AI to extract topics from.',
                    ['topics' => ['Add at least one topic, or upload/paste a course outline for AI to extract topics from.']]
                );
            }

            if (empty($topics) && $hasOutline) {
                $course = Course::find($requestCourseId);
                try {
                    $extractResult = app(AiTopicExtractorService::class)->extractFromText(
                        $scriptText,
                        $course?->code,
                        $course?->name
                    );
                } catch (\Throwable $e) {
                    report($e);

                    return $this->createQuizFormError(
                        $request,
                        'Could not extract topics from the outline. Add topics manually or try again.'
                    );
                }
                if (! empty($extractResult['topics'])) {
                    $topics = array_map(fn ($t) => ['name' => $t], $extractResult['topics']);
                    $baseCreateData['topics'] = json_encode(array_values($topics));
                } elseif ($extractResult['error'] !== null) {
                    return $this->createQuizFormError($request, $extractResult['error']);
                }
            }

            if ($hasOutline) {
                $baseCreateData['script_text'] = $scriptText;
            }

            $target = $poolTotal;
            $topicList = ! empty($topics) ? $topics : [['name' => 'General knowledge']];
            $createdQuizzes = [];
            foreach ($quizTargets as $classGroupId) {
                $createData = $classGroupId
                    ? $this->quizCreateDataForClassGroup($baseCreateData, (int) $classGroupId, $request)
                    : $baseCreateData;
                $quiz = Quiz::createFromAttributes($createData);
                if (! $quiz || ! $quiz->id) {
                    return $this->createQuizFormError($request, 'Failed to create quiz.');
                }
                AiQuizGenerationProgress::start((int) $quiz->id, $target);
                $this->dispatchQuizGenerationJob((int) $quiz->id, $topicList, $target, (int) $user->id);
                $createdQuizzes[] = $quiz;
            }
            $this->processAiQueueAfterResponse(count($createdQuizzes));

            $quizCount = count($createdQuizzes);
            $message = $quizCount > 1
                ? $quizCount . ' quizzes created. Generating ' . $target . ' question(s) for each…'
                : 'Quiz created. Generating ' . $target . ' question(s)…';

            return $this->quizzesCreatedResponse($request, $createdQuizzes, $message, true, $target);

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Quiz create failed', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $userMessage = 'Could not create quiz. ';
            if ($e instanceof \Illuminate\Database\QueryException) {
                if (str_contains($e->getMessage(), 'Unknown column')) {
                    $userMessage .= 'Database schema is outdated — on the server run: php artisan migrate --force';
                } elseif (str_contains($e->getMessage(), 'foreign key constraint')) {
                    $userMessage .= 'A selected course or class group is no longer valid. Refresh the page and try again.';
                } elseif (config('app.debug')) {
                    $userMessage .= $e->getMessage();
                } else {
                    $userMessage .= 'A database error occurred. Contact support if this continues.';
                }
            } elseif (config('app.debug')) {
                $userMessage .= $e->getMessage();
            } else {
                $userMessage .= 'Please try again or contact support if the problem persists.';
            }

            if ($this->wantsJsonResponse($request)) {
                return response()->json(['error' => $userMessage], 500);
            }

            return redirect()->route($this->staffRoutePrefix() . '.quizzes.create')
                ->withInput()
                ->with('error', $userMessage);
        }
    }
    private function resolveOutlineTextFromRequest(Request $request): ?string
    {
        if ($request->filled('source_script') && trim((string) $request->source_script) !== '') {
            return trim((string) $request->source_script);
        }
        if ($request->hasFile('source_outline')) {
            $text = app(DocumentTextExtractor::class)->extract($request->file('source_outline'));
            if (trim($text) !== '') {
                return trim($text);
            }
        }

        return null;
    }

    /** Process queued AI jobs after the HTTP response (when no dedicated worker is running). */
    private function processAiQueueAfterResponse(int $maxJobs = 1): void
    {
        if (! config('quizsnap.ai.process_queue_after_dispatch', true)) {
            return;
        }

        $maxJobs = max(1, min(5, $maxJobs));

        app()->terminating(function () use ($maxJobs) {
            try {
                Artisan::call('queue:work', [
                    '--max-jobs' => $maxJobs,
                    '--stop-when-empty' => true,
                    '--timeout' => 900,
                    '--tries' => 2,
                ]);
            } catch (\Throwable $e) {
                Log::warning('AI queue auto-process failed', ['message' => $e->getMessage()]);
            }
        });
    }

    /** @param array<int, array{name: string}> $topicList */
    private function dispatchQuizGenerationJob(int $quizId, array $topicList, int $target, int $userId): void
    {
        try {
            GenerateQuizQuestionsJob::dispatch($quizId, $topicList, $target, $userId);

            return;
        } catch (\Throwable $e) {
            Log::warning('AI quiz job queue dispatch failed; running after response', [
                'quiz_id' => $quizId,
                'message' => $e->getMessage(),
            ]);
        }

        app()->terminating(function () use ($quizId, $topicList, $target, $userId) {
            try {
                GenerateQuizQuestionsJob::dispatchSync($quizId, $topicList, $target, $userId);
            } catch (\Throwable $e) {
                AiQuizGenerationProgress::fail(
                    $quizId,
                    'Could not start question generation. Check Redis and queue worker configuration.'
                );
                Log::error('AI quiz job sync fallback failed', [
                    'quiz_id' => $quizId,
                    'message' => $e->getMessage(),
                ]);
            }
        });
    }

    public function show(Request $request, string $quiz): View|Response|RedirectResponse
    {
        $quiz = Quiz::query()->find($quiz);
        if (! $quiz) {
            return redirect()->route('dashboard.quizzes.index')
                ->with('error', UserFriendlyMessages::NOT_FOUND);
        }

        $this->authorize('view', $quiz);
        $quiz->load(['course', 'classGroup', 'questions', 'questionPools']);

        // Unapproved pools for Overview tab: paginated (50 per page)
        $unapprovedPoolsQuery = $quiz->questionPools()->where('is_approved', false)->orderBy('id');
        $unapprovedPoolsTotal = $unapprovedPoolsQuery->count();
        $unapprovedPools = $unapprovedPoolsQuery->paginate(50, ['*'], 'pool_page')->withQueryString();

        // Approved questions for Overview tab: load all for live search
        $approvedQuestionsQuery = $quiz->questions()->orderBy('id');
        $approvedQuestionsTotal = $approvedQuestionsQuery->count();
        $approvedQuestions = $approvedQuestionsQuery->get();

        // Completed sessions for Sessions tab: only sessions that have a result (tally sessions = results)
        $sessionsQuery = $quiz->sessions()
            ->with(['result', 'violations'])
            ->whereNotNull('ended_at')
            ->whereHas('result')
            ->orderByDesc('ended_at');
        $sessionsPaginator = $sessionsQuery->get();

        // Stats for Sessions tab (only completed sessions with result; broken/incomplete sessions not counted)
        $completedSessions = $quiz->sessions()->whereNotNull('ended_at')->whereHas('result')->with(['result', 'violations'])->get();
        $scores = $completedSessions->pluck('result.score')->filter()->values();
        $sessionsStats = [
            'total_students' => $completedSessions->count(),
            'average_score' => $scores->isNotEmpty() ? round($scores->average(), 1) : 0,
            'highest_score' => $scores->isNotEmpty() ? $scores->max() : 0,
            'lowest_score' => $scores->isNotEmpty() ? $scores->min() : 0,
            'total_violations' => $completedSessions->sum(fn ($s) => $s->violations->count()),
            'students_with_violations' => $completedSessions->filter(fn ($s) => $s->violations->count() > 0)->count(),
        ];

        // Question analytics: per-question answered count and correct count (from completed sessions only)
        $questionStats = $this->computeQuestionStats($quiz, $completedSessions);

        $allowedDevicesEffective = $quiz->getEffectiveAllowedDevices();
        $aiGenerationProgress = AiQuizGenerationProgress::get((int) $quiz->id);
        $data = compact('quiz', 'unapprovedPools', 'unapprovedPoolsTotal', 'approvedQuestions', 'approvedQuestionsTotal', 'sessionsPaginator', 'sessionsStats', 'questionStats', 'allowedDevicesEffective', 'aiGenerationProgress');

        // Live tab/pagination: return only the tab HTML fragment for AJAX requests
        if ($request->ajax()) {
            $tab = $request->get('tab');
            if (! in_array($tab, ['overview', 'sessions', 'scores', 'analytics'], true)) {
                $tab = 'overview'; // default so pagination (e.g. questions_page=2) returns overview partial, not full page
            }
            return response()->view('admin.quizzes.partials.' . $tab, $data);
        }

        return view('admin.quizzes.show', $data);
    }

    /**
     * Compute per-question stats: answered count and correct count from completed sessions.
     *
     * @return array<int, array{question_id: int, label: string, answered: int, correct: int, percentage: float|null}>
     */
    private function computeQuestionStats(Quiz $quiz, \Illuminate\Support\Collection $completedSessions): array
    {
        $questionMap = $quiz->questions->keyBy('id');
        $aggregate = [];
        $sessionsWithAnswers = $quiz->sessions()
            ->whereNotNull('ended_at')
            ->whereIn('id', $completedSessions->pluck('id'))
            ->with('answers')
            ->get();
        foreach ($sessionsWithAnswers as $session) {
            $correctMap = $session->assigned_correct_answers ?? [];
            foreach ($session->answers as $answer) {
                $qid = $answer->question_id;
                if (! isset($aggregate[$qid])) {
                    $aggregate[$qid] = ['answered' => 0, 'correct' => 0];
                }
                $aggregate[$qid]['answered']++;
                $sessionCorrect = $correctMap[$qid] ?? $correctMap[(string) $qid] ?? null;
                if ($sessionCorrect !== null && trim((string) $answer->student_answer) === trim((string) $sessionCorrect)) {
                    $aggregate[$qid]['correct']++;
                }
            }
        }
        $order = $quiz->questions->pluck('id')->values()->all();
        $result = [];
        foreach ($order as $idx => $qid) {
            $answered = $aggregate[$qid]['answered'] ?? 0;
            $correct = $aggregate[$qid]['correct'] ?? 0;
            $question = $questionMap->get($qid);
            $textSnippet = $question ? \Illuminate\Support\Str::limit(strip_tags((string) $question->text), 60) : '';
            $label = 'Q' . ($idx + 1) . ($textSnippet ? ' — ' . $textSnippet : '');
            $result[] = [
                'question_id' => $qid,
                'label' => $label,
                'short_label' => 'Q' . ($idx + 1),
                'answered' => $answered,
                'correct' => $correct,
                'percentage' => $answered > 0 ? round(100.0 * $correct / $answered, 1) : null,
            ];
        }
        return $result;
    }

    /**
     * Show session detail: result, faces, violation logs.
     * Logs admin view of face images for audit trail.
     * Route uses {quizSession} to avoid conflict with Laravel's session.
     */
    public function showSession(string $quizId, QuizSession $quizSession): View|RedirectResponse
    {
        $quiz = $quizSession->quiz;
        if (! $quiz) {
            abort(404);
        }
        $this->authorize('view', $quiz);
        // Handle stale/migrated links by redirecting to canonical quiz/session URL.
        if ((string) $quizId !== (string) $quiz->getRouteKey()) {
            return redirect()->route('dashboard.quizzes.sessions.show', [
                'quizId' => $quiz->getRouteKey(),
                'quizSession' => $quizSession->getRouteKey(),
            ]);
        }
        $session = $quizSession;
        $session->load([
            'quiz',
            'result',
            'answers.question',
            'violations' => fn ($q) => $q->orderBy('occurred_at'),
        ]);
        $assignedIds = collect($session->assigned_question_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();
        $assignedQuestions = collect();
        if (!empty($assignedIds)) {
            $assignedQuestions = Question::whereIn('id', $assignedIds)->get()
                ->sortBy(fn ($q) => array_search((int) $q->id, $assignedIds, true))
                ->values();
        }

        $admin = $this->adminUser();
        if ($admin) {
            $now = now();
            if ($session->pre_face_image) {
                FaceImageViewLog::create([
                    'admin_id' => $admin->id,
                    'quiz_session_id' => $session->id,
                    'image_type' => FaceImageViewLog::IMAGE_TYPE_PRE,
                    'viewed_at' => $now,
                ]);
            }
            if ($session->post_face_image) {
                FaceImageViewLog::create([
                    'admin_id' => $admin->id,
                    'quiz_session_id' => $session->id,
                    'image_type' => FaceImageViewLog::IMAGE_TYPE_POST,
                    'viewed_at' => $now,
                ]);
            }
        }

        return view('admin.sessions.show', compact('quiz', 'session', 'assignedQuestions'));
    }

    /**
     * Reset IP lock for a session (allow the IP to be used again).
     */
    public function resetSessionIp(string $quizId, QuizSession $quizSession): RedirectResponse
    {
        $quiz = $quizSession->quiz;
        if (! $quiz) {
            abort(404);
        }
        $this->authorize('manageSessions', $quiz);
        // If URL quiz is stale, move to canonical URL first.
        if ((string) $quizId !== (string) $quiz->getRouteKey()) {
            return redirect()->route('dashboard.quizzes.sessions.show', [
                'quizId' => $quiz->getRouteKey(),
                'quizSession' => $quizSession->getRouteKey(),
            ])->with('info', 'Session opened via updated quiz link.');
        }
        $session = $quizSession;
        $lockedIp = trim((string) $session->ip_address);

        if ($lockedIp === '' || str_starts_with($lockedIp, 'reset-')) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.sessions.show', [$quiz, $session])
                ->with('info', 'IP lock was already reset for this session.');
        }

        // Release this IP for the whole quiz so a different student can use it immediately.
        // We reset every matching session row, not just the clicked one.
        $sessionsUsingIp = QuizSession::where('quiz_id', $quiz->id)
            ->where('ip_address', $lockedIp)
            ->get();
        $timestamp = now()->timestamp;
        $releasedCount = 0;
        foreach ($sessionsUsingIp as $lockedSession) {
            $lockedSession->update([
                'ip_address' => 'reset-' . $lockedSession->id . '-' . $timestamp,
                'session_token' => null,
            ]);
            $releasedCount++;
        }

        $message = $releasedCount > 1
            ? 'IP lock reset across multiple sessions. This network can now be used by another student.'
            : 'IP lock reset. This network can now be used by another student.';

        return redirect()->route($this->staffRoutePrefix() . '.quizzes.sessions.show', [$quiz, $session])
            ->with('success', $message);
    }

    /**
     * Release withheld result so student can view the already-computed score.
     * Keeps result/violations intact for audit; only removes hold state.
     */
    public function clearWithheldResult(string $quizId, QuizSession $quizSession): RedirectResponse
    {
        $quiz = $quizSession->quiz;
        if (! $quiz) {
            abort(404);
        }
        $this->authorize('view', $quiz);

        if ((string) $quizId !== (string) $quiz->getRouteKey()) {
            return redirect()->route('dashboard.quizzes.sessions.clear-withheld', [
                'quizId' => $quiz->getRouteKey(),
                'quizSession' => $quizSession->getRouteKey(),
            ]);
        }

        if (! $quizSession->result) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.sessions.show', [$quiz, $quizSession])
                ->with('info', 'No result found for this session.');
        }

        if (! $quizSession->isResultWithheld()) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.sessions.show', [$quiz, $quizSession])
                ->with('info', 'Result is already visible to the student.');
        }

        $quizSession->update([
            'submission_reason' => 'withheld_cleared_by_examiner',
        ]);

        try {
            app(StudentNotificationService::class)->notifyResultReleased($quizSession);
        } catch (\Throwable $e) {
            report($e);
        }

        $this->broadcastDataUpdatedSafe('dashboard');

        return redirect()->route($this->staffRoutePrefix() . '.quizzes.sessions.show', [$quiz, $quizSession])
            ->with('success', 'Result released. Student can now view the score.');
    }

    /**
     * Kill a session: delete the session and its result, allowing the student to retake the quiz.
     */
    public function killSession(string $quizId, QuizSession $quizSession): RedirectResponse
    {
        $quiz = $quizSession->quiz;
        if (! $quiz) {
            abort(404);
        }
        $this->authorize('manageSessions', $quiz);

        if ((int) $quizSession->quiz_id !== (int) $quiz->id) {
            abort(404);
        }

        $redirect = redirect()
            ->route($this->staffRoutePrefix() . '.quizzes.show', ['quiz' => $quiz, 'tab' => 'sessions']);

        try {
            app(\App\Services\QuizConcurrencyService::class)->clearLiveSession((int) $quizSession->id);

            DB::transaction(function () use ($quizSession) {
                if (Schema::hasTable('operations_exam_incidents')) {
                    DB::table('operations_exam_incidents')
                        ->where('quiz_session_id', $quizSession->id)
                        ->update(['quiz_session_id' => null]);
                }
                $quizSession->delete();
            });
        } catch (\Throwable $e) {
            report($e);

            return $redirect->with('error', UserFriendlyMessages::GENERIC);
        }

        $this->broadcastDataUpdatedSafe('dashboard');
        $this->broadcastDataUpdatedSafe('sessions');

        return $redirect->with('success', 'Session killed. The student can retake this quiz.');
    }

    /**
     * Delete completed quiz sessions within a date/time window so affected students can retake.
     * Cascades to answers/results/violations via FK constraints.
     */
    public function clearSessionsByRange(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->authorize('manageSessions', $quiz);

        $validated = $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ]);

        $from = \Illuminate\Support\Carbon::parse($validated['from']);
        $to = \Illuminate\Support\Carbon::parse($validated['to']);

        $matching = $quiz->sessions()
            ->whereNotNull('ended_at')
            ->whereBetween('ended_at', [$from, $to])
            ->get(['id', 'student_index']);

        if ($matching->isEmpty()) {
            return redirect()
                ->route($this->staffRoutePrefix() . '.quizzes.show', ['quiz' => $quiz, 'tab' => 'sessions'])
                ->with('info', 'No completed sessions found in the selected date/time range.');
        }

        $ids = $matching->pluck('id')->all();
        $deletedSessions = count($ids);
        $affectedStudents = $matching->pluck('student_index')
            ->filter()
            ->map(fn ($idx) => strtoupper(trim((string) $idx)))
            ->unique()
            ->count();

        DB::transaction(function () use ($ids) {
            QuizSession::whereIn('id', $ids)->delete();
        });

        try {
            $this->broadcastDataUpdatedSafe('quizzes');
            $this->broadcastDataUpdatedSafe('dashboard');
        } catch (\Throwable $e) {
            // ignore broadcast failures
        }

        return redirect()
            ->route($this->staffRoutePrefix() . '.quizzes.show', ['quiz' => $quiz, 'tab' => 'sessions'])
            ->with('success', 'Cleared');
    }

    /**
     * Approve a question pool item: create Question from it and mark pool as approved.
     */
    public function approvePool(Quiz $quiz, QuestionPool $pool): RedirectResponse
    {
        $this->authorize('update', $quiz);
        if ($quiz->hasStarted()) {
            return $this->redirectQuizOverview($quiz, 'error', 'Error');
        }
        if ($pool->quiz_id !== $quiz->id) {
            abort(404);
        }
        if ($pool->is_approved) {
            return $this->redirectQuizOverview($quiz, 'info', 'Already approved.');
        }
        Question::create([
            'quiz_id' => $quiz->id,
            'text' => $pool->question_text,
            'type' => QuestionTypes::normalize((string) ($pool->type ?? QuestionTypes::MCQ)),
            'options' => $pool->options ?? [],
            'correct_answer' => $pool->correct_answer,
            'topic' => $pool->topic,
            'source' => 'ai',
            'points' => 1,
            'explanation_wrong' => $pool->explanation_wrong ?? null,
            'explanation_correct' => $pool->explanation_correct ?? null,
        ]);
        $pool->update(['is_approved' => true]);
        return $this->redirectQuizOverview($quiz, 'success', 'Saved');
    }

    /**
     * Redirect to quiz overview tab with 303 See Other so refresh never resubmits the approve POST.
     */
    private function redirectQuizOverview(Quiz $quiz, string $flashKey = 'success', string $flashMessage = 'Saved'): RedirectResponse
    {
        $url = route($this->staffRoutePrefix() . '.quizzes.show', [$quiz]) . '?tab=overview';
        return redirect()->to($url)->with($flashKey, $flashMessage)->setStatusCode(303);
    }

    /**
     * Approve all unapproved question pool items for this quiz.
     */
    public function approveAllPool(Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);
        if ($quiz->hasStarted()) {
            return $this->redirectQuizOverview($quiz, 'error', 'Error');
        }
        $pools = $quiz->questionPools()->where('is_approved', false)->get();
        foreach ($pools as $pool) {
            Question::create([
                'quiz_id' => $quiz->id,
                'text' => $pool->question_text,
                'type' => QuestionTypes::normalize((string) ($pool->type ?? QuestionTypes::MCQ)),
                'options' => $pool->options ?? [],
                'correct_answer' => $pool->correct_answer,
                'topic' => $pool->topic,
                'source' => 'ai',
                'points' => 1,
                'explanation_wrong' => $pool->explanation_wrong ?? null,
                'explanation_correct' => $pool->explanation_correct ?? null,
            ]);
            $pool->update(['is_approved' => true]);
        }
        try {
            QuizBackupService::sendIfConfigured($quiz);
        } catch (\Throwable $e) {
            // Do not fail the request if digest send fails
        }
        return $this->redirectQuizOverview($quiz, 'success', 'Saved');
    }

    /**
     * Publish quiz: make it visible on the student landing page.
     */
    public function publish(Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);
        if (!$quiz->hasEnoughApprovedQuestions()) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('error', UserFriendlyMessages::GENERIC);
        }
        $quiz->update(['is_published' => true, 'status' => Quiz::STATUS_PUBLISHED]);
        $this->broadcastDataUpdatedSafe('quizzes');
        try {
            $quiz->load('course');
            app(StudentNotificationService::class)->notifyQuizPublished($quiz);
        } catch (\Throwable $e) {
            report($e);
        }
        try {
            QuizBackupService::sendIfConfigured($quiz);
        } catch (\Throwable $e) {
            // Do not fail the request if digest send fails
        }
        return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('success', 'Published');
    }

    /**
     * Unpublish quiz: remove it from the student landing page.
     * Only shown when quiz is published but not yet "open" (e.g. future starts_at).
     */
    public function unpublish(Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);
        $quiz->update(['is_published' => false, 'status' => Quiz::STATUS_DRAFT]);
        $this->broadcastDataUpdatedSafe('quizzes');
        return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('success', 'Unpublished');
    }

    /**
     * End quiz: set ends_at to now so students can no longer start or continue.
     * Shown when quiz is published and the quiz window is open (starts_at passed or null).
     */
    public function endQuiz(Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);
        $quiz->update(['ends_at' => now(), 'is_active' => false]);
        $this->broadcastDataUpdatedSafe('quizzes');
        return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('success', 'Ended');
    }

    /**
     * Extend quiz time while quiz is ongoing.
     * Adds additional minutes to the quiz duration.
     */
    public function extendTime(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);
        
        // Only allow extending time if quiz has started (has active sessions)
        if (!$quiz->hasStarted()) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)
                ->with('error', UserFriendlyMessages::GENERIC);
        }
        
        // Check if quiz has ended
        if ($quiz->hasEnded()) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)
                ->with('error', UserFriendlyMessages::GENERIC);
        }
        
        $request->validate([
            'additional_minutes' => 'required|integer|min:1|max:120',
        ]);
        
        $additionalMinutes = (int) $request->input('additional_minutes');
        $newDuration = $quiz->duration_minutes + $additionalMinutes;
        
        // Cap at reasonable maximum (e.g., 600 minutes = 10 hours)
        if ($newDuration > 600) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)
                ->with('error', UserFriendlyMessages::GENERIC);
        }
        
        $quiz->update(['duration_minutes' => $newDuration]);
        
        // Broadcast update so students' timers refresh
        $this->broadcastDataUpdatedSafe('quizzes');
        
        return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)
            ->with('success', 'Extended');
    }

    /**
     * Show edit form for an unapproved pool item.
     */
    public function editPool(Quiz $quiz, QuestionPool $pool): View|RedirectResponse
    {
        $this->authorize('view', $quiz);
        if ($quiz->hasStarted()) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('error', UserFriendlyMessages::GENERIC);
        }
        if ($pool->quiz_id !== $quiz->id) {
            abort(404);
        }
        if ($pool->is_approved) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('info', 'Already approved.');
        }
        return view('admin.quizzes.edit-pool', compact('quiz', 'pool'));
    }

    /**
     * Update an unapproved pool item.
     */
    public function updatePool(Request $request, Quiz $quiz, QuestionPool $pool): RedirectResponse
    {
        $this->authorize('update', $quiz);
        if ($quiz->hasStarted()) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('error', UserFriendlyMessages::GENERIC);
        }
        if ($pool->quiz_id !== $quiz->id || $pool->is_approved) {
            abort(404);
        }
        $request->validate([
            'question_text' => 'required|string|max:65535',
            'correct_answer' => 'required|string|in:A,B,C,D',
            'topic' => 'nullable|string|max:255',
            'option_a' => 'required|string|max:1000',
            'option_b' => 'required|string|max:1000',
            'option_c' => 'required|string|max:1000',
            'option_d' => 'required|string|max:1000',
        ]);
        $pool->update([
            'question_text' => $request->question_text,
            'options' => [
                ['key' => 'A', 'text' => $request->option_a],
                ['key' => 'B', 'text' => $request->option_b],
                ['key' => 'C', 'text' => $request->option_c],
                ['key' => 'D', 'text' => $request->option_d],
            ],
            'correct_answer' => $request->correct_answer,
            'topic' => $request->filled('topic') ? $request->topic : null,
        ]);
        return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('success', 'Saved');
    }

    /**
     * Reject (delete) an unapproved pool item.
     */
    public function rejectPool(Quiz $quiz, QuestionPool $pool): RedirectResponse
    {
        $this->authorize('update', $quiz);
        if ($quiz->hasStarted()) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('error', UserFriendlyMessages::GENERIC);
        }
        if ($pool->quiz_id !== $quiz->id) {
            abort(404);
        }
        if ($pool->is_approved) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('info', 'Already approved.');
        }
        $pool->delete();
        return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('success', 'Removed');
    }

    /**
     * Show edit form for an approved question.
     */
    public function editQuestion(Quiz $quiz, Question $question): View|RedirectResponse
    {
        $this->authorize('view', $quiz);
        if ($quiz->hasStarted()) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('error', UserFriendlyMessages::GENERIC);
        }
        if ($question->quiz_id !== $quiz->id) {
            abort(404);
        }
        return view('admin.quizzes.edit-question', compact('quiz', 'question'));
    }

    /**
     * Update an approved question.
     */
    public function updateQuestion(Request $request, Quiz $quiz, Question $question): RedirectResponse
    {
        $this->authorize('update', $quiz);
        if ($quiz->hasStarted()) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('error', UserFriendlyMessages::GENERIC);
        }
        if ($question->quiz_id !== $quiz->id) {
            abort(404);
        }
        $request->validate([
            'text' => 'required|string|max:65535',
            'correct_answer' => 'required|string|in:A,B,C,D',
            'topic' => 'nullable|string|max:255',
            'option_a' => 'required|string|max:1000',
            'option_b' => 'required|string|max:1000',
            'option_c' => 'required|string|max:1000',
            'option_d' => 'required|string|max:1000',
        ]);
        $options = [
            ['key' => 'A', 'text' => $request->option_a],
            ['key' => 'B', 'text' => $request->option_b],
            ['key' => 'C', 'text' => $request->option_c],
            ['key' => 'D', 'text' => $request->option_d],
        ];
        $question->update([
            'text' => $request->text,
            'options' => $options,
            'correct_answer' => $request->correct_answer,
            'topic' => $request->filled('topic') ? $request->topic : null,
        ]);
        return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('success', 'Saved');
    }

    /**
     * Delete a quiz (and its sessions, questions, pools via cascade).
     * Only allowed when the quiz has not been started by any student.
     */
    public function destroy(Quiz $quiz): RedirectResponse
    {
        $this->authorize('delete', $quiz);
        if ($quiz->hasStarted()) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('error', UserFriendlyMessages::GENERIC);
        }
        $title = $quiz->title;
        if ($quiz->script_public_id && ! str_starts_with((string) $quiz->script_public_id, 'http')) {
            LocalUploadService::deletePublicPath($quiz->script_public_id);
        }
        $quiz->delete();
        try {
            $this->broadcastDataUpdatedSafe('quizzes');
        } catch (\Exception $e) {
            // Ignore broadcast errors
        }
        return redirect()->route($this->staffRoutePrefix() . '.quizzes.index')->with('success', 'Deleted');
    }

    /**
     * Delete an approved question. Blocked if any active (non-ended) session has this question in its snapshot.
     */
    public function destroyQuestion(Quiz $quiz, Question $question): RedirectResponse
    {
        $this->authorize('update', $quiz);
        if ($question->quiz_id !== $quiz->id) {
            abort(404);
        }
        $assignedToActiveSession = QuizSession::where('quiz_id', $quiz->id)
            ->whereNull('ended_at')
            ->whereJsonContains('assigned_question_ids', (int) $question->id)
            ->exists();
        if ($assignedToActiveSession) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('error', UserFriendlyMessages::GENERIC);
        }
        if ($quiz->hasStarted()) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('error', UserFriendlyMessages::GENERIC);
        }
        $question->delete();
        return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('success', 'Removed');
    }

    public function edit(Quiz $quiz): View|RedirectResponse
    {
        $this->authorize('update', $quiz);
        if ($quiz->hasStarted()) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)
                ->with('error', 'This quiz cannot be edited because at least one student has already started it.');
        }
        $quiz->load('classGroup.courses', 'course');
        $courses = $quiz->classGroup
            ? $quiz->classGroup->courses()->orderBy('name')->get()
            : ($quiz->course_id ? Course::where('id', $quiz->course_id)->orderBy('name')->get() : collect());
        if ($quiz->course_id && ! $courses->contains('id', $quiz->course_id)) {
            $currentCourse = Course::find($quiz->course_id);
            if ($currentCourse) {
                $courses = $courses->push($currentCourse)->sortBy('name')->values();
            }
        }
        $user = $this->adminUser();
        $aiApiAvailable = app(AiQuestionService::class)->hasApiKey();
        $aiGenerationEnabled = AiQuestionService::isGenerationEnabled();
        $aiTokenStatus = $user ? app(AiQuizTokenService::class)->getStatus($user) : null;

        return view('admin.quizzes.edit', compact('quiz', 'courses', 'aiApiAvailable', 'aiGenerationEnabled', 'aiTokenStatus'));
    }

    public function update(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);
        if ($quiz->hasStarted()) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)
                ->with('error', 'This quiz cannot be edited because at least one student has already started it.');
        }

        try {
        $request->validate([
            'title' => 'required|string|max:255',
            'exam_type' => 'nullable|in:quiz,midsem,end_of_semester',
            'course_id' => 'required|exists:courses,id',
            'number_of_questions' => 'required|integer|min:1|max:250',
            'questions_per_student' => 'required|integer|min:1|max:250',
            'duration_minutes' => 'required|integer|min:1|max:300',
            'topics' => 'nullable|string',
            'source_script' => 'nullable|string|max:100000',
            'source_file' => 'nullable|file|mimes:txt,pdf,docx|max:10240',
            'is_active' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'result_visibility' => 'nullable|in:score_only,full_review_after_end,disabled',
            'allowed_devices' => 'nullable|in:desktop,mobile,both',
            'include_mcq' => 'nullable|boolean',
            'include_true_false' => 'nullable|boolean',
            'include_fill_in' => 'nullable|boolean',
            'mcq_count' => 'nullable|integer|min:0|max:250',
            'true_false_count' => 'nullable|integer|min:0|max:250',
            'fill_in_count' => 'nullable|integer|min:0|max:250',
        ]);
        $typeCountsOrError = $this->questionTypeCountsOrError($request);
        if (is_string($typeCountsOrError)) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.edit', $quiz)
                ->withInput()
                ->with('error', $typeCountsOrError);
        }
        $typeCounts = $typeCountsOrError;
        $poolTotal = QuestionTypes::total($typeCounts);
        $requestCourseId = (int) $request->course_id;
        $classGroup = $quiz->classGroup;
        if ($classGroup && ! $classGroup->courses()->where('courses.id', $requestCourseId)->exists()) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.edit', $quiz)
                ->withInput()
                ->with('error', UserFriendlyMessages::GENERIC);
        }
        $topics = $request->topics;
        if (is_string($topics) && $topics !== '') {
            $topics = array_map('trim', explode(',', $topics));
            $topics = array_map(fn ($t) => ['name' => $t], $topics);
        } else {
            $topics = null;
        }
        $scriptUrl = $quiz->script_url;
        $scriptPublicId = $quiz->script_public_id;
        $scriptText = $quiz->script_text;
        if ($request->filled('source_script') && trim($request->source_script) !== '') {
            $scriptText = trim($request->source_script);
            $scriptUrl = null;
            $scriptPublicId = null;
        } elseif ($request->hasFile('source_file')) {
            $file = $request->file('source_file');
            $uploaded = LocalUploadService::storePublicFile($file, 'quiz-scripts');
            if ($uploaded) {
                $scriptUrl = $uploaded['url'];
                $scriptPublicId = $uploaded['path'];
                $scriptText = app(DocumentTextExtractor::class)->extract($file);
            } else {
                return redirect()->route($this->staffRoutePrefix() . '.quizzes.edit', $quiz)
                    ->withInput()
                    ->with('error', 'Failed');
            }
        }
        $updateData = [
            'title' => $request->title,
            'exam_type' => $request->input('exam_type') ?: null,
            'course_id' => $request->course_id,
            'number_of_questions' => $poolTotal,
            'question_type_counts' => $typeCounts,
            'duration_minutes' => $request->duration_minutes,
            'topics' => is_array($topics) ? json_encode($topics) : null,
            'script_url' => $scriptUrl,
            'script_public_id' => $scriptPublicId,
            'script_text' => $scriptText,
            'is_active' => $request->boolean('is_active', true),
            'starts_at' => $request->filled('starts_at') ? $request->starts_at : null,
            'ends_at' => $request->filled('ends_at') ? $request->ends_at : null,
            'result_visibility' => $request->input('result_visibility', $quiz->result_visibility ?? Quiz::RESULT_VISIBILITY_FULL_REVIEW_AFTER_END),
        ];
        if (Schema::hasColumn('quizzes', 'questions_per_student')) {
            $updateData['questions_per_student'] = (int) $request->questions_per_student;
        }
        if (Schema::hasColumn('quizzes', 'allowed_devices')) {
            $reqAllowed = $request->input('allowed_devices');
            $validDevices = [Quiz::ALLOWED_DEVICES_DESKTOP, Quiz::ALLOWED_DEVICES_MOBILE, Quiz::ALLOWED_DEVICES_BOTH];
            $updateData['allowed_devices'] = in_array($reqAllowed, $validDevices, true)
                ? $reqAllowed
                : ($quiz->getAttribute('allowed_devices') ?? Quiz::ALLOWED_DEVICES_DESKTOP);
        }
        $quiz->updateFromAttributes($updateData);
        $this->broadcastDataUpdatedSafe('quizzes');

        return redirect()->route($this->staffRoutePrefix() . '.quizzes.edit', $quiz)->with('success', 'Saved. On the quiz overview, use "Generate questions with AI" to add more questions.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Quiz update failed', [
                'quiz_id' => $quiz->id,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            $userMessage = 'Could not update quiz. ';
            if ($e instanceof \Illuminate\Database\QueryException && str_contains($e->getMessage(), 'Unknown column')) {
                $userMessage .= 'Database schema is outdated — on the server run: php artisan migrate --force';
            } elseif (config('app.debug')) {
                $userMessage .= $e->getMessage();
            } else {
                $userMessage .= 'Please try again or contact support if the problem persists.';
            }

            return redirect()->route($this->staffRoutePrefix() . '.quizzes.edit', $quiz)
                ->withInput()
                ->with('error', $userMessage);
        }
    }

    /**
     * Synchronous single-batch AI generation (5 questions per call).
     * Called repeatedly from the browser via fetch() until target is reached.
     * Works on shared hosting with no queue worker.
     */
    public function generateBatch(Request $request, Quiz $quiz): \Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $quiz);
        $user = $this->adminUser();
        if (! $user) {
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        $validated = $request->validate([
            'target'     => 'required|integer|min:1|max:250',
            'topics'     => 'nullable|string|max:1000',
            'first_call' => 'nullable|boolean',
        ]);

        $aiService = app(AiQuestionService::class);
        if (! $aiService->hasApiKey()) {
            return response()->json(['error' => 'AI generation is unavailable. Add a DeepSeek API key in Dashboard → Settings → AI.'], 422);
        }

        $target      = (int) $validated['target'];
        $isFirstCall = (bool) ($validated['first_call'] ?? false);

        $currentCount = $quiz->questions()->count();
        $poolCount    = $quiz->questionPools()->count();
        $totalSoFar   = $currentCount + $poolCount;
        $remaining    = max(0, $target - $totalSoFar);

        $tokenService = app(AiQuizTokenService::class);
        $tokenPendingKey = 'quiz_ai_token_pending:' . $quiz->id . ':' . $user->id;

        // On first call: clear stale caches so the latest API key and progress are used.
        if ($isFirstCall) {
            \Illuminate\Support\Facades\Cache::forget('quiz_ai_progress:' . $quiz->id);
            \Illuminate\Support\Facades\Cache::forget('setting:' . \App\Models\Setting::KEY_DEEPSEEK_API);
            \Illuminate\Support\Facades\Cache::forget($tokenPendingKey);

            if ($remaining > 0 && ! $tokenService->canUse($user)) {
                $status = $tokenService->getStatus($user);

                return response()->json(['error' => $status['message'] ?? 'No AI quiz tokens left.'], 422);
            }

            if ($remaining > 0) {
                \Illuminate\Support\Facades\Cache::put($tokenPendingKey, true, now()->addHours(4));
            }
        }

        if ($remaining <= 0) {
            \Illuminate\Support\Facades\Cache::forget($tokenPendingKey);

            return response()->json([
                'generated'      => 0,
                'questions_count' => $currentCount,
                'pool_count'      => $poolCount,
                'target'          => $target,
                'done'            => true,
            ]);
        }

        $batchSize = min(5, $remaining);

        $topicsRaw = preg_split('/[\s,]+/', (string) ($validated['topics'] ?? ''), -1, PREG_SPLIT_NO_EMPTY);
        $topics    = array_map(fn ($t) => ['name' => trim($t)], array_filter(array_map('trim', $topicsRaw)));
        if (empty($topics)) {
            $topics = [['name' => 'General knowledge']];
        }

        $sourceText = (string) ($quiz->script_text ?? '');
        if (mb_strlen($sourceText) > 50000) {
            $sourceText = mb_substr($sourceText, 0, 50000) . "\n[... truncated ...]";
        }

        @set_time_limit(300);
        $ids       = $aiService->generatePoolAndStore($quiz, $topics, $batchSize, $sourceText ?: null);
        $generated = count($ids);

        if ($generated > 0 && \Illuminate\Support\Facades\Cache::get($tokenPendingKey)) {
            $tokenService->consume($user);
            \Illuminate\Support\Facades\Cache::forget($tokenPendingKey);
        }

        // If the AI service returned nothing (API failure, bad key, etc.) return 200 with error
        // so the browser always gets JSON (no HTML error page) and the JS can show the message.
        if ($generated === 0) {
            $apiError = $aiService->getLastApiError();
            $message = $apiError
                ? 'AI returned 0 questions. ' . $apiError
                : 'AI returned 0 questions. Check the DeepSeek API key in Dashboard → Settings → AI and that your account has balance.';
            return response()->json([
                'error' => $message,
                'generated' => 0,
                'questions_count' => $currentCount,
                'pool_count' => $poolCount,
                'total_so_far' => $totalSoFar,
                'target' => $target,
                'done' => false,
            ]);
        }

        $newCount   = $quiz->questions()->count();
        $newPool    = $quiz->questionPools()->count();
        $newTotal   = $newCount + $newPool;
        $done       = $newTotal >= $target;

        return response()->json([
            'generated'       => $generated,
            'questions_count'  => $newCount,
            'pool_count'       => $newPool,
            'total_so_far'     => $newTotal,
            'target'           => $target,
            'done'             => $done,
        ]);
    }

    /**
     * Batch AI generation for quiz overview (DeepSeek).
     */
    public function generateBatchAi(Request $request, Quiz $quiz): \Illuminate\Http\JsonResponse
    {
        return $this->generateBatch($request, $quiz);
    }

    /** @deprecated Use generateBatchAi — kept for old route name. */
    public function generateBatchGemini(Request $request, Quiz $quiz): \Illuminate\Http\JsonResponse
    {
        return $this->generateBatchAi($request, $quiz);
    }

    /**
     * Show scores page: all students who took the quiz with their scores and violations.
     */
    public function scores(Quiz $quiz): View
    {
        $this->authorize('view', $quiz);
        
        // Load only sessions that have a result (sessions count = results count; incomplete/broken sessions excluded)
        $sessions = $quiz->sessions()
            ->with(['result', 'violations'])
            ->whereNotNull('ended_at')
            ->whereHas('result')
            ->orderByDesc('ended_at')
            ->get();
        
        // Session count = result count (every listed session has a result)
        $totalStudents = $sessions->count();
        $completedWithResults = $totalStudents;
        
        $scores = $sessions->pluck('result.score')->filter()->values();
        $averageScore = $scores->isNotEmpty() ? round($scores->average(), 1) : 0;
        $highestScore = $scores->isNotEmpty() ? $scores->max() : 0;
        $lowestScore = $scores->isNotEmpty() ? $scores->min() : 0;
        
        $totalViolations = $sessions->sum(fn($s) => $s->violations->count());
        $studentsWithViolations = $sessions->filter(fn($s) => $s->violations->count() > 0)->count();
        
        $stats = [
            'total_students' => $totalStudents,
            'completed_with_results' => $completedWithResults,
            'average_score' => $averageScore,
            'highest_score' => $highestScore,
            'lowest_score' => $lowestScore,
            'total_violations' => $totalViolations,
            'students_with_violations' => $studentsWithViolations,
        ];
        
        return view('admin.quizzes.scores', compact('quiz', 'sessions', 'stats'));
    }

    /**
     * Export quiz results (scores) as PDF. Preview (inline) or download.
     */
    public function exportScoresPdf(Quiz $quiz, Request $request): Response
    {
        $this->authorize('view', $quiz);
        $quiz->load(['classGroup.level', 'course', 'academicClass']);

        $sessions = $quiz->sessions()
            ->with(['result', 'violations'])
            ->whereNotNull('ended_at')
            ->whereHas('result')
            ->orderBy('student_index')
            ->get();

        $lecturer = $this->adminUser();
        $lecturerName = $lecturer ? ($lecturer->name ?: $lecturer->username) : '—';
        $courseName = '—';
        if ($quiz->course) {
            $code = trim($quiz->course->code ?? '');
            $name = trim($quiz->course->name ?? '');
            $courseName = $code && $name ? $code . ' – ' . $name : ($name ?: $code ?: '—');
        }
        $examTypeLabel = $quiz->getExamTypeLabel();
        $reportDate = $quiz->ended_at ? $quiz->ended_at->format('F j, Y') : now()->format('F j, Y');
        $institutionName = Setting::getValue(Setting::KEY_INSTITUTION_NAME, '');
        $logoPath = Setting::getValue(Setting::KEY_INSTITUTION_LOGO, '');
        $institutionLogoPath = null;
        if ($logoPath) {
            if (str_starts_with($logoPath, 'http')) {
                try {
                    $response = \Illuminate\Support\Facades\Http::timeout(10)->get($logoPath);
                    if ($response->successful()) {
                        $body = $response->body();
                        $mime = $response->header('Content-Type') ?: 'image/png';
                        $institutionLogoPath = 'data:' . (explode(';', $mime)[0] ?: 'image/png') . ';base64,' . base64_encode($body);
                    }
                } catch (\Throwable $e) {
                    // omit logo on fetch failure
                }
            } else {
                $fullPath = storage_path('app/public/' . $logoPath);
                if (file_exists($fullPath)) {
                    $mime = @mime_content_type($fullPath) ?: 'image/png';
                    $institutionLogoPath = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fullPath));
                }
            }
        }

        $classGroupName = $quiz->classGroup
            ? ($quiz->classGroup->display_name ?: $quiz->classGroup->name)
            : ($quiz->academicClass ? $quiz->academicClass->display_label : '—');
        $pdf = Pdf::loadView('admin.quizzes.scores-export-pdf', [
            'quiz' => $quiz,
            'sessions' => $sessions,
            'lecturerName' => $lecturerName,
            'courseName' => $courseName,
            'classGroupName' => $classGroupName,
            'examTypeLabel' => $examTypeLabel,
            'reportDate' => $reportDate,
            'institutionName' => $institutionName,
            'institutionLogoPath' => $institutionLogoPath,
        ])->setPaper('a4', 'portrait')->setWarnings(false);

        $groupSlug = \Illuminate\Support\Str::slug($classGroupName ?: 'group');
        $courseSlug = \Illuminate\Support\Str::slug($courseName ?: 'course');
        $dateStr = now()->format('Y-m-d');
        $filename = $groupSlug . '-' . $courseSlug . '-' . $dateStr . '.pdf';

        if (request()->routeIs('*scores.export.pdf.preview')) {
            return $pdf->stream($filename);
        }
        return $pdf->download($filename);
    }

    /**
     * Export question analytics as PDF (preview or download).
     */
    public function exportAnalyticsPdf(Quiz $quiz, Request $request): Response
    {
        $this->authorize('view', $quiz);
        $quiz->load(['classGroup', 'course', 'questions', 'academicClass']);
        $completedSessions = $quiz->sessions()->whereNotNull('ended_at')->whereHas('result')->with(['result', 'violations'])->get();
        $questionStats = $this->computeQuestionStats($quiz, $completedSessions);

        $courseName = '—';
        if ($quiz->course) {
            $code = trim($quiz->course->code ?? '');
            $name = trim($quiz->course->name ?? '');
            $courseName = $code && $name ? $code . ' – ' . $name : ($name ?: $code ?: '—');
        }
        $reportDate = $quiz->ended_at ? $quiz->ended_at->format('F j, Y') : now()->format('F j, Y');
        $institutionName = Setting::getValue(Setting::KEY_INSTITUTION_NAME, '');
        $classGroupName = $quiz->classGroup ? $quiz->classGroup->name : ($quiz->academicClass ? $quiz->academicClass->display_label : '—');
        $logoPath = Setting::getValue(Setting::KEY_INSTITUTION_LOGO, '');
        $institutionLogoPath = null;
        if ($logoPath && ! str_starts_with($logoPath, 'http')) {
            $fullPath = storage_path('app/public/' . $logoPath);
            if (file_exists($fullPath)) {
                $mime = @mime_content_type($fullPath) ?: 'image/png';
                $institutionLogoPath = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fullPath));
            }
        }

        $pdf = Pdf::loadView('admin.quizzes.analytics-export-pdf', [
            'quiz' => $quiz,
            'questionStats' => $questionStats,
            'courseName' => $courseName,
            'reportDate' => $reportDate,
            'institutionName' => $institutionName,
            'classGroupName' => $classGroupName,
            'institutionLogoPath' => $institutionLogoPath,
        ])->setPaper('a4', 'portrait')->setWarnings(false);

        $filename = \Illuminate\Support\Str::slug($classGroupName ?: 'group') . '-' . \Illuminate\Support\Str::slug($quiz->title) . '-analytics-' . now()->format('Y-m-d') . '.pdf';
        if (request()->routeIs('*analytics.export.pdf.preview')) {
            return $pdf->stream($filename);
        }
        return $pdf->download($filename);
    }

    /**
     * Append one MCQ/short item to TXT export buffers (used for Question and QuestionPool rows).
     *
     * @param  array<int, string>  $content
     * @param  array<int, string>  $answerKey
     */
    private function appendQuizQuestionTextBlock(array &$content, array &$answerKey, int $displayNumber, string $stem, ?array $options, ?string $correctAnswer): void
    {
        $content[] = $displayNumber.'. '.$stem;
        if ($options && is_array($options) && count($options) > 0) {
            foreach ($options as $option) {
                if (isset($option['key'], $option['text'])) {
                    $isCorrect = $correctAnswer !== null && $correctAnswer !== '' && $option['key'] === $correctAnswer;
                    $marker = $isCorrect ? ' ***' : '';
                    $content[] = '   '.$option['key'].'. '.$option['text'].$marker;
                }
            }
        } else {
            if ($correctAnswer) {
                $content[] = '   Answer: '.$correctAnswer;
            }
        }
        if ($correctAnswer) {
            $answerKey[] = $displayNumber.'. '.$correctAnswer;
        } else {
            $answerKey[] = $displayNumber.'. (No answer specified)';
        }
        $content[] = '';
    }

    /**
     * Export quiz questions as TXT in exam format.
     */
    public function exportQuestionsTxt(Quiz $quiz): Response
    {
        $this->authorize('view', $quiz);
        $quiz->load(['course', 'classGroup', 'questions', 'academicClass']);
        
        $questions = $quiz->questions()->orderBy('id')->get();
        
        $lecturer = $this->adminUser();
        $lecturerName = $lecturer ? ($lecturer->name ?: $lecturer->username) : '—';
        
        $courseName = '—';
        $courseCode = '—';
        if ($quiz->course) {
            $courseCode = trim($quiz->course->code ?? '');
            $courseName = trim($quiz->course->name ?? '');
        }
        
        // Format date properly - use ends_at if available, otherwise starts_at, otherwise current date
        if ($quiz->ends_at) {
            $examDate = $quiz->ends_at->format('F j, Y');
        } elseif ($quiz->starts_at) {
            $examDate = $quiz->starts_at->format('F j, Y');
        } else {
            $examDate = now()->format('F j, Y');
        }
        
        // Format duration properly - show as MINUTES if less than 60, otherwise HOURS
        $durationMinutes = $quiz->duration_minutes ?? 120;
        if ($durationMinutes < 60) {
            $duration = $durationMinutes . ' MINUTE' . ($durationMinutes > 1 ? 'S' : '');
        } else {
            $hours = floor($durationMinutes / 60);
            $minutes = $durationMinutes % 60;
            if ($hours > 0 && $minutes > 0) {
                $duration = $hours . ' HOUR' . ($hours > 1 ? 'S' : '') . ' ' . $minutes . ' MINUTE' . ($minutes > 1 ? 'S' : '');
            } elseif ($hours > 0) {
                $duration = $hours . ' HOUR' . ($hours > 1 ? 'S' : '');
            } else {
                $duration = $minutes . ' MINUTE' . ($minutes > 1 ? 'S' : '');
            }
        }
        
        $institutionName = Setting::getValue(Setting::KEY_INSTITUTION_NAME, 'TAKORADI TECHNICAL UNIVERSITY');
        
        $classGroupName = $quiz->classGroup ? $quiz->classGroup->name : ($quiz->academicClass ? $quiz->academicClass->display_label : '—');
        $programme = $classGroupName !== '—' ? strtoupper($classGroupName) : '—';
        
        // Get exam year (current year / next year format)
        $currentYear = now()->format('Y');
        $nextYear = now()->addYear()->format('y');
        $examYear = $currentYear . '/' . $nextYear;
        
        try {
            // Build text content
            $content = [];
            
            // Header
            $content[] = str_pad('', 80, ' ', STR_PAD_BOTH);
            $content[] = strtoupper($institutionName);
            $content[] = 'FACULTY OF APPLIED ARTS AND TECHNOLOGY';
            $content[] = 'DEPARTMENT OF COMPUTER SCIENCE';
            $content[] = 'END OF FIRST SEMESTER EXAMINATIONS, ' . $examYear;
            $content[] = 'PROGRAMME: ' . $programme;
            $content[] = '';
            
            // Course info
            $content[] = 'COURSE TITLE: ' . strtoupper($courseName) . str_pad('COURSE CODE: ' . strtoupper($courseCode), 80 - strlen('COURSE TITLE: ' . strtoupper($courseName)), ' ', STR_PAD_LEFT);
            $content[] = 'DATE: ' . strtoupper($examDate) . str_pad('DURATION: ' . strtoupper($duration), 80 - strlen('DATE: ' . strtoupper($examDate)), ' ', STR_PAD_LEFT);
            $content[] = '';
            
            // Instructions
            $content[] = 'INSTRUCTIONS:';
            $content[] = 'Answer all questions. Each question carries equal marks. Write clearly and legibly.';
            $content[] = '';
            
            // Questions
            $answerKey = [];
            foreach ($questions as $idx => $question) {
                $this->appendQuizQuestionTextBlock(
                    $content,
                    $answerKey,
                    $idx + 1,
                    (string) $question->text,
                    is_array($question->options) ? $question->options : null,
                    $question->correct_answer
                );
            }
            
            // Answer Key Section
            $content[] = '';
            $content[] = str_repeat('=', 80);
            $content[] = 'ANSWER KEY';
            $content[] = str_repeat('=', 80);
            $content[] = '';
            foreach ($answerKey as $answer) {
                $content[] = $answer;
            }
            
            // Footer
            $content[] = '';
            $content[] = str_pad('Generated ' . now()->format('M d, Y H:i') . ' — QuizSnap', 80, ' ', STR_PAD_BOTH);
            
            // Join content with newlines
            $textContent = implode("\n", $content);
            
            // Create temporary file with .txt extension
            $tempDir = sys_get_temp_dir();
            $tempFile = $tempDir . DIRECTORY_SEPARATOR . 'questions_' . uniqid() . '.txt';
            
            // Write content to file
            file_put_contents($tempFile, $textContent);
            
            // Verify file was created
            if (!file_exists($tempFile)) {
                throw new \Exception('Failed to create TXT file');
            }
            
            // Generate filename with class name
            $classSlug = $classGroupName !== '—' ? \Illuminate\Support\Str::slug($classGroupName) : 'class';
            $courseSlug = \Illuminate\Support\Str::slug($courseName ?: 'course');
            $dateStr = now()->format('Y-m-d');
            $filename = $classSlug . '-' . $courseSlug . '-questions-' . $dateStr . '.txt';
            
            // Return download with proper headers to force .txt download
            return response()->download($tempFile, $filename, [
                'Content-Type' => 'text/plain',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ])->deleteFileAfterSend(true);
            
        } catch (\Throwable $e) {
            // Clean up temp file if it exists
            if (isset($tempFile) && file_exists($tempFile)) {
                @unlink($tempFile);
            }
            abort(500, UserFriendlyMessages::DOWNLOAD_UNAVAILABLE);
        }
    }

    /**
     * Normalize question text so we can match pool rows to Question rows without duplicating.
     */
    private function normalizeQuestionTextForDedup(?string $text): string
    {
        $t = trim(strip_tags((string) $text));
        $t = preg_replace('/\s+/u', ' ', $t) ?? $t;

        return mb_strtolower($t);
    }

    /**
     * Export the full question pool: every row in question_pools (pending + approved) is the canonical
     * generator pool, plus any Question rows that were never stored as a pool (legacy direct AI).
     * Random selection for students still uses only the approved bank (questions table); each student gets a subset.
     */
    public function exportFullQuestionPoolTxt(Quiz $quiz): Response
    {
        $this->authorize('view', $quiz);
        $quiz->load(['course', 'classGroup', 'questions', 'academicClass']);

        $allPools = $quiz->questionPools()->orderBy('id')->get();
        $approvedBank = $quiz->questions()->orderBy('id')->get();

        $poolTextHashes = [];
        foreach ($allPools as $pool) {
            $poolTextHashes[hash('sha256', $this->normalizeQuestionTextForDedup((string) $pool->question_text))] = true;
        }

        $courseName = '—';
        $courseCode = '—';
        if ($quiz->course) {
            $courseCode = trim($quiz->course->code ?? '');
            $courseName = trim($quiz->course->name ?? '');
        }

        if ($quiz->ends_at) {
            $examDate = $quiz->ends_at->format('F j, Y');
        } elseif ($quiz->starts_at) {
            $examDate = $quiz->starts_at->format('F j, Y');
        } else {
            $examDate = now()->format('F j, Y');
        }

        $durationMinutes = $quiz->duration_minutes ?? 120;
        if ($durationMinutes < 60) {
            $duration = $durationMinutes.' MINUTE'.($durationMinutes > 1 ? 'S' : '');
        } else {
            $hours = floor($durationMinutes / 60);
            $minutes = $durationMinutes % 60;
            if ($hours > 0 && $minutes > 0) {
                $duration = $hours.' HOUR'.($hours > 1 ? 'S' : '').' '.$minutes.' MINUTE'.($minutes > 1 ? 'S' : '');
            } elseif ($hours > 0) {
                $duration = $hours.' HOUR'.($hours > 1 ? 'S' : '');
            } else {
                $duration = $minutes.' MINUTE'.($minutes > 1 ? 'S' : '');
            }
        }

        $institutionName = Setting::getValue(Setting::KEY_INSTITUTION_NAME, 'TAKORADI TECHNICAL UNIVERSITY');
        $classGroupName = $quiz->classGroup ? $quiz->classGroup->name : ($quiz->academicClass ? $quiz->academicClass->display_label : '—');
        $programme = $classGroupName !== '—' ? strtoupper($classGroupName) : '—';
        $currentYear = now()->format('Y');
        $nextYear = now()->addYear()->format('y');
        $examYear = $currentYear.'/'.$nextYear;
        $perStudent = $quiz->getQuestionsPerStudent();
        $pendingPoolCount = $allPools->where('is_approved', false)->count();
        $approvedPoolCount = $allPools->where('is_approved', true)->count();

        try {
            $content = [];
            $content[] = str_pad('', 80, ' ', STR_PAD_BOTH);
            $content[] = strtoupper($institutionName);
            $content[] = 'FACULTY OF APPLIED ARTS AND TECHNOLOGY';
            $content[] = 'DEPARTMENT OF COMPUTER SCIENCE';
            $content[] = 'END OF FIRST SEMESTER EXAMINATIONS, '.$examYear;
            $content[] = 'PROGRAMME: '.$programme;
            $content[] = '';
            $content[] = 'COURSE TITLE: '.strtoupper($courseName).str_pad('COURSE CODE: '.strtoupper($courseCode), 80 - strlen('COURSE TITLE: '.strtoupper($courseName)), ' ', STR_PAD_LEFT);
            $content[] = 'DATE: '.strtoupper($examDate).str_pad('DURATION: '.strtoupper($duration), 80 - strlen('DATE: '.strtoupper($examDate)), ' ', STR_PAD_LEFT);
            $content[] = '';
            $content[] = 'FULL QUESTION POOL EXPORT (all question_pools rows + bank-only questions)';
            $content[] = 'Questions per student (random draw from approved bank only): '.$perStudent;
            $content[] = 'question_pools table: '.$allPools->count().' row(s) (pending: '.$pendingPoolCount.', approved: '.$approvedPoolCount.').';
            $content[] = 'Approved bank (questions table, used for draws): '.$approvedBank->count().' row(s).';
            $content[] = '';

            $answerKeyAll = [];
            $num = 0;

            if ($allPools->isNotEmpty()) {
                $content[] = str_repeat('=', 80);
                $content[] = 'SECTION A — COMPLETE POOL (question_pools: '.$allPools->count().' row(s); pending + approved)';
                $content[] = str_repeat('=', 80);
                $content[] = '';
                foreach ($allPools as $pool) {
                    $num++;
                    $tag = $pool->is_approved
                        ? '[Approved — in student draw pool]'
                        : '[Pending — not in draws until approved]';
                    $stem = $tag.' '.(string) $pool->question_text;
                    $this->appendQuizQuestionTextBlock(
                        $content,
                        $answerKeyAll,
                        $num,
                        $stem,
                        is_array($pool->options) ? $pool->options : null,
                        $pool->correct_answer
                    );
                }
            }

            $orphanQuestions = $approvedBank->filter(function ($question) use ($poolTextHashes) {
                $h = hash('sha256', $this->normalizeQuestionTextForDedup((string) $question->text));

                return ! isset($poolTextHashes[$h]);
            })->values();

            if ($orphanQuestions->isNotEmpty()) {
                $content[] = '';
                $content[] = str_repeat('=', 80);
                $content[] = 'SECTION B — QUESTIONS IN BANK ONLY (no matching question_pools row; '.$orphanQuestions->count().' item(s))';
                $content[] = str_repeat('=', 80);
                $content[] = '';
                foreach ($orphanQuestions as $question) {
                    $num++;
                    $stem = '[Bank row — no pool duplicate] '.(string) $question->text;
                    $this->appendQuizQuestionTextBlock(
                        $content,
                        $answerKeyAll,
                        $num,
                        $stem,
                        is_array($question->options) ? $question->options : null,
                        $question->correct_answer
                    );
                }
            }

            if ($allPools->isEmpty() && $orphanQuestions->isEmpty()) {
                $content[] = str_repeat('=', 80);
                $content[] = 'NO POOL ROWS — APPROVED BANK ONLY (questions table: '.$approvedBank->count().' item(s))';
                $content[] = str_repeat('=', 80);
                $content[] = '';
                if ($approvedBank->isEmpty()) {
                    $content[] = '(No questions in this quiz.)';
                    $content[] = '';
                } else {
                    foreach ($approvedBank as $question) {
                        $num++;
                        $this->appendQuizQuestionTextBlock(
                            $content,
                            $answerKeyAll,
                            $num,
                            (string) $question->text,
                            is_array($question->options) ? $question->options : null,
                            $question->correct_answer
                        );
                    }
                }
            }

            $content[] = '';
            $content[] = str_repeat('=', 80);
            $content[] = 'ANSWER KEY (all items above in order)';
            $content[] = str_repeat('=', 80);
            $content[] = '';
            if (count($answerKeyAll) === 0) {
                $content[] = '(None)';
            } else {
                foreach ($answerKeyAll as $line) {
                    $content[] = $line;
                }
            }

            $content[] = '';
            $content[] = str_pad('Generated '.now()->format('M d, Y H:i').' — QuizSnap', 80, ' ', STR_PAD_BOTH);

            $textContent = implode("\n", $content);
            $tempDir = sys_get_temp_dir();
            $tempFile = $tempDir.DIRECTORY_SEPARATOR.'full_pool_'.uniqid().'.txt';
            file_put_contents($tempFile, $textContent);

            if (! file_exists($tempFile)) {
                throw new \Exception('Failed to create TXT file');
            }

            $classSlug = $classGroupName !== '—' ? \Illuminate\Support\Str::slug($classGroupName) : 'class';
            $courseSlug = \Illuminate\Support\Str::slug($courseName ?: 'course');
            $dateStr = now()->format('Y-m-d');
            $filename = $classSlug.'-'.$courseSlug.'-full-pool-'.$dateStr.'.txt';

            return response()->download($tempFile, $filename, [
                'Content-Type' => 'text/plain',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ])->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            if (isset($tempFile) && file_exists($tempFile)) {
                @unlink($tempFile);
            }
            abort(500, UserFriendlyMessages::DOWNLOAD_UNAVAILABLE);
        }
    }

    /**
     * Export quiz results (scores) as Excel.
     */
    public function exportScoresExcel(Quiz $quiz): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->authorize('view', $quiz);
        $filename = 'quiz-scores-' . \Illuminate\Support\Str::slug($quiz->title) . '-' . now()->format('Y-m-d-His') . '.xlsx';
        return Excel::download(new QuizScoresExport($quiz), $filename);
    }

    /**
     * Export quiz results (scores) as CSV. Restricted by course assignment via authorizeQuiz.
     * Buffered response with Content-Length to avoid ERR_INCOMPLETE_CHUNKED_ENCODING.
     */
    public function exportScores(Quiz $quiz): Response
    {
        $this->authorize('view', $quiz);

        $filename = 'quiz-scores-' . \Illuminate\Support\Str::slug($quiz->title) . '-' . now()->format('Y-m-d-His') . '.csv';

        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, [
            'Student Index',
            'Score %',
            'Total Questions',
            'Correct Count',
            'Violations Count',
            'Submitted At',
        ]);

        $sessions = $quiz->sessions()
            ->with('result')
            ->whereNotNull('ended_at')
            ->whereHas('result')
            ->orderBy('student_index')
            ->get();

        foreach ($sessions as $session) {
            $result = $session->result;
            fputcsv($stream, [
                $session->student_index,
                $result ? (string) $result->score : '',
                $result ? (string) $result->total_questions : '',
                $result ? (string) $result->correct_count : '',
                $result ? (string) $result->violations_count : '',
                $result && $result->submitted_at ? $result->submitted_at->toIso8601String() : '',
            ]);
        }
        rewind($stream);
        $body = stream_get_contents($stream);
        fclose($stream);

        return new Response($body, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => (string) strlen($body),
        ]);
    }

    /**
     * Export quiz violations as CSV. Restricted by course assignment via authorizeQuiz.
     * Buffered response with Content-Length to avoid ERR_INCOMPLETE_CHUNKED_ENCODING.
     */
    public function exportViolations(Quiz $quiz): Response
    {
        $this->authorize('view', $quiz);

        $filename = 'quiz-violations-' . \Illuminate\Support\Str::slug($quiz->title) . '-' . now()->format('Y-m-d-His') . '.csv';

        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, [
            'Student Index',
            'Session ID',
            'Type',
            'Severity',
            'Occurred At',
            'Metadata',
        ]);

        $violations = \App\Models\QuizViolation::query()
            ->whereHas('quizSession', fn ($q) => $q->where('quiz_id', $quiz->id))
            ->with('quizSession:id,student_index')
            ->orderBy('occurred_at')
            ->get();

        foreach ($violations as $v) {
            $session = $v->quizSession;
            fputcsv($stream, [
                $session ? $session->student_index : '',
                (string) $v->quiz_session_id,
                $v->type ?? '',
                $v->severity ?? 'warning',
                $v->occurred_at ? $v->occurred_at->toIso8601String() : '',
                $v->metadata ? (is_string($v->metadata) ? $v->metadata : json_encode($v->metadata)) : '',
            ]);
        }
        rewind($stream);
        $body = stream_get_contents($stream);
        fclose($stream);

        return new Response($body, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => (string) strlen($body),
        ]);
    }
}
