<?php

namespace App\Http\Controllers;

use App\Models\SystemError;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class ServerLogsController extends Controller
{
    private const DEFAULT_SECRET = 'QuizSnapMigrate2026Xp9k3m7';

    /**
     * Return recent server logs for debugging (key required).
     * Visit: /maintenance/logs?key=YOUR_SECRET&lines=200
     */
    public function __invoke(Request $request): Response
    {
        $secret = env('MIGRATION_RUN_KEY', self::DEFAULT_SECRET);
        if ($request->query('key') !== $secret) {
            return response('Invalid or missing key.', 403, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        }

        $lines = min(500, max(1, (int) $request->query('lines', 200)));
        $sections = [];

        $sections[] = 'QuizSnap server logs (last '.$lines.' lines per file)';
        $sections[] = 'Generated: '.now()->toIso8601String();
        $sections[] = str_repeat('=', 60);

        foreach ($this->logSources() as $label => $path) {
            $sections[] = '';
            $sections[] = '=== '.$label.' ===';
            $sections[] = 'Path: '.$path;
            if (! is_readable($path)) {
                $sections[] = '(not readable or missing)';
                continue;
            }
            $sections[] = $this->tailFile($path, $lines);
        }

        $sections[] = '';
        $sections[] = '=== Recent application errors (database) ===';
        $sections[] = $this->recentSystemErrors($lines);

        return response(implode("\n", $sections), 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function logSources(): array
    {
        $sources = [];

        $logsDir = storage_path('logs');
        $laravelLogs = glob($logsDir.'/laravel*.log') ?: [];
        usort($laravelLogs, fn ($a, $b) => filemtime($b) <=> filemtime($a));
        if ($laravelLogs !== []) {
            foreach (array_slice($laravelLogs, 0, 3) as $index => $path) {
                $sources[$index === 0 ? 'Laravel' : 'Laravel ('.basename($path).')'] = $path;
            }
        } else {
            $sources['Laravel'] = $logsDir.'/laravel.log';
        }

        $optional = [
            'Nginx error' => '/var/log/nginx/quizsnap-error.log',
            'Nginx error (default)' => '/var/log/nginx/error.log',
            'Supervisor Reverb' => '/var/log/supervisor/quizsnap-reverb.log',
            'Supervisor queue' => '/var/log/supervisor/quizsnap-queue.log',
            'Supervisor worker' => '/var/log/supervisor/quizsnap-worker.log',
            'PHP-FPM' => '/var/log/php8.3-fpm.log',
        ];

        foreach ($optional as $label => $path) {
            if (is_readable($path)) {
                $sources[$label] = $path;
            }
        }

        return $sources;
    }

    private function tailFile(string $file, int $lines): string
    {
        try {
            $content = @file($file, FILE_IGNORE_NEW_LINES);
            if ($content === false) {
                return '(read failed)';
            }

            return implode("\n", array_slice($content, -$lines));
        } catch (\Throwable $e) {
            return '(error: '.$e->getMessage().')';
        }
    }

    private function recentSystemErrors(int $limit): string
    {
        if (! Schema::hasTable('system_errors')) {
            return '(system_errors table not available)';
        }

        try {
            $errors = SystemError::query()
                ->where('resolution_status', 'open')
                ->orderByDesc('last_seen_at')
                ->limit(min(50, $limit))
                ->get(['exception_class', 'message', 'route', 'url', 'severity', 'occurrence_count', 'last_seen_at']);

            if ($errors->isEmpty()) {
                return '(no open errors recorded)';
            }

            $lines = [];
            foreach ($errors as $error) {
                $lines[] = sprintf(
                    '[%s] %s x%d | %s | route=%s | %s',
                    $error->last_seen_at?->toDateTimeString() ?? '-',
                    $error->severity,
                    $error->occurrence_count,
                    class_basename((string) $error->exception_class),
                    $error->route ?? '-',
                    $error->message
                );
            }

            return implode("\n", $lines);
        } catch (\Throwable $e) {
            return '(could not read system errors: '.$e->getMessage().')';
        }
    }
}
