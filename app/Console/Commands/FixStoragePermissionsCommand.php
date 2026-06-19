<?php

namespace App\Console\Commands;

use App\Support\StoragePermissions;
use Illuminate\Console\Command;

class FixStoragePermissionsCommand extends Command
{
    protected $signature = 'storage:fix-permissions';

    protected $description = 'Fix storage and bootstrap/cache permissions so the web server can write logs, sessions, and compiled views';

    public function handle(): int
    {
        $result = StoragePermissions::fix(base_path());

        foreach ($result['lines'] as $line) {
            $this->line($line);
        }

        if ($result['ok']) {
            $this->info('Storage permissions are OK.');

            return self::SUCCESS;
        }

        $this->error('Storage is not fully writable by the web server.');
        if ($result['chown_hint']) {
            $this->warn($result['chown_hint']);
        }

        return self::FAILURE;
    }
}
