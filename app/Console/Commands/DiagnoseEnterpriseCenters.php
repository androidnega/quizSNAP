<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Monitoring\MonitoringOverviewService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

class DiagnoseEnterpriseCenters extends Command
{
    protected $signature = 'monitoring:diagnose {username? : Staff username to check, e.g. manuel}';

    protected $description = 'Diagnose Monitoring, Operations, and Intelligence center access on this server';

    public function handle(): int
    {
        $this->info('Enterprise center diagnostics');
        $this->line('Git: ' . trim((string) shell_exec('git log -1 --oneline 2>/dev/null')));
        $this->newLine();

        $routes = [
            'dashboard.monitoring.overview' => '/dashboard/monitoring',
            'dashboard.operations.index' => '/dashboard/operations',
            'dashboard.intelligence.index' => '/dashboard/intelligence',
        ];

        $routeRows = [];
        foreach ($routes as $name => $path) {
            $registered = Route::has($name);
            $routeRows[] = [
                $name,
                $path,
                $registered ? 'yes' : 'MISSING',
            ];
        }
        $this->table(['Route name', 'Path', 'Registered'], $routeRows);

        $tables = [
            'monitoring_user_sessions',
            'server_health_snapshots',
            'system_errors',
            'monitoring_notifications',
        ];
        $this->newLine();
        $this->line('Monitoring tables:');
        foreach ($tables as $table) {
            $this->line('  ' . $table . ': ' . (Schema::hasTable($table) ? 'ok' : 'MISSING'));
        }

        $username = (string) ($this->argument('username') ?? 'manuel');
        $user = User::whereRaw('LOWER(TRIM(username)) = ?', [strtolower(trim($username))])->first();
        $this->newLine();
        if (! $user) {
            $this->error("User \"{$username}\" not found.");

            return Command::FAILURE;
        }

        $this->table(['Field', 'Value'], [
            ['id', (string) $user->id],
            ['username', $user->username],
            ['role', $user->role],
            ['canAccessMonitoring', $user->canAccessMonitoring() ? 'yes' : 'no'],
            ['canAccessOperations', $user->canAccessOperations() ? 'yes' : 'no'],
            ['canAccessIntelligence', $user->canAccessIntelligence() ? 'yes' : 'no'],
        ]);

        if (! $user->canAccessMonitoring()) {
            $this->warn('This user cannot access enterprise centers. Role must be system_admin.');

            return Command::FAILURE;
        }

        try {
            app(MonitoringOverviewService::class)->dashboardStats();
            $this->info('MonitoringOverviewService::dashboardStats() OK');
        } catch (\Throwable $e) {
            $this->error('MonitoringOverviewService failed: ' . $e->getMessage());

            return Command::FAILURE;
        }

        if (! View::exists('admin.monitoring.overview')) {
            $this->error('View admin.monitoring.overview is missing on this server.');

            return Command::FAILURE;
        }

        $this->info('View admin.monitoring.overview exists');
        $this->newLine();
        $this->line('If the browser still returns to /dashboard:');
        $this->line('  1. Run: bash deploy.sh');
        $this->line('  2. Log out and log back in as ' . $user->username);
        $this->line('  3. Hard refresh (Cmd/Ctrl+Shift+R)');
        $this->line('  4. Open /dashboard/monitoring and check the address bar stays on that URL');

        return Command::SUCCESS;
    }
}
