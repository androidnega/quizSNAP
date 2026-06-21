<?php

namespace App\Http\Controllers\Admin\Monitoring;

use App\Http\Controllers\Controller;
use App\Models\MonitoringIncident;
use App\Services\Monitoring\BackupMonitoringService;
use App\Services\Monitoring\CommandCenterService;
use App\Services\Monitoring\DatabaseCapacityService;
use App\Services\Monitoring\DeploymentTrackingService;
use App\Services\Monitoring\IncidentManagementService;
use App\Services\Monitoring\StorageCapacityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MonitoringOpsController extends Controller
{
    public function commandCenter(CommandCenterService $service): View
    {
        return view('admin.monitoring.command-center.index', [
            'payload' => $service->payload(),
        ]);
    }

    public function commandCenterLive(CommandCenterService $service): JsonResponse
    {
        return response()->json($service->payload());
    }

    public function backups(BackupMonitoringService $backups): View
    {
        return view('admin.monitoring.backups.index', [
            'latest' => $backups->latest(),
            'history' => $backups->history(),
        ]);
    }

    public function scanBackups(BackupMonitoringService $backups): RedirectResponse
    {
        $backups->scan();

        return back()->with('success', 'Backup scan completed.');
    }

    public function deployments(DeploymentTrackingService $deployments): View
    {
        return view('admin.monitoring.deployments.index', [
            'deployments' => $deployments->history(),
        ]);
    }

    public function recordDeployment(Request $request, DeploymentTrackingService $deployments): RedirectResponse
    {
        $deployments->recordFromEnvironment(auth()->user(), $request->input('notes'));

        return back()->with('success', 'Deployment recorded.');
    }

    public function incidents(IncidentManagementService $incidents): View
    {
        return view('admin.monitoring.incidents.index', [
            'incidents' => $incidents->listOpen(100),
        ]);
    }

    public function storeIncident(Request $request, IncidentManagementService $incidents): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'severity' => 'required|in:P1,P2,P3,P4',
            'affected_services' => 'nullable|string',
        ]);

        $incidents->create([
            'title' => $validated['title'],
            'severity' => $validated['severity'],
            'affected_services' => array_filter(array_map('trim', explode(',', $validated['affected_services'] ?? ''))),
        ], auth()->user());

        return back()->with('success', 'Incident created.');
    }

    public function resolveIncident(Request $request, MonitoringIncident $incident, IncidentManagementService $incidents): RedirectResponse
    {
        $incidents->updateStatus($incident, MonitoringIncident::STATUS_RESOLVED, $request->input('notes'));

        return back()->with('success', 'Incident resolved.');
    }

    public function databaseCapacity(DatabaseCapacityService $capacity): View
    {
        return view('admin.monitoring.capacity.database', [
            'latest' => $capacity->latest(),
        ]);
    }

    public function storageCapacity(StorageCapacityService $capacity): View
    {
        return view('admin.monitoring.capacity.storage', [
            'latest' => $capacity->latest(),
        ]);
    }
}
