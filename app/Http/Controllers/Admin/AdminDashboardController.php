<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Models\ClassGroup;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\Setting;
use App\Services\PageCacheService;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    use InteractsWithAdminSession;

    /** Unified dashboard: show admin or examiner content based on role. */
    public function index(): View|\Illuminate\Http\RedirectResponse
    {
        if (session('admin_role') === 'super_admin') {
            return $this->adminDashboard();
        }
        if (session('admin_role') === 'coordinator') {
            return $this->coordinatorDashboard();
        }
        return $this->examinerDashboard();
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
        return view('admin.dashboard-admin', compact('overview', 'update_mode', 'update_started_at', 'update_estimated_end'));
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
