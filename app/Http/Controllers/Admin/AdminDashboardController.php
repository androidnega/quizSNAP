<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Models\ClassGroup;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\Setting;
use App\Services\AdminDashboardChartsService;
use App\Services\InfrastructureStatusService;
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
        if ($user?->isSupportAgent()) {
            if (\App\Support\LiveSupportAccess::isEnabled()) {
                return redirect()->route('dashboard.support.index');
            }

            return view('admin.dashboard-support-disabled');
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
        $infrastructure = app(InfrastructureStatusService::class)->snapshot();

        return view('admin.dashboard-admin', compact(
            'overview',
            'update_mode',
            'update_started_at',
            'update_estimated_end',
            'liveVisitors',
            'liveQuizTakers',
            'infrastructure',
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
                'infrastructure' => app(InfrastructureStatusService::class)->snapshot(),
                'as_of' => now()->toIso8601String(),
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    public function charts(): JsonResponse
    {
        $user = $this->adminUser();
        $isExaminer = $user?->isExaminer()
            || (string) session('admin_role') === 'examiner';

        if (! $user || ! $isExaminer) {
            return response()->json(['success' => false, 'message' => 'Charts are available to examiners only.'], 403);
        }

        $period = request()->query('period', '30d');
        if (! in_array($period, ['7d', '30d', '90d'], true)) {
            $period = '30d';
        }

        return response()->json([
            'success' => true,
            'charts' => app(AdminDashboardChartsService::class)->dashboardCharts($period, $user),
        ]);
    }

    /** Examiner dashboard: stats + examiner-scoped charts only. */
    public function examinerDashboard(): View
    {
        $user = $this->adminUser();
        $emptyStats = ['quizzes' => 0, 'sessions' => 0, 'results' => 0];
        $classGroupsCount = 0;
        $stats = $emptyStats;
        $needsFacultyDepartment = false;

        try {
            $quizQuery = Quiz::query()
                ->when($user && ! $user->isSuperAdmin(), fn ($q) => $q->where('examiner_id', $user->id));

            $classGroupIds = $user ? $user->classGroupIds() : [];
            if ($classGroupIds !== []) {
                $classGroupsCount = ClassGroup::query()
                    ->whereIn('id', $classGroupIds)
                    ->when(
                        $user && \Illuminate\Support\Facades\Schema::hasColumn('class_group_course', 'examiner_id'),
                        function ($q) use ($user) {
                            $q->whereHas('courses', fn ($c) => $c->where('class_group_course.examiner_id', $user->id));
                        }
                    )
                    ->count();
                if ($classGroupsCount === 0) {
                    $classGroupsCount = count($classGroupIds);
                }
            }

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

            $needsFacultyDepartment = $user && $user->isExaminer() && (! $user->faculty_id || ! $user->department_id);
        } catch (\Throwable $e) {
            report($e);
        }

        return view('admin.dashboard-examiner', compact('classGroupsCount', 'stats', 'needsFacultyDepartment'));
    }

    /** Coordinator dashboard: class groups, courses, examiners, exam calendar — no quiz authoring. */
    public function coordinatorDashboard(): View
    {
        $user = $this->adminUser();
        $emptyStats = [
            'class_groups' => 0,
            'courses' => 0,
            'examiners' => 0,
            'exam_calendar' => 0,
            'students' => 0,
        ];

        try {
            $stats = $user
                ? app(PageCacheService::class)->coordinatorStats($user)
                : $emptyStats;
        } catch (\Throwable $e) {
            report($e);
            $stats = $emptyStats;
        }

        $classGroups = collect();
        try {
            $ids = $user ? $user->classGroupIds() : [];
            if ($ids !== []) {
                $classGroups = ClassGroup::withCount(['students', 'quizzes', 'courses'])
                    ->with('level')
                    ->whereIn('id', $ids)
                    ->orderBy('name')
                    ->get();
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return view('admin.dashboard-coordinator', compact('stats', 'classGroups'));
    }
}
