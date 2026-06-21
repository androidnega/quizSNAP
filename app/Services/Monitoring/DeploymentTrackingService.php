<?php

namespace App\Services\Monitoring;

use App\Models\MonitoringDeployment;
use App\Models\User;
use Illuminate\Support\Facades\Process;

class DeploymentTrackingService
{
    public function recordFromEnvironment(?User $user = null, ?string $notes = null): MonitoringDeployment
    {
        return MonitoringDeployment::query()->create([
            'version' => config('app.version', config('app.env')),
            'git_commit' => $this->gitCommit(),
            'branch' => $this->gitBranch(),
            'deployed_by' => $user?->id,
            'deployed_by_name' => $user?->name,
            'notes' => $notes,
            'meta' => ['php' => PHP_VERSION, 'laravel' => app()->version()],
            'deployed_at' => now(),
        ]);
    }

    public function history(int $limit = 50)
    {
        return MonitoringDeployment::query()->orderByDesc('deployed_at')->limit($limit)->get();
    }

    protected function gitCommit(): ?string
    {
        try {
            $result = Process::run('git rev-parse --short HEAD');

            return $result->successful() ? trim($result->output()) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function gitBranch(): ?string
    {
        try {
            $result = Process::run('git rev-parse --abbrev-ref HEAD');

            return $result->successful() ? trim($result->output()) : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
