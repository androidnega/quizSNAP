<?php

namespace App\Http\Controllers\Admin\Monitoring;

use App\Http\Controllers\Controller;
use App\Models\SystemError;
use App\Models\SystemErrorOccurrence;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MonitoringErrorController extends Controller
{
    public function index(Request $request): View
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('system_errors')) {
            return view('admin.monitoring.errors.index', [
                'errors' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 25),
                'exportAllUrl' => route('dashboard.monitoring.errors.export'),
            ]);
        }

        $query = SystemError::query()->withCount('occurrences')->orderByDesc('last_seen_at');

        if ($severity = $request->query('severity')) {
            $query->where('severity', $severity);
        }
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('message', 'like', "%{$search}%")
                    ->orWhere('exception_class', 'like', "%{$search}%")
                    ->orWhere('file', 'like', "%{$search}%");
            });
        }
        if ($status = $request->query('status')) {
            $query->where('resolution_status', $status);
        }

        return view('admin.monitoring.errors.index', [
            'errors' => $query->paginate(25)->withQueryString(),
            'exportAllUrl' => route('dashboard.monitoring.errors.export').(
                ($q = http_build_query(array_filter($request->only(['search', 'severity', 'status'])))) ? '?'.$q : ''
            ),
        ]);
    }

    public function show(SystemError $error): View
    {
        $error->load(['occurrences' => fn ($q) => $q->orderByDesc('occurred_at')->limit(20)]);

        return view('admin.monitoring.errors.show', compact('error'));
    }

    public function resolve(SystemError $error): RedirectResponse
    {
        $error->update(['resolution_status' => SystemError::STATUS_RESOLVED]);

        return back()->with('success', 'Error marked as resolved.');
    }

    public function ignore(SystemError $error): RedirectResponse
    {
        $error->update(['resolution_status' => SystemError::STATUS_IGNORED]);

        return back()->with('success', 'Error ignored.');
    }

    public function feed(): View
    {
        $occurrences = SystemErrorOccurrence::query()
            ->with('systemError')
            ->orderByDesc('occurred_at')
            ->limit(50)
            ->get();

        return view('admin.monitoring.errors.feed', compact('occurrences'));
    }
}
