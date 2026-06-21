<?php

namespace App\Services\Monitoring;

use App\Events\Monitoring\MonitoringErrorOccurred;
use App\Models\MonitoringSetting;
use App\Models\SystemError;
use App\Models\SystemErrorOccurrence;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;
use Throwable;

class ErrorMonitoringService
{
    protected static array $severityMap = [
        \Illuminate\Validation\ValidationException::class => SystemError::SEVERITY_WARNING,
        \Illuminate\Auth\AuthenticationException::class => SystemError::SEVERITY_WARNING,
        \Illuminate\Auth\Access\AuthorizationException::class => SystemError::SEVERITY_WARNING,
        \Illuminate\Database\QueryException::class => SystemError::SEVERITY_ERROR,
        \PDOException::class => SystemError::SEVERITY_CRITICAL,
        \TypeError::class => SystemError::SEVERITY_CRITICAL,
        \Symfony\Component\ErrorHandler\Error\FatalError::class => SystemError::SEVERITY_FATAL,
        \Error::class => SystemError::SEVERITY_FATAL,
    ];

    public function capture(Throwable $exception, ?Request $request = null): ?SystemError
    {
        if (! Schema::hasTable('system_errors')) {
            return null;
        }

        try {
            $request = $request ?? request();
            $severity = $this->classifySeverity($exception);
            $fingerprint = $this->buildFingerprint($exception);
            $sourceContext = $this->extractSourceContext($exception);
            $user = auth()->user();
            $userId = $user instanceof User ? $user->id : null;
            $agent = $this->parseUserAgent($request?->userAgent());

            $error = SystemError::query()->where('fingerprint', $fingerprint)->first();

            if ($error) {
                $affectedIds = $error->affected_user_ids ?? [];
                if ($userId && ! in_array($userId, $affectedIds, true)) {
                    $affectedIds[] = $userId;
                }

                $error->update([
                    'occurrence_count' => $error->occurrence_count + 1,
                    'affected_users_count' => count($affectedIds),
                    'affected_user_ids' => $affectedIds,
                    'last_seen_at' => now(),
                    'severity' => $this->maxSeverity($error->severity, $severity),
                ]);
            } else {
                $error = SystemError::query()->create([
                    'fingerprint' => $fingerprint,
                    'exception_class' => get_class($exception),
                    'exception_type' => Str::limit(class_basename($exception), 128, ''),
                    'message' => Str::limit($exception->getMessage(), 2000),
                    'error_code' => (string) $exception->getCode(),
                    'severity' => $severity,
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'class_name' => $this->extractClassFromTrace($exception),
                    'method' => $this->extractMethodFromTrace($exception),
                    'route' => $request?->route()?->getName(),
                    'url' => $request?->fullUrl(),
                    'http_method' => $request?->method(),
                    'source_context' => $sourceContext,
                    'occurrence_count' => 1,
                    'affected_users_count' => $userId ? 1 : 0,
                    'affected_user_ids' => $userId ? [$userId] : [],
                    'resolution_status' => SystemError::STATUS_OPEN,
                    'first_seen_at' => now(),
                    'last_seen_at' => now(),
                ]);
            }

            SystemErrorOccurrence::query()->create([
                'system_error_id' => $error->id,
                'user_id' => $userId,
                'user_name' => $user instanceof User ? $user->name : null,
                'user_role' => $user instanceof User ? $user->role : null,
                'session_id' => $request?->session()?->getId(),
                'browser' => $agent['browser'] ?? null,
                'device' => $agent['device'] ?? null,
                'operating_system' => $agent['os'] ?? null,
                'ip_address' => $request?->ip(),
                'environment' => app()->environment(),
                'request_payload' => $this->sanitizePayload($request),
                'stack_trace' => $exception->getTraceAsString(),
                'occurred_at' => now(),
            ]);

            if (in_array($severity, [SystemError::SEVERITY_CRITICAL, SystemError::SEVERITY_FATAL, SystemError::SEVERITY_ERROR], true)) {
                broadcast(new MonitoringErrorOccurred($error->fresh(), $severity))->toOthers();
                app(MonitoringNotificationService::class)->notifyForError($error, $severity);
            }

            return $error;
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    public function classifySeverity(Throwable $exception): string
    {
        foreach (self::$severityMap as $class => $severity) {
            if ($exception instanceof $class) {
                return $severity;
            }
        }

        return SystemError::SEVERITY_ERROR;
    }

    public function buildFingerprint(Throwable $exception): string
    {
        return hash('sha256', implode('|', [
            get_class($exception),
            $exception->getFile(),
            $exception->getLine(),
            Str::limit($exception->getMessage(), 500),
        ]));
    }

    public function extractSourceContext(Throwable $exception, int $contextLines = 5): ?array
    {
        $file = $exception->getFile();
        $line = $exception->getLine();

        if (! is_readable($file)) {
            return null;
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return null;
        }

        $start = max(0, $line - $contextLines - 1);
        $end = min(count($lines), $line + $contextLines);
        $snippet = [];

        for ($i = $start; $i < $end; $i++) {
            $snippet[$i + 1] = $lines[$i];
        }

        return [
            'file' => $file,
            'line' => $line,
            'lines' => $snippet,
        ];
    }

    protected function extractClassFromTrace(Throwable $exception): ?string
    {
        $trace = $exception->getTrace();
        $frame = $trace[0] ?? null;

        return $frame['class'] ?? null;
    }

    protected function extractMethodFromTrace(Throwable $exception): ?string
    {
        $trace = $exception->getTrace();
        $frame = $trace[0] ?? null;

        return $frame['function'] ?? null;
    }

    protected function sanitizePayload(?Request $request): ?array
    {
        if (! $request) {
            return null;
        }

        $data = $request->except(['password', 'password_confirmation', '_token', 'current_password']);

        return json_decode(json_encode($data), true);
    }

    protected function parseUserAgent(?string $userAgent): array
    {
        if (! $userAgent || ! class_exists(Agent::class)) {
            return $this->parseUserAgentFallback($userAgent);
        }

        $agent = new Agent;
        $agent->setUserAgent($userAgent);

        return [
            'browser' => $agent->browser(),
            'device' => $agent->device() ?: ($agent->isMobile() ? 'Mobile' : 'Desktop'),
            'os' => $agent->platform(),
        ];
    }

    protected function parseUserAgentFallback(?string $userAgent): array
    {
        if (! $userAgent) {
            return [];
        }

        return [
            'browser' => Str::limit($userAgent, 64),
            'device' => str_contains(strtolower($userAgent), 'mobile') ? 'Mobile' : 'Desktop',
            'os' => null,
        ];
    }

    protected function maxSeverity(string $current, string $incoming): string
    {
        $order = [
            SystemError::SEVERITY_INFO => 1,
            SystemError::SEVERITY_WARNING => 2,
            SystemError::SEVERITY_ERROR => 3,
            SystemError::SEVERITY_CRITICAL => 4,
            SystemError::SEVERITY_FATAL => 5,
        ];

        return ($order[$incoming] ?? 0) >= ($order[$current] ?? 0) ? $incoming : $current;
    }

    public function slowQueryThresholdMs(): int
    {
        return (int) (MonitoringSetting::get('slow_query_threshold_ms') ?? 500);
    }
}
