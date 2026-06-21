<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Models\ClassGroup;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\Setting;
use App\Services\LiveQuizSessionService;
use App\Services\PageCacheService;
use App\Services\SitePresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    use InteractsWithAdminSession;

    /** Unified dashboard: show admin, system monitor, coordinator, or examiner content based on role. */
    public function index(): View|\Illuminate\Http\RedirectResponse
    {
        $user = $this->adminUser();

        if ($user?->isSystemAdministrator()) {
            return $this->systemAdministratorDashboard();
        }
        if ($user?->isSuperAdmin()) {
            return $this->adminDashboard();
        }
        if ($user?->isCoordinator()) {
            return $this->coordinatorDashboard();
        }

        return $this->examinerDashboard();
    }

    /** System Monitor dashboard: hub for Monitoring, Operations, and Intelligence centers. */
    public function systemAdministratorDashboard(): View
    {
        $stats = null;
        try {
            $stats = app(\App\Services\Monitoring\MonitoringOverviewService::class)->dashboardStats();
        } catch (\Throwable) {
            $stats = [
                'errors_today' => 0,
                'critical_errors' => 0,
                'failed_jobs' => 0,
                'live_visitors' => app(SitePresenceService::class)->countActive(),
                'live_quiz_takers' => app(LiveQuizSessionService::class)->countActive(),
            ];
        }

        return view('admin.dashboard-system-admin', compact('stats'));
    }

    /** Admin (Super Admin) dashboard: stats, courses, users, class groups, quizzes. */
    public function adminDashboard(): View
    {
        $overview = app(PageCacheService::class)->adminOverviewStats();
        $updateSettings = Setting::getMany([
            Setting::KEY_UPDATE_MODE,
            Setting::KEY_UPDATE_STARTED_AT,
            Setting::KEY_UPDATE_ESTIMATED_END,
        ], [
            Setting::KEY_UPDATE_MODE => '0',
        ]);
        $update_mode = ($updateSettings[Setting::KEY_UPDATE_MODE] ?? '0') === '1';
        $update_started_at = $update_mode ? ($updateSettings[Setting::KEY_UPDATE_STARTED_AT] ?? null) : null;
        $update_estimated_end = $update_mode ? ($updateSettings[Setting::KEY_UPDATE_ESTIMATED_END] ?? null) : null;
        $liveVisitors = app(SitePresenceService::class)->countActive();
        $liveQuizTakers = app(LiveQuizSessionService::class)->countActive();

        return view('admin.dashboard-admin', compact(
            'overview',
            'update_mode',
            'update_started_at',
            'update_estimated_end',
            'liveVisitors',
            'liveQuizTakers',
        ));
    }

    /** JSON live counters for super admin dashboard cards (not cached). */
    public function liveStats(): JsonResponse
    {
        $user = $this->adminUser();
        if (! $user?->isSuperAdmin()) {
            return response()->json(['success' => false], 403);
        }

        return response()
            ->json([
                'success' => true,
                'visitors' => app(SitePresenceService::class)->countActive(),
                'quiz_takers' => app(LiveQuizSessionService::class)->countActive(),
                'as_of' => now()->toIso8601String(),
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    /** Examiner dashboard: my class groups, my quizzes, recent sessions. */
    public function examinerDashboard(): View
    {
        $user = $this->adminUser();
        $classGroupIds = $user ? $user->classGroupIds() : [];
        $quizQuery = Quiz::query()
            ->when($user && ! $user->isSuperAdmin(), fn ($q) => $q->where('examiner_id', $user->id));

        $quizzes = (clone $quizQuery)
            ->with(['course', 'classGroup'])
            ->orderByDesc('created_at')
            ->paginate(10);

        $classGroups = ! empty($classGroupIds)
            ? ClassGroup::withCount('students')
                ->with([
                    'courses' => function ($q) use ($user) {
                        if ($user && \Illuminate\Support\Facades\Schema::hasColumn('class_group_course', 'examiner_id')) {
                            $q->wherePivot('examiner_id', $user->id);
                        }
                        $q->where('is_archived', false)->orderBy('name');
                    },
                ])
                ->whereIn('id', $classGroupIds)
                ->orderBy('name')
                ->get()
            : collect();

        $classGroupsCount = $classGroups->filter(function ($g) {
            return $g->relationLoaded('courses') && $g->courses->isNotEmpty();
        })->count();

        $sessionsWithResults = QuizSession::query()
            ->whereNotNull('ended_at')
            ->whereHas('result')
            ->whereIn('quiz_id', (clone $quizQuery)->select('id'))
            ->count();

        $stats = [
            'quizzes' => (clone $quizQuery)->count(),
            'sessions' => $sessionsWithResults,
            'results' => $sessionsWithResults,
        ];

        $recentSessions = QuizSession::with(['quiz', 'result'])
            ->whereIn('quiz_id', (clone $quizQuery)->select('id'))
            ->orderByDesc('start_time')
            ->limit(20)
            ->get();

        $needsFacultyDepartment = $user && $user->isExaminer() && (! $user->faculty_id || ! $user->department_id);

        return view('admin.dashboard-examiner', compact('quizzes', 'classGroups', 'classGroupsCount', 'recentSessions', 'stats', 'needsFacultyDepartment'));
    }

    /** Coordinator dashboard: class groups, courses, examiners, exam calendar — no quiz authoring. */
    public function coordinatorDashboard(): View
    {
        $user = $this->adminUser();
        $stats = $user
            ? app(PageCacheService::class)->coordinatorStats($user)
            : [
                'class_groups' => 0,
                'courses' => 0,
                'examiners' => 0,
                'exam_calendar' => 0,
                'students' => 0,
            ];

        return view('admin.dashboard-coordinator', compact('stats'));
    }
}
