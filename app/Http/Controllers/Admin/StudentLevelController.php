<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StudentLevel;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StudentLevelController extends Controller
{
    public function index(): View
    {
        $levels = StudentLevel::ordered();
        $levelsRoutePrefix = session('admin_role') === 'coordinator'
            ? 'dashboard.coordinators.student-levels'
            : 'dashboard.student-levels';

        return view('admin.student-levels.index', compact('levels', 'levelsRoutePrefix'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'value' => 'required|integer|min:1|max:999|unique:student_levels,value',
            'label' => 'required|string|max:100',
        ]);
        $maxOrder = StudentLevel::max('sort_order') ?? 0;
        StudentLevel::create([
            'value' => (int) $request->value,
            'label' => trim($request->label),
            'sort_order' => $maxOrder + 1,
        ]);

        return back()->with('success', 'Level added.');
    }

    public function update(Request $request, StudentLevel $studentLevel): RedirectResponse
    {
        $request->validate([
            'value' => 'required|integer|min:1|max:999|unique:student_levels,value,' . $studentLevel->id,
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
        $studentLevel->delete();

        return back()->with('success', 'Level removed.');
    }
}
