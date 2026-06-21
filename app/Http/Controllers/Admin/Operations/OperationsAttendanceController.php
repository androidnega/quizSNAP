<?php

namespace App\Http\Controllers\Admin\Operations;

use App\Http\Controllers\Controller;
use App\Services\Operations\OperationsAttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class OperationsAttendanceController extends Controller
{
    public function index(OperationsAttendanceService $service): View
    {
        return view('admin.operations.attendance.index', [
            'snapshot' => $service->snapshot(),
        ]);
    }

    public function live(OperationsAttendanceService $service): JsonResponse
    {
        return response()->json($service->snapshot());
    }
}
