<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Student\StudentDashboardController;
use App\Models\Student;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class DashboardGatewayController extends Controller
{
    public function __invoke(): View|RedirectResponse
    {
        if (session('student_id')) {
            $student = Student::find(session('student_id'));
            if ($student) {
                return app(StudentDashboardController::class)->index();
            }
            \App\Support\StudentSession::clear();
        }

        if (session('student_index') && ! session('admin_authenticated')) {
            return redirect()->route('student.account.login.form')
                ->with('error', 'Your session expired. Please sign in again.');
        }

        return app(AdminDashboardController::class)->index();
    }
}
