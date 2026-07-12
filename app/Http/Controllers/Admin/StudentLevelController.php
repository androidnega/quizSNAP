<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Http\Controllers\Controller;
use App\Models\StudentLevel;
use App\Support\AcademicCatalogScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentLevelController extends Controller
{
    use InteractsWithAdminSession;

    public function index(): View
    {
        $user = $this->adminUser();
        $levels = StudentLevel::ordered($user);
        $levelsRoutePrefix = ($user && $user->isCoordinatorOnly())
            ? 'dashboard.coordinators.student-levels'
            : 'dashboard.student-levels';

        return view('admin.student-levels.index', compact('levels', 'levelsRoutePrefix'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->adminUser();
        $facultyId = AcademicCatalogScope::facultyIdForWrite($user);
        if ($user && $user->isCoordinatorOnly() && ! $facultyId) {
            return back()->withInput()->with('error', 'Assign a faculty to your account before creating levels.');
        }

        $request->validate([
            'value' => [
                'required', 'integer', 'min:1', 'max:999',
                Rule::unique('student_levels', 'value')->where(fn ($q) => $q->where('faculty_id', $facultyId)),
            ],
            'label' => 'required|string|max:100',
        ]);

        $maxOrder = StudentLevel::query()
            ->when($facultyId, fn ($q) => $q->where('faculty_id', $facultyId))
            ->max('sort_order') ?? 0;

        StudentLevel::create([
            'value' => (int) $request->value,
            'label' => trim($request->label),
            'sort_order' => $maxOrder + 1,
            'faculty_id' => $facultyId,
        ]);

        return back()->with('success', 'Level added.');
    }

    public function update(Request $request, StudentLevel $studentLevel): RedirectResponse
    {
        AcademicCatalogScope::assertCanAccess($this->adminUser(), $studentLevel);

        $request->validate([
            'value' => [
                'required', 'integer', 'min:1', 'max:999',
                Rule::unique('student_levels', 'value')
                    ->where(fn ($q) => $q->where('faculty_id', $studentLevel->faculty_id))
                    ->ignore($studentLevel->id),
            ],
            'label' => 'required|string|max:100',
        ]);

        $studentLevel->update([
            'value' => (int) $request->value,
            'label' => trim($request->label),
        ]);

        return back()->with('success', 'Level updated.');
    }

    public function destroy(StudentLevel $studentLevel): RedirectResponse
    {
        AcademicCatalogScope::assertCanAccess($this->adminUser(), $studentLevel);
        $studentLevel->delete();

        return back()->with('success', 'Level removed.');
    }
}
