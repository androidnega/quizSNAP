<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Models\Course;
use App\Models\QuizCategory;
use App\Models\Semester;
use App\Models\StudentLevel;
use App\Models\User;
use App\Support\UserFriendlyMessages;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Course management: Admin and Coordinator can create/edit courses.
 * Coordinators are limited to their institution. Examiners see assigned courses only.
 */
class CourseController extends Controller
{
    use InteractsWithAdminSession;

    private function scopedCourseQuery(?User $user)
    {
        $q = Course::query()->where('is_archived', false);
        if (! $user || $user->isSuperAdmin()) {
            return $q;
        }

        $ids = $user->assignedCourseIds();

        return $q->whereIn('id', $ids !== [] ? $ids : [-1]);
    }

    private function assertCanAccessCourse(?User $user, Course $course): void
    {
        if (! $user || ! $user->canAccessCourse($course)) {
            abort(403, UserFriendlyMessages::ACCESS_DENIED);
        }
    }

    /** @return list<int> */
    private function scopedExaminerIds(?User $user): array
    {
        if (! $user) {
            return [];
        }
        if ($user->isSuperAdmin()) {
            return User::where('role', User::ROLE_EXAMINER)->pluck('id')->all();
        }

        return $user->examinersInScope()->pluck('id')->all();
    }

    public function index(): View
    {
        $user = $this->adminUser();
        $canManageAll = $user && ($user->isSuperAdmin() || $user->isCoordinatorOnly());
        $baseQuery = $this->scopedCourseQuery($user);

        if (! $canManageAll && $user?->isExaminer()) {
            $totalCount = (clone $baseQuery)->count();
            $stats = [
                'total' => $totalCount,
                'assigned' => $totalCount,
                'unassigned' => 0,
            ];
        } else {
            $assignedCount = (clone $baseQuery)->whereHas('examiners')->count();
            $unassignedCount = (clone $baseQuery)->whereDoesntHave('examiners')->count();
            $totalCount = $assignedCount + $unassignedCount;
            $stats = [
                'total' => $totalCount,
                'assigned' => $assignedCount,
                'unassigned' => $unassignedCount,
            ];
        }

        $courses = (clone $baseQuery)
            ->withCount(['quizzes', 'validIndices'])
            ->with('examiners:id,username,name')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.courses.index', compact('courses', 'canManageAll', 'stats'));
    }

    public function create(): View|RedirectResponse
    {
        $user = $this->adminUser();
        if ($user && $user->isCoordinatorOnly() && ! $user->institution_id) {
            return redirect()->route('dashboard.courses.index')
                ->with('error', 'Your account has no institution assigned. Contact a Super Admin before creating courses.');
        }

        $canAssignLecturers = $user && ($user->isSuperAdmin() || $user->isCoordinatorOnly());
        $examiners = $canAssignLecturers && $user
            ? $user->examinersInScope()->orderBy('username')->get()
            : collect();
        $quizCategories = QuizCategory::ordered($user);
        $levels = StudentLevel::ordered($user);
        $semesters = Semester::ordered($user);

        return view('admin.courses.create', compact('examiners', 'canAssignLecturers', 'quizCategories', 'levels', 'semesters'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->adminUser();
        if (! $user || (! $user->isSuperAdmin() && ! $user->isCoordinatorOnly())) {
            abort(403, UserFriendlyMessages::ACCESS_DENIED);
        }
        if ($user->isCoordinatorOnly() && ! $user->institution_id) {
            return redirect()->route('dashboard.courses.index')
                ->with('error', 'Your account has no institution assigned. Contact a Super Admin before creating courses.');
        }

        $canAssignLecturers = $user->isSuperAdmin() || $user->isCoordinatorOnly();
        $allowedExaminerIds = $this->scopedExaminerIds($user);

        $rules = [
            'code' => 'required|string|max:64|unique:courses,code',
            'name' => 'required|string|max:255',
            'quiz_category_id' => 'nullable|exists:quiz_categories,id',
            'level_id' => 'nullable|exists:student_levels,id',
            'semester_id' => 'nullable|exists:semesters,id',
        ];

        if ($canAssignLecturers) {
            $rules['examiner_ids'] = 'nullable|array';
            $rules['examiner_ids.*'] = 'integer|exists:users,id';
        }

        $request->validate($rules);

        $examinerIds = array_values(array_intersect(
            array_map('intval', $request->input('examiner_ids', [])),
            $allowedExaminerIds
        ));

        $attrs = [
            'code' => trim($request->code),
            'name' => strtoupper(trim($request->name)),
            'is_archived' => false,
            'quiz_category_id' => $request->filled('quiz_category_id') ? (int) $request->quiz_category_id : null,
            'level_id' => $request->filled('level_id') ? (int) $request->level_id : null,
            'semester_id' => $request->filled('semester_id') ? (int) $request->semester_id : null,
        ];
        if (Schema::hasColumn('courses', 'institution_id') && $user->isCoordinatorOnly()) {
            $attrs['institution_id'] = (int) $user->institution_id;
        } elseif (Schema::hasColumn('courses', 'institution_id') && $user->isSuperAdmin() && $request->filled('institution_id')) {
            $attrs['institution_id'] = (int) $request->institution_id;
        }

        $course = Course::create($attrs);

        if ($canAssignLecturers) {
            $course->examiners()->sync($examinerIds);
        }

        return redirect()->route('dashboard.courses.index')->with('success', 'Course created.');
    }

    public function edit(Course $course): View
    {
        $user = $this->adminUser();
        $this->assertCanAccessCourse($user, $course);

        $canAssignLecturers = $user && ($user->isSuperAdmin() || $user->isCoordinatorOnly());
        $course->load('examiners:id,username,name');
        $examiners = $canAssignLecturers && $user
            ? $user->examinersInScope()->orderBy('username')->get()
            : collect();
        $quizCategories = QuizCategory::ordered($user);
        $levels = StudentLevel::ordered($user);
        $semesters = Semester::ordered($user);

        return view('admin.courses.edit', compact('course', 'examiners', 'canAssignLecturers', 'quizCategories', 'levels', 'semesters'));
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $user = $this->adminUser();
        $this->assertCanAccessCourse($user, $course);

        $canAssignLecturers = $user && ($user->isSuperAdmin() || $user->isCoordinatorOnly());
        $allowedExaminerIds = $this->scopedExaminerIds($user);

        $rules = [
            'code' => 'required|string|max:64|unique:courses,code,' . $course->id,
            'name' => 'required|string|max:255',
            'quiz_category_id' => 'nullable|exists:quiz_categories,id',
            'level_id' => 'nullable|exists:student_levels,id',
            'semester_id' => 'nullable|exists:semesters,id',
        ];

        if ($canAssignLecturers) {
            $rules['examiner_ids'] = 'nullable|array';
            $rules['examiner_ids.*'] = 'integer|exists:users,id';
        }

        $request->validate($rules);

        $course->update([
            'code' => trim($request->code),
            'name' => strtoupper(trim($request->name)),
            'quiz_category_id' => $request->filled('quiz_category_id') ? (int) $request->quiz_category_id : null,
            'level_id' => $request->filled('level_id') ? (int) $request->level_id : null,
            'semester_id' => $request->filled('semester_id') ? (int) $request->semester_id : null,
        ]);

        // Ensure institution stays set for coordinator-owned courses
        if ($user && $user->isCoordinatorOnly()
            && Schema::hasColumn('courses', 'institution_id')
            && ! $course->institution_id
            && $user->institution_id) {
            $course->update(['institution_id' => (int) $user->institution_id]);
        }

        if ($canAssignLecturers) {
            $examinerIds = array_values(array_intersect(
                array_map('intval', $request->input('examiner_ids', [])),
                $allowedExaminerIds
            ));
            $course->examiners()->sync($examinerIds);
        }

        return redirect()->route('dashboard.courses.index')->with('success', 'Course updated.');
    }

    public function archive(Course $course): RedirectResponse
    {
        $this->assertCanAccessCourse($this->adminUser(), $course);
        $course->update(['is_archived' => true]);

        return redirect()->route('dashboard.courses.index')->with('success', 'Course archived.');
    }

    public function unarchive(Course $course): RedirectResponse
    {
        $this->assertCanAccessCourse($this->adminUser(), $course);
        $course->update(['is_archived' => false]);

        return redirect()->route('dashboard.courses.index')->with('success', 'Course restored.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        $user = $this->adminUser();
        $canManage = $user && ($user->isSuperAdmin() || $user->isCoordinatorOnly());
        if (! $canManage) {
            return redirect()->route('dashboard.courses.index')
                ->with('error', 'Only the coordinator or Super Administrator can delete courses.');
        }
        $this->assertCanAccessCourse($user, $course);

        if ($course->quizzes()->exists()) {
            return redirect()->route('dashboard.courses.index')
                ->with('error', 'Cannot delete: this course has quizzes. Archive the course or remove/reassign the quizzes first.');
        }
        $name = $course->name;
        $course->examiners()->detach();
        $course->classGroups()->detach();
        $course->validIndices()->delete();
        $course->delete();

        return redirect()->route('dashboard.courses.index')->with('success', "Course \"{$name}\" deleted.");
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $user = $this->adminUser();
        $canManage = $user && ($user->isSuperAdmin() || $user->isCoordinatorOnly());
        if (! $canManage) {
            return redirect()->route('dashboard.courses.index')
                ->with('error', 'Only the coordinator or Super Administrator can delete courses.');
        }

        $ids = $request->input('course_ids', []);
        if (! is_array($ids) || count($ids) === 0) {
            return redirect()->route('dashboard.courses.index')
                ->with('error', 'No courses selected.');
        }

        $allowedIds = $user->isSuperAdmin()
            ? array_map('intval', $ids)
            : array_values(array_intersect(array_map('intval', $ids), $user->assignedCourseIds()));

        $courses = Course::whereIn('id', $allowedIds ?: [-1])->get();
        if ($courses->isEmpty()) {
            return redirect()->route('dashboard.courses.index')
                ->with('error', 'No valid courses selected.');
        }

        $deleted = 0;
        $skipped = [];

        foreach ($courses as $course) {
            if ($course->quizzes()->exists()) {
                $skipped[] = $course->name ?: $course->code ?: ('ID ' . $course->id);
                continue;
            }
            $course->examiners()->detach();
            $course->classGroups()->detach();
            $course->validIndices()->delete();
            $course->delete();
            $deleted++;
        }

        $message = $deleted > 0
            ? "{$deleted} course" . ($deleted === 1 ? '' : 's') . ' deleted.'
            : 'No courses were deleted.';

        if (! empty($skipped)) {
            $list = implode(', ', array_slice($skipped, 0, 5));
            if (count($skipped) > 5) {
                $list .= ' +' . (count($skipped) - 5) . ' more';
            }
            $message .= ' Skipped (has quizzes): ' . $list . '.';
        }

        return redirect()->route('dashboard.courses.index')
            ->with($deleted > 0 ? 'success' : 'error', $message);
    }
}
