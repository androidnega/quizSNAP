<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Http\Controllers\Controller;
use App\Models\Semester;
use App\Support\AcademicCatalogScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SemesterController extends Controller
{
    use InteractsWithAdminSession;

    public function index(): View
    {
        $user = $this->adminUser();
        $semesters = Semester::ordered($user);

        return view('admin.coordinators.quizsnap.semesters.index', compact('semesters'));
    }

    public function create(): View
    {
        return view('admin.coordinators.quizsnap.semesters.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->adminUser();
        $facultyId = AcademicCatalogScope::facultyIdForWrite($user);
        if ($user && $user->isCoordinatorOnly() && ! $facultyId) {
            return back()->withInput()->with('error', 'Assign a faculty to your account before creating semesters.');
        }

        $request->validate([
            'value' => [
                'required', 'integer', 'min:1', 'max:10',
                Rule::unique('semesters', 'value')->where(fn ($q) => $q->where('faculty_id', $facultyId)),
            ],
            'name' => 'required|string|max:20',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        Semester::create([
            'value' => (int) $request->value,
            'name' => trim($request->name),
            'sort_order' => (int) ($request->sort_order ?? 0),
            'faculty_id' => $facultyId,
        ]);

        return redirect()->route('dashboard.coordinators.semesters.index')
            ->with('success', 'Semester created.');
    }

    public function edit(Semester $semester): View
    {
        AcademicCatalogScope::assertCanAccess($this->adminUser(), $semester);

        return view('admin.coordinators.quizsnap.semesters.edit', compact('semester'));
    }

    public function update(Request $request, Semester $semester): RedirectResponse
    {
        $user = $this->adminUser();
        AcademicCatalogScope::assertCanAccess($user, $semester);
        $facultyId = $semester->faculty_id;

        $request->validate([
            'value' => [
                'required', 'integer', 'min:1', 'max:10',
                Rule::unique('semesters', 'value')
                    ->where(fn ($q) => $q->where('faculty_id', $facultyId))
                    ->ignore($semester->id),
            ],
            'name' => 'required|string|max:20',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $semester->update([
            'value' => (int) $request->value,
            'name' => trim($request->name),
            'sort_order' => (int) ($request->sort_order ?? 0),
        ]);

        return redirect()->route('dashboard.coordinators.semesters.index')
            ->with('success', 'Semester updated.');
    }

    public function destroy(Semester $semester): RedirectResponse
    {
        AcademicCatalogScope::assertCanAccess($this->adminUser(), $semester);
        if ($semester->courses()->exists() || $semester->quizzes()->exists()) {
            return back()->with('error', 'Cannot delete semester with courses or quizzes.');
        }
        $semester->delete();

        return redirect()->route('dashboard.coordinators.semesters.index')
            ->with('success', 'Semester deleted.');
    }
}
