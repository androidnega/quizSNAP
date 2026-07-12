<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Support\AcademicCatalogScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AcademicYearController extends Controller
{
    use InteractsWithAdminSession;

    public function index(): View
    {
        $years = AcademicYear::ordered($this->adminUser());

        return view('admin.coordinators.academic-years.index', compact('years'));
    }

    public function create(): View
    {
        return view('admin.coordinators.academic-years.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->adminUser();
        $facultyId = AcademicCatalogScope::facultyIdForWrite($user);
        if ($user && $user->isCoordinatorOnly() && ! $facultyId) {
            return back()->withInput()->with('error', 'Assign a faculty to your account before creating academic years.');
        }

        $request->validate([
            'year' => [
                'required', 'string', 'max:9',
                Rule::unique('academic_years', 'year')->where(fn ($q) => $q->where('faculty_id', $facultyId)),
            ],
            'is_active' => 'boolean',
        ]);

        if ($request->boolean('is_active')) {
            $q = AcademicYear::query();
            AcademicCatalogScope::apply($q, $user, 'academic_years');
            $q->update(['is_active' => false]);
        }

        AcademicYear::create([
            'year' => $request->year,
            'is_active' => $request->boolean('is_active'),
            'faculty_id' => $facultyId,
        ]);

        return redirect()->route('dashboard.coordinators.academic-years.index')
            ->with('success', 'Academic year created.');
    }

    public function edit(AcademicYear $academicYear): View
    {
        AcademicCatalogScope::assertCanAccess($this->adminUser(), $academicYear);

        return view('admin.coordinators.academic-years.edit', compact('academicYear'));
    }

    public function update(Request $request, AcademicYear $academicYear): RedirectResponse
    {
        $user = $this->adminUser();
        AcademicCatalogScope::assertCanAccess($user, $academicYear);

        $request->validate([
            'year' => [
                'required', 'string', 'max:9',
                Rule::unique('academic_years', 'year')
                    ->where(fn ($q) => $q->where('faculty_id', $academicYear->faculty_id))
                    ->ignore($academicYear->id),
            ],
            'is_active' => 'boolean',
        ]);

        if ($request->boolean('is_active')) {
            $q = AcademicYear::query()->where('id', '!=', $academicYear->id);
            AcademicCatalogScope::apply($q, $user, 'academic_years');
            $q->update(['is_active' => false]);
        }

        $academicYear->update([
            'year' => $request->year,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('dashboard.coordinators.academic-years.index')
            ->with('success', 'Academic year updated.');
    }

    public function destroy(AcademicYear $academicYear): RedirectResponse
    {
        AcademicCatalogScope::assertCanAccess($this->adminUser(), $academicYear);
        if ($academicYear->academicClasses()->exists()) {
            return back()->with('error', 'Cannot delete academic year with linked academic classes.');
        }
        $academicYear->delete();

        return redirect()->route('dashboard.coordinators.academic-years.index')
            ->with('success', 'Academic year deleted.');
    }
}
