<?php

namespace App\Services\Monitoring;

use App\Models\MonitoringUserSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;

class SessionTerminationService
{
    public function terminate(string $sessionId): bool
    {
        $record = MonitoringUserSession::query()->where('session_id', $sessionId)->first();
        if (! $record) {
            return false;
        }

        $this->destroySessionStorage($sessionId);
        $record->update(['is_active' => false]);

        app(SecurityMonitoringService::class)->record(
            'session_terminated',
            'Monitoring admin terminated session '.$sessionId,
            'warning',
            ['session_id' => $sessionId, 'user_id' => $record->user_id]
        );

        return true;
    }

    public function forceLogoutUser(int $userId): int
    {
        $sessions = MonitoringUserSession::query()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->get();

        $count = 0;
        foreach ($sessions as $session) {
            if ($this->terminate($session->session_id)) {
                $count++;
            }
        }

        return $count;
    }

    protected function destroySessionStorage(string $sessionId): void
    {
        $driver = config('session.driver');

        match ($driver) {
            'database' => $this->destroyDatabaseSession($sessionId),
            'redis' => $this->destroyRedisSession($sessionId),
            'file' => $this->destroyFileSession($sessionId),
            default => null,
        };
    }

    protected function destroyDatabaseSession(string $sessionId): void
    {
        $table = config('session.table', 'sessions');
        if (Schema::hasTable($table)) {
            DB::table($table)->where('id', $sessionId)->delete();
        }
    }

    protected function destroyRedisSession(string $sessionId): void
    {
        try {
            $connection = config('session.connection') ?? 'default';
            $prefix = config('cache.prefix', '').'';
            Redis::connection($connection)->del($this->redisSessionKey($sessionId, $prefix));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function destroyFileSession(string $sessionId): void
    {
        $path = config('session.files', storage_path('framework/sessions')).'/'.$sessionId;
        if (File::exists($path)) {
            File::delete($path);
        }
    }

    protected function redisSessionKey(string $sessionId, string $prefix): string
    {
        return $prefix.'laravel_session:'.$sessionId;
    }
}
