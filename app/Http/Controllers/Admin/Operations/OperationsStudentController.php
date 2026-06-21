<?php

namespace App\Http\Controllers\Admin\Operations;

use App\Http\Controllers\Controller;
use App\Services\Operations\OperationsStudentMonitorService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class OperationsStudentController extends Controller
{
    public function index(OperationsStudentMonitorService $service): View
    {
        return view('admin.operations.students.index', [
            'snapshot' => $service->snapshot(),
        ]);
    }

    public function live(OperationsStudentMonitorService $service): JsonResponse
    {
        return response()->json($service->snapshot());
    }
}
