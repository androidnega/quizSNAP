<?php

namespace App\Http\Controllers\Admin\Monitoring;

use App\Http\Controllers\Controller;
use App\Services\Monitoring\QueueMonitoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MonitoringQueueController extends Controller
{
    public function index(QueueMonitoringService $queue): View
    {
        return view('admin.monitoring.queue.index', [
            'stats' => $queue->stats(),
            'failedJobs' => $queue->failedJobs(),
        ]);
    }

    public function failedJobs(QueueMonitoringService $queue): View
    {
        return view('admin.monitoring.failed-jobs.index', [
            'failedJobs' => $queue->failedJobs(50),
        ]);
    }

    public function retry(Request $request, QueueMonitoringService $queue): RedirectResponse|JsonResponse
    {
        $uuid = $request->input('uuid');
        if (! $uuid) {
            return back()->with('error', 'Job UUID required.');
        }
        $queue->retry($uuid);

        return $request->expectsJson()
            ? response()->json(['success' => true])
            : back()->with('success', 'Job queued for retry.');
    }

    public function retryAll(QueueMonitoringService $queue): RedirectResponse
    {
        $queue->retryAll();

        return back()->with('success', 'All failed jobs queued for retry.');
    }

    public function delete(Request $request, QueueMonitoringService $queue): RedirectResponse|JsonResponse
    {
        $uuid = $request->input('uuid');
        if (! $uuid) {
            return back()->with('error', 'Job UUID required.');
        }
        $queue->deleteFailed($uuid);

        return $request->expectsJson()
            ? response()->json(['success' => true])
            : back()->with('success', 'Failed job deleted.');
    }

    public function deleteAll(QueueMonitoringService $queue): RedirectResponse
    {
        $count = $queue->deleteAllFailed();

        return back()->with('success', "Deleted {$count} failed jobs.");
    }
}
