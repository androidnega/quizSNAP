<?php

namespace App\Http\Controllers\Admin\Operations;

use App\Http\Controllers\Controller;
use App\Services\Operations\OperationsCommandCenterService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class OperationsOverviewController extends Controller
{
    public function index(OperationsCommandCenterService $service): View
    {
        return view('admin.operations.command-center.index', [
            'payload' => $service->payload(),
        ]);
    }

    public function live(OperationsCommandCenterService $service): JsonResponse
    {
        return response()->json($service->payload());
    }

    public function wallboard(OperationsCommandCenterService $service): View
    {
        return view('admin.operations.wallboard.index', [
            'payload' => $service->payload(),
        ]);
    }

    public function wallboardLive(OperationsCommandCenterService $service): JsonResponse
    {
        return response()->json($service->payload());
    }
}
