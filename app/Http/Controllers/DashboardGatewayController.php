<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Student\StudentDashboardController;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class DashboardGatewayController extends Controller
{
    public function __invoke(): View|RedirectResponse
    {
        if (session('student_id')) {
            return app(StudentDashboardController::class)->index();
        }

        return app(AdminDashboardController::class)->index();
    }
}
