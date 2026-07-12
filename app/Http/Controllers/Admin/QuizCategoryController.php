<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Http\Controllers\Controller;
use App\Models\QuizCategory;
use App\Support\AcademicCatalogScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class QuizCategoryController extends Controller
{
    use InteractsWithAdminSession;

    public function index(): View
    {
        $categories = QuizCategory::ordered($this->adminUser());

        return view('admin.coordinators.quizsnap.quiz-categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.coordinators.quizsnap.quiz-categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->adminUser();
        $facultyId = AcademicCatalogScope::facultyIdForWrite($user);
        if ($user && $user->isCoordinatorOnly() && ! $facultyId) {
            return back()->withInput()->with('error', 'Assign a faculty to your account before creating categories.');
        }

        $request->validate([
            'name' => [
                'required', 'string', 'max:50',
                Rule::unique('quiz_categories', 'name')->where(fn ($q) => $q->where('faculty_id', $facultyId)),
            ],
            'sort_order' => 'nullable|integer|min:0',
        ]);

        QuizCategory::create([
            'name' => trim($request->name),
            'sort_order' => (int) ($request->sort_order ?? 0),
            'faculty_id' => $facultyId,
        ]);

        return redirect()->route('dashboard.coordinators.quiz-categories.index')
            ->with('success', 'Quiz category created.');
    }

    public function edit(QuizCategory $quizCategory): View
    {
        AcademicCatalogScope::assertCanAccess($this->adminUser(), $quizCategory);

        return view('admin.coordinators.quizsnap.quiz-categories.edit', compact('quizCategory'));
    }

    public function update(Request $request, QuizCategory $quizCategory): RedirectResponse
    {
        $user = $this->adminUser();
        AcademicCatalogScope::assertCanAccess($user, $quizCategory);

        $request->validate([
            'name' => [
                'required', 'string', 'max:50',
                Rule::unique('quiz_categories', 'name')
                    ->where(fn ($q) => $q->where('faculty_id', $quizCategory->faculty_id))
                    ->ignore($quizCategory->id),
            ],
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $quizCategory->update([
            'name' => trim($request->name),
            'sort_order' => (int) ($request->sort_order ?? 0),
        ]);

        return redirect()->route('dashboard.coordinators.quiz-categories.index')
            ->with('success', 'Quiz category updated.');
    }

    public function destroy(QuizCategory $quizCategory): RedirectResponse
    {
        AcademicCatalogScope::assertCanAccess($this->adminUser(), $quizCategory);
        if ($quizCategory->courses()->exists() || $quizCategory->academicClasses()->exists() || $quizCategory->quizzes()->exists()) {
            return back()->with('error', 'Cannot delete category with courses, classes, or quizzes.');
        }
        $quizCategory->delete();

        return redirect()->route('dashboard.coordinators.quiz-categories.index')
            ->with('success', 'Quiz category deleted.');
    }
}
