<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Http\Controllers\Controller;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\QuizCategory;
use App\Models\StudentLevel;
use App\Support\AcademicCatalogScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademicClassController extends Controller
{
    use InteractsWithAdminSession;

    public function index(Request $request): View
    {
        $user = $this->adminUser();
        $query = AcademicClass::with(['quizCategory', 'level', 'academicYear'])
            ->orderBy('academic_year_id', 'desc')
            ->orderBy('name');
        AcademicCatalogScope::apply($query, $user, 'academic_classes');
        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }
        $classes = $query->paginate(20)->withQueryString();
        $academicYears = AcademicYear::ordered($user);

        return view('admin.coordinators.quizsnap.academic-classes.index', compact('classes', 'academicYears'));
    }

    public function create(): View
    {
        $user = $this->adminUser();
        $quizCategories = QuizCategory::ordered($user);
        $levels = StudentLevel::ordered($user);
        $academicYears = AcademicYear::ordered($user);

        return view('admin.coordinators.quizsnap.academic-classes.create', compact('quizCategories', 'levels', 'academicYears'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->adminUser();
        $facultyId = AcademicCatalogScope::facultyIdForWrite($user);
        if ($user && $user->isCoordinatorOnly() && ! $facultyId) {
            return back()->withInput()->with('error', 'Assign a faculty to your account before creating academic classes.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'quiz_category_id' => 'required|exists:quiz_categories,id',
            'level_id' => 'required|exists:student_levels,id',
            'academic_year_id' => 'required|exists:academic_years,id',
        ]);

        $this->assertRelatedInFaculty($request, $facultyId);

        AcademicClass::create([
            'name' => trim($request->name),
            'quiz_category_id' => (int) $request->quiz_category_id,
            'level_id' => (int) $request->level_id,
            'academic_year_id' => (int) $request->academic_year_id,
            'faculty_id' => $facultyId,
        ]);

        return redirect()->route('dashboard.coordinators.academic-classes.index')
            ->with('success', 'Academic class created.');
    }

    public function edit(AcademicClass $academicClass): View
    {
        $user = $this->adminUser();
        AcademicCatalogScope::assertCanAccess($user, $academicClass);
        $quizCategories = QuizCategory::ordered($user);
        $levels = StudentLevel::ordered($user);
        $academicYears = AcademicYear::ordered($user);

        return view('admin.coordinators.quizsnap.academic-classes.edit', compact('academicClass', 'quizCategories', 'levels', 'academicYears'));
    }

    public function update(Request $request, AcademicClass $academicClass): RedirectResponse
    {
        $user = $this->adminUser();
        AcademicCatalogScope::assertCanAccess($user, $academicClass);

        $request->validate([
            'name' => 'required|string|max:255',
            'quiz_category_id' => 'required|exists:quiz_categories,id',
            'level_id' => 'required|exists:student_levels,id',
            'academic_year_id' => 'required|exists:academic_years,id',
        ]);

        $this->assertRelatedInFaculty($request, $academicClass->faculty_id);

        $academicClass->update($request->only('name', 'quiz_category_id', 'level_id', 'academic_year_id'));

        return redirect()->route('dashboard.coordinators.academic-classes.index')
            ->with('success', 'Academic class updated.');
    }

    public function destroy(AcademicClass $academicClass): RedirectResponse
    {
        AcademicCatalogScope::assertCanAccess($this->adminUser(), $academicClass);
        if ($academicClass->students()->exists() || $academicClass->quizzes()->exists()) {
            return back()->with('error', 'Cannot delete class with students or quizzes.');
        }
        $academicClass->delete();

        return redirect()->route('dashboard.coordinators.academic-classes.index')
            ->with('success', 'Academic class deleted.');
    }

    private function assertRelatedInFaculty(Request $request, ?int $facultyId): void
    {
        if ($facultyId === null && $this->adminUser()?->isSuperAdmin()) {
            return;
        }

        $cat = QuizCategory::find($request->quiz_category_id);
        $level = StudentLevel::find($request->level_id);
        $year = AcademicYear::find($request->academic_year_id);
        foreach ([$cat, $level, $year] as $row) {
            if (! $row || (int) ($row->faculty_id ?? 0) !== (int) $facultyId) {
                abort(422, 'Selected academic options must belong to your faculty.');
            }
        }
    }
}
