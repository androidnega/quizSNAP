<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademicYearController extends Controller
{
    public function index(): View
    {
        $years = AcademicYear::orderBy('year', 'desc')->get();

        return view('admin.coordinators.academic-years.index', compact('years'));
    }

    public function create(): View
    {
        return view('admin.coordinators.academic-years.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'year' => 'required|string|max:9|unique:academic_years,year',
            'is_active' => 'boolean',
        ]);

        if ($request->boolean('is_active')) {
            AcademicYear::query()->update(['is_active' => false]);
        }

        AcademicYear::create([
            'year' => $request->year,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('dashboard.coordinators.academic-years.index')
            ->with('success', 'Academic year created.');
    }

    public function edit(AcademicYear $academicYear): View
    {
        return view('admin.coordinators.academic-years.edit', compact('academicYear'));
    }

    public function update(Request $request, AcademicYear $academicYear): RedirectResponse
    {
        $request->validate([
            'year' => 'required|string|max:9|unique:academic_years,year,' . $academicYear->id,
            'is_active' => 'boolean',
        ]);

        if ($request->boolean('is_active')) {
            AcademicYear::where('id', '!=', $academicYear->id)->update(['is_active' => false]);
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
        if ($academicYear->academicClasses()->exists()) {
            return back()->with('error', 'Cannot delete academic year with linked academic classes.');
        }

        $academicYear->delete();

        return redirect()->route('dashboard.coordinators.academic-years.index')
            ->with('success', 'Academic year deleted.');
    }
}
