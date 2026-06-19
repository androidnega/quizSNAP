<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ClassGroup;
use App\Models\ClassGroupStudent;
use App\Models\ExamCalendar;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\Student;
use App\Models\User;
use App\Services\PageCacheService;
use App\Support\UserFriendlyMessages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class StudentDashboardController extends Controller
{
    /**
     * @return array{classGroupIds: array<int>, classGroups: \Illuminate\Support\Collection<int, ClassGroup>}
     */
    private function resolveStudentClassGroups(Student $student): array
    {
        $indexNumber = $student->index_number ?? '';
        $classGroupIds = app(PageCacheService::class)->studentClassGroupIds($indexNumber)['classGroupIds'];

        $classGroups = $classGroupIds === []
            ? collect()
            : ClassGroup::whereIn('id', $classGroupIds)->get();

        return ['classGroupIds' => $classGroupIds, 'classGroups' => $classGroups];
    }

    /**
     * @param  array<int>  $classGroupIds
     * @return \Illuminate\Support\Collection<int, Quiz>
     */
    private function publishedQuizCandidates(array $classGroupIds, bool $onlyStarted = false): \Illuminate\Support\Collection
    {
        if ($classGroupIds === []) {
            return collect();
        }

        $query = Quiz::whereIn('class_group_id', $classGroupIds)
            ->where('is_published', true)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            });

        if ($onlyStarted) {
            $query->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            });
        }

        return $query->with('course')->withCount('questions')->get();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Quiz>  $candidates
     * @return array<int, true>
     */
    private function completedQuizIdMap(Student $student, \Illuminate\Support\Collection $candidates): array
    {
        $quizIds = $candidates->pluck('id')->filter()->values()->all();
        if ($quizIds === []) {
            return [];
        }

        return QuizSession::query()
            ->where('student_index', $student->index_number)
            ->whereIn('quiz_id', $quizIds)
            ->whereNotNull('ended_at')
            ->pluck('quiz_id')
            ->unique()
            ->flip()
            ->all();
    }

    protected function student(): Student
    {
        $user = auth()->user();
        if ($user instanceof Student) {
            return $user;
        }
        $student = Student::find(session('student_id'));
        if (!$student) {
            abort(401, 'Not authenticated as student.');
        }
        return $student;
    }

    public function index(): View
    {
        $student = $this->student();
        ['classGroupIds' => $classGroupIds, 'classGroups' => $classGroups] = $this->resolveStudentClassGroups($student);

        $sessionsCount = QuizSession::where('student_index', $student->index_number)->whereHas('result')->count();
        $recentSessions = QuizSession::where('student_index', $student->index_number)
            ->with(['quiz', 'result'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $lastQuiz = QuizSession::where('student_index', $student->index_number)
            ->whereHas('result')
            ->with(['quiz.course', 'result'])
            ->orderByDesc('created_at')
            ->first();

        $examCalendarEntries = $classGroupIds === []
            ? collect()
            : ExamCalendar::with('course')
                ->whereIn('class_group_id', $classGroupIds)
                ->orderBy('scheduled_at')
                ->get();

        $scheduledQuiz = null;
        $scheduledQuizSession = null;
        if ($classGroupIds !== []) {
            $candidates = $this->publishedQuizCandidates($classGroupIds);
            $ready = $candidates->filter(fn (Quiz $q) => $q->hasEnoughApprovedQuestions() && (($q->starts_at && $q->starts_at->isFuture()) || $q->isActive()));
            $scheduledQuiz = $ready->sortBy(fn (Quiz $q) => $q->starts_at && $q->starts_at->isFuture() ? $q->starts_at->timestamp : PHP_INT_MAX)->first();

            if ($scheduledQuiz) {
                $scheduledQuizSession = QuizSession::where('quiz_id', $scheduledQuiz->id)
                    ->where('student_index', $student->index_number)
                    ->whereNotNull('ended_at')
                    ->with('result')
                    ->first();
            }
        }

        $hour = (int) now()->format('G');
        $greeting = ($hour >= 5 && $hour < 12) ? 'Good morning' : (($hour >= 12 && $hour < 17) ? 'Good afternoon' : 'Good evening');

        return view('student.dashboard.index', [
            'student' => $student,
            'classGroups' => $classGroups,
            'examCalendarEntries' => $examCalendarEntries,
            'sessionsCount' => $sessionsCount,
            'recentSessions' => $recentSessions,
            'scheduledQuiz' => $scheduledQuiz,
            'scheduledQuizSession' => $scheduledQuizSession,
            'lastQuiz' => $lastQuiz,
            'greeting' => $greeting,
            'displayName' => $student->first_name,
            'dashboardBanner' => \App\Models\Setting::getStudentDashboardBannerConfig(),
        ]);
    }

    /**
     * Exam calendar page: midsem & end-of-semester exams for the student's class(es).
     * Shows countdown when an exam is within a few hours.
     */
    public function calendar(): View|RedirectResponse
    {
        $student = $this->student();
        ['classGroupIds' => $classGroupIds] = $this->resolveStudentClassGroups($student);

        $examCalendarEntries = $classGroupIds === []
            ? collect()
            : ExamCalendar::with('course')
                ->whereIn('class_group_id', $classGroupIds)
                ->orderBy('scheduled_at')
                ->get();

        return view('student.dashboard.calendar', [
            'student' => $student,
            'examCalendarEntries' => $examCalendarEntries,
        ]);
    }

    /**
     * List all quizzes (sessions) this student has taken. Marks are kept forever.
     * Active quizzes (available to take) are shown first.
     */
    public function quizzes(): View
    {
        $student = $this->student();
        ['classGroupIds' => $classGroupIds] = $this->resolveStudentClassGroups($student);

        $activeQuizzes = collect();
        if ($classGroupIds !== []) {
            $candidates = $this->publishedQuizCandidates($classGroupIds, onlyStarted: true);
            $completedIds = $this->completedQuizIdMap($student, $candidates);
            $activeQuizzes = $candidates
                ->filter(fn (Quiz $q) => $q->hasEnoughApprovedQuestions() && $q->isActive())
                ->reject(fn (Quiz $q) => isset($completedIds[$q->id]));
        }

        $sessions = QuizSession::where('student_index', $student->index_number)
            ->whereHas('result')
            ->with(['quiz', 'result'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('student.dashboard.quizzes', [
            'student' => $student,
            'sessions' => $sessions,
            'activeQuizzes' => $activeQuizzes,
        ]);
    }

    public function profile(): View
    {
        $student = $this->student();
        $indexUpper = strtoupper(trim($student->index_number ?? ''));

        // Same resolution as coordinator: class groups by case-insensitive index so student sees same institution/faculty/department as coordinator
        $cgStudents = ClassGroupStudent::whereRaw('UPPER(TRIM(index_number)) = ?', [$indexUpper])
            ->with([
                'classGroup' => fn ($q) => $q->with([
                    'examiner' => fn ($e) => $e->with(['institution', 'faculty', 'department']),
                    'academicYear',
                    'level',
                    'courses',
                    'quizCategory',
                    'semester',
                ]),
            ])
            ->get();
        $classGroups = $cgStudents->map(fn ($s) => $s->classGroup)->filter()->unique('id')->values();

        // Courses being offered (from student's class groups) — unique by course id, display name + code
        $studentCourses = $classGroups->flatMap(fn ($g) => $g->relationLoaded('courses') ? $g->courses : collect())
            ->unique('id')
            ->values()
            ->map(fn ($c) => ['name' => $c->name ?? '', 'code' => $c->code ?? ''])
            ->values();

        $institution = null;
        $faculty = null;
        $department = null;
        $academicYears = collect();

        // Same source as coordinator: institution/faculty/department from examiners of the student's class groups
        foreach ($classGroups as $cg) {
            if ($cg && $cg->examiner_id) {
                $ex = $cg->examiner;
                if ($ex) {
                    $institution = $institution ?? $ex->institution;
                    $faculty = $faculty ?? $ex->faculty;
                    $department = $department ?? $ex->department;
                }
            }
            if ($cg && $cg->academicYear && !$academicYears->contains('id', $cg->academicYear->id)) {
                $academicYears->push($cg->academicYear);
            }
        }
        $academicYears = $academicYears->sortByDesc('year')->values();

        $levelLabel = null;
        $qualificationType = null;
        $currentSemester = null;

        // If we have institution but missing faculty/department, derive from institution
        if ($institution && (!$faculty || !$department)) {
            if (!$institution->relationLoaded('faculties')) {
                $institution->load(['faculties.departments']);
            }
            $firstFaculty = $institution->faculties->first();
            if ($firstFaculty && !$faculty) {
                $faculty = $firstFaculty;
            }
            if ($firstFaculty && !$department) {
                $department = $firstFaculty->departments->first();
            }
        }

        // Last resort: no institution from groups or dm user — use first institution and derive faculty/department
        if (!$institution) {
            $institution = \App\Models\Institution::with(['faculties.departments'])->first();
            if ($institution) {
                $firstFaculty = $institution->faculties->first();
                if ($firstFaculty) {
                    $faculty = $faculty ?? $firstFaculty;
                    $department = $department ?? $firstFaculty->departments->first();
                }
            }
        }

        $levelValue = (int) ($student->level ?? 0);
        if ($levelValue <= 0 && $student->level_id) {
            $lv = \App\Models\StudentLevel::find($student->level_id);
            $levelValue = $lv ? (int) $lv->value : 0;
        }
        if ($levelValue <= 0 && $classGroups->isNotEmpty()) {
            foreach ($classGroups as $cg) {
                if ($cg->level_id && $cg->level) {
                    $levelValue = (int) $cg->level->value;
                    break;
                }
            }
        }
        if ($levelValue > 0) {
            $levelModel = \App\Models\StudentLevel::where('value', $levelValue)->first();
            $levelLabel = $levelModel?->label ?? (string) $levelValue;
        }

        // Qualification type from student record (preferred), else from class group quiz category
        if ($student->quiz_category_id) {
            $qc = \App\Models\QuizCategory::find($student->quiz_category_id);
            $qualificationType = $qc?->name;
        }
        if (!$qualificationType && $classGroups->isNotEmpty()) {
            foreach ($classGroups as $cg) {
                if ($cg?->quizCategory) {
                    $qualificationType = $cg->quizCategory->name;
                    break;
                }
            }
        }

        // Current semester from student record (preferred), else from class group semester
        if ($student->semester_id) {
            $sem = \App\Models\Semester::find($student->semester_id);
            $currentSemester = $sem?->name;
        }
        if (!$currentSemester && $classGroups->isNotEmpty()) {
            foreach ($classGroups as $cg) {
                if ($cg?->semester) {
                    $currentSemester = $cg->semester->name;
                    break;
                }
            }
        }

        return view('student.dashboard.profile', [
            'student' => $student,
            'classGroups' => $classGroups,
            'studentCourses' => $studentCourses,
            'levelLabel' => $levelLabel,
            'qualificationType' => $qualificationType,
            'currentSemester' => $currentSemester,
            'institution' => $institution ?? null,
            'faculty' => $faculty,
            'department' => $department,
            'academicYears' => $academicYears ?? collect(),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $student = $this->student();
        $request->validate([
            'student_name' => 'nullable|string|max:255',
        ]);
        $student->student_name = $request->filled('student_name') ? ucwords(strtolower(trim($request->student_name))) : null;
        $student->save();
        return redirect()->route('dashboard.my-profile')->with('success', 'Saved');
    }

    /**
     * Show one past quiz. Marks (score) always shown. Full Q&A review only for last 21 days.
     */
    public function showQuiz(Request $request, $sessionId): View|RedirectResponse
    {
        try {
            $student = $this->student();
            
            // Validate session ID is numeric
            if (!is_numeric($sessionId)) {
                return redirect()->route('dashboard.my-quizzes')->with('error', UserFriendlyMessages::GENERIC);
            }
            
            $quizSession = QuizSession::where('id', $sessionId)
                ->where('student_index', $student->index_number)
                ->with(['quiz.course', 'quiz.classGroup.level', 'quiz.academicClass', 'result', 'answers.question'])
                ->first();
            
            if (!$quizSession) {
                return redirect()->route('dashboard.my-quizzes')->with('error', UserFriendlyMessages::NOT_FOUND);
            }

            if (!$quizSession->quiz) {
                return redirect()->route('dashboard.my-quizzes')->with('error', UserFriendlyMessages::NOT_FOUND);
            }

            if (!$quizSession->result) {
                return redirect()->route('dashboard.my-quizzes')->with('error', 'This attempt is no longer available.');
            }

            if (!$quizSession->quiz->canShowScore()) {
                return redirect()->route('dashboard.my-quizzes')->with('info', 'Results are not available for this quiz.');
            }

            $reviewAvailableWithinDays = 21;
            $showFullReview = $quizSession->created_at && $quizSession->created_at->gte(now()->subDays($reviewAvailableWithinDays));

            return view('student.dashboard.quiz-show', [
                'student' => $student,
                'session' => $quizSession,
                'showFullReview' => $showFullReview,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error showing quiz review: ' . $e->getMessage(), [
                'session_id' => $sessionId ?? null,
                'student_id' => $student->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('dashboard.my-quizzes')->with('error', UserFriendlyMessages::GENERIC);
        }
    }

    /**
     * Download quiz result as PDF.
     */
    public function downloadPdf(Request $request, $sessionId): Response|RedirectResponse
    {
        try {
            $student = $this->student();
            
            // Validate session ID is numeric
            if (!is_numeric($sessionId)) {
                return redirect()->route('dashboard.my-quizzes')->with('error', UserFriendlyMessages::GENERIC);
            }
            
            $quizSession = QuizSession::where('id', $sessionId)
                ->where('student_index', $student->index_number)
                ->with(['quiz.course', 'quiz.classGroup.level', 'quiz.academicClass', 'result', 'answers.question'])
                ->first();
            
            if (!$quizSession) {
                return redirect()->route('dashboard.my-quizzes')->with('error', UserFriendlyMessages::NOT_FOUND);
            }

            if (!$quizSession->quiz) {
                return redirect()->route('dashboard.my-quizzes')->with('error', UserFriendlyMessages::NOT_FOUND);
            }

            if (!$quizSession->quiz->canShowScore() || !$quizSession->result) {
                return redirect()->route('dashboard.my-quizzes')->with('error', UserFriendlyMessages::NOT_FOUND);
            }

            if ($quizSession->isResultWithheld()) {
                return redirect()->route('dashboard.my-quizzes')->with('error', 'Result is on hold. Download is available after the lecturer releases it.');
            }

            $reviewAvailableWithinDays = 21;
            $showFullReview = $quizSession->created_at && $quizSession->created_at->gte(now()->subDays($reviewAvailableWithinDays));

            $html = view('student.dashboard.quiz-pdf', [
                'student' => $student,
                'session' => $quizSession,
                'showFullReview' => $showFullReview,
            ])->render();

            $filename = 'Quiz_Result_' . ($quizSession->quiz->title ?? 'Quiz') . '_' . ($quizSession->created_at ? $quizSession->created_at->format('Y-m-d') : date('Y-m-d')) . '.pdf';

            return response($html)
                ->header('Content-Type', 'text/html')
                ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
        } catch (\Exception $e) {
            \Log::error('Error generating PDF: ' . $e->getMessage(), [
                'session_id' => $sessionId ?? null,
                'student_id' => $student->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('dashboard.my-quizzes')->with('error', UserFriendlyMessages::GENERIC);
        }
    }

    /**
     * Show course materials page with weekly content.
     */
    public function courseMaterials(): View
    {
        $student = $this->student();
        return view('student.dashboard.course-materials', [
            'student' => $student,
        ]);
    }
}
