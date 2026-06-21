<?php

namespace App\Http\Controllers\Admin\Operations;

use App\Http\Controllers\Controller;
use App\Services\Operations\OperationsProctoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class OperationsProctoringController extends Controller
{
    public function index(OperationsProctoringService $service): View
    {
        return view('admin.operations.proctoring.index', [
            'snapshot' => $service->snapshot(),
        ]);
    }

    public function live(OperationsProctoringService $service): JsonResponse
    {
        return response()->json($service->snapshot());
    }
}
