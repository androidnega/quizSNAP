<?php

namespace App\Http\Controllers\Admin\Monitoring;

use App\Http\Controllers\Controller;
use App\Models\SystemError;
use App\Services\Monitoring\MonitoringLogMaintenanceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        $errors = $this->errorsQuery($request)->limit(500)->get();
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

    public function downloadErrors(Request $request): StreamedResponse
    {
        $format = $this->resolveDownloadFormat($request);
        $errors = $this->errorsQuery($request)->limit(500)->get();

        return $this->streamErrorsDownload($errors, $format, $request);
    }

    public function downloadError(Request $request, SystemError $error): StreamedResponse
    {
        $format = $this->resolveDownloadFormat($request);

        return $this->streamErrorsDownload(collect([$error]), $format, $request, 'error-'.$error->id);
    }

    protected function resolveDownloadFormat(Request $request): string
    {
        $format = strtolower((string) $request->query('format', 'txt'));

        if (! in_array($format, ['json', 'txt'], true)) {
            abort(400, 'Invalid download format. Use json or txt.');
        }

        return $format;
    }

    protected function errorsQuery(Request $request): Builder
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

        return $query;
    }

    protected function streamErrorsDownload($errors, string $format, Request $request, ?string $nameSuffix = null): StreamedResponse
    {
        $filename = 'quizsnap-errors-'.($nameSuffix ?? now()->format('Y-m-d-His')).'.'.$format;

        if ($format === 'json') {
            $payload = [
                'exported_at' => now()->toIso8601String(),
                'count' => $errors->count(),
                'filters' => array_filter($request->only(['search', 'severity', 'status', 'ids'])),
                'errors' => $errors->map(fn (SystemError $error) => $this->errorToArray($error))->values()->all(),
            ];
            $content = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            return response()->streamDownload(
                static fn () => print($content),
                $filename,
                ['Content-Type' => 'application/json; charset=UTF-8']
            );
        }

        $text = $errors->map(fn (SystemError $error) => $this->formatError($error))->implode("\n\n---\n\n");

        return response()->streamDownload(
            static fn () => print($text),
            $filename,
            ['Content-Type' => 'text/plain; charset=UTF-8']
        );
    }

    protected function errorToArray(SystemError $error): array
    {
        return [
            'id' => $error->id,
            'fingerprint' => $error->fingerprint,
            'severity' => $error->severity,
            'resolution_status' => $error->resolution_status,
            'exception_class' => $error->exception_class,
            'exception_type' => $error->exception_type,
            'message' => $error->message,
            'error_code' => $error->error_code,
            'file' => $error->file,
            'line' => $error->line,
            'class_name' => $error->class_name,
            'method' => $error->method,
            'route' => $error->route,
            'url' => $error->url,
            'http_method' => $error->http_method,
            'source_context' => $error->source_context,
            'occurrence_count' => $error->occurrence_count,
            'affected_users_count' => $error->affected_users_count,
            'affected_user_ids' => $error->affected_user_ids,
            'first_seen_at' => $error->first_seen_at?->toIso8601String(),
            'last_seen_at' => $error->last_seen_at?->toIso8601String(),
        ];
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
