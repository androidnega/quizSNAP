<?php

namespace App\Http\Controllers\Admin\Operations;

use App\Http\Controllers\Controller;
use App\Models\OperationsExamIncident;
use App\Models\User;
use App\Services\Operations\OperationsExamIncidentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OperationsIncidentController extends Controller
{
    public function index(OperationsExamIncidentService $incidents): View
    {
        return view('admin.operations.incidents.index', [
            'open' => $incidents->listOpen(100),
            'history' => $incidents->history(50),
        ]);
    }

    public function store(Request $request, OperationsExamIncidentService $incidents): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'severity' => 'required|in:critical,high,medium,low',
            'incident_type' => 'nullable|string|max:64',
            'quiz_id' => 'nullable|integer',
            'quiz_session_id' => 'nullable|integer',
            'assigned_to' => 'nullable|integer|exists:users,id',
        ]);

        $assignee = isset($validated['assigned_to']) ? User::find($validated['assigned_to']) : null;

        $incidents->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'severity' => $validated['severity'],
            'incident_type' => $validated['incident_type'] ?? null,
            'quiz_id' => $validated['quiz_id'] ?? null,
            'quiz_session_id' => $validated['quiz_session_id'] ?? null,
            'assigned_to' => $assignee?->id,
            'assigned_to_name' => $assignee?->name,
        ], auth()->user());

        return back()->with('success', 'Incident created.');
    }

    public function assign(Request $request, OperationsExamIncident $incident, OperationsExamIncidentService $incidents): RedirectResponse
    {
        $request->validate(['assigned_to' => 'required|integer|exists:users,id']);
        $user = User::findOrFail($request->input('assigned_to'));
        $incidents->assign($incident, $user);

        return back()->with('success', 'Incident assigned.');
    }

    public function resolve(Request $request, OperationsExamIncident $incident, OperationsExamIncidentService $incidents): RedirectResponse
    {
        $incidents->resolve($incident, $request->input('notes'));

        return back()->with('success', 'Incident resolved.');
    }
}
