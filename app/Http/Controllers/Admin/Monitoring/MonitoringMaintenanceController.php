<?php

namespace App\Http\Controllers\Admin\Monitoring;

use App\Http\Controllers\Controller;
use App\Models\SystemError;
use App\Services\Monitoring\MonitoringLogMaintenanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MonitoringMaintenanceController extends Controller
{
    public function clearLogs(Request $request, MonitoringLogMaintenanceService $maintenance): RedirectResponse
    {
        $validated = $request->validate([
            'category' => 'required|string|in:'.implode(',', array_merge(array_keys($maintenance->categories()), ['all'])),
            'confirm' => 'required|accepted',
        ]);

        $result = $maintenance->clear($validated['category']);
        $deleted = (int) ($result['deleted'] ?? 0);
        $label = $validated['category'] === 'all'
            ? 'all monitoring logs'
            : ($maintenance->categories()[$validated['category']] ?? $validated['category']);

        return back()->with('success', "Cleared {$deleted} {$label} record(s).");
    }

    public function exportErrors(Request $request): JsonResponse
    {
        $query = SystemError::query()->orderByDesc('last_seen_at');

        if ($ids = $request->input('ids')) {
            $idList = is_array($ids) ? $ids : explode(',', (string) $ids);
            $query->whereIn('id', array_filter(array_map('intval', $idList)));
        } else {
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
        }

        $errors = $query->limit(500)->get();
        $text = $errors->map(fn (SystemError $error) => $this->formatError($error))->implode("\n\n---\n\n");

        return response()->json([
            'count' => $errors->count(),
            'text' => $text,
        ]);
    }

    public function exportError(SystemError $error): JsonResponse
    {
        return response()->json([
            'text' => $this->formatError($error),
        ]);
    }

    protected function formatError(SystemError $error): string
    {
        $lines = [
            'Severity: '.$error->severity,
            'Status: '.$error->resolution_status,
            'Exception: '.$error->exception_class,
            'Message: '.$error->message,
            'File: '.($error->file ?? '—').':'.($error->line ?? '—'),
            'Route: '.($error->route ?? '—'),
            'URL: '.($error->url ?? '—'),
            'Occurrences: '.$error->occurrence_count,
            'Affected users: '.$error->affected_users_count,
            'First seen: '.($error->first_seen_at?->toDateTimeString() ?? '—'),
            'Last seen: '.($error->last_seen_at?->toDateTimeString() ?? '—'),
        ];

        if ($error->source_context) {
            $lines[] = 'Source context: '.json_encode($error->source_context, JSON_PRETTY_PRINT);
        }

        return implode("\n", $lines);
    }
}
