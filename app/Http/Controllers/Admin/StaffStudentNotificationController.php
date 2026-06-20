<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Http\Controllers\Controller;
use App\Models\ClassGroup;
use App\Services\StudentNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffStudentNotificationController extends Controller
{
    use InteractsWithAdminSession;

    public function create(): View
    {
        $user = $this->adminUser();
        abort_unless($user && ($user->isExaminer() || $user->isCoordinator() || $user->isSuperAdmin()), 403);

        $classGroups = ClassGroup::query()
            ->when(! $user->isSuperAdmin(), fn ($q) => $q->whereIn('id', $user->classGroupIds()))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.student-notifications.create', [
            'classGroups' => $classGroups,
        ]);
    }

    public function store(Request $request, StudentNotificationService $notifications): RedirectResponse
    {
        $user = $this->adminUser();
        abort_unless($user && ($user->isExaminer() || $user->isCoordinator() || $user->isSuperAdmin()), 403);

        $allowedIds = $user->isSuperAdmin()
            ? ClassGroup::pluck('id')->all()
            : $user->classGroupIds();

        $validated = $request->validate([
            'class_group_id' => 'required|integer|in:'.implode(',', $allowedIds ?: [0]),
            'title' => 'required|string|max:120',
            'body' => 'required|string|max:2000',
        ]);

        $sent = $notifications->sendStaffMessage(
            (int) $validated['class_group_id'],
            trim($validated['title']),
            trim($validated['body']),
            (int) $user->id,
        );

        return redirect()
            ->route('dashboard.student-notifications.create')
            ->with('success', $sent > 0
                ? "Message sent to {$sent} student(s)."
                : 'No students found in that class group.');
    }
}
