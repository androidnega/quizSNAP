<?php

namespace App\Http\Controllers\Admin\Monitoring;

use App\Http\Controllers\Controller;
use App\Models\QuizViolation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MonitoringStudentActivityController extends Controller
{
    public function index(Request $request): View
    {
        $query = QuizViolation::query()
            ->with(['quizSession.student', 'quizSession.quiz'])
            ->orderByDesc('occurred_at');

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }
        if ($search = $request->query('search')) {
            $query->whereHas('quizSession.student', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('index_number', 'like', "%{$search}%");
            });
        }

        return view('admin.monitoring.student-activities.index', [
            'activities' => $query->paginate(40)->withQueryString(),
            'types' => QuizViolation::types(),
        ]);
    }
}
