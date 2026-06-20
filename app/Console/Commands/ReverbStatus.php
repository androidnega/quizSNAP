<?php

namespace App\Console\Commands;

use App\Services\ReverbClientConfig;
use Illuminate\Console\Command;

class ReverbStatus extends Command
{
    protected $signature = 'quizsnap:reverb-status';

    protected $description = 'Show whether live WebSocket (Reverb) is configured and reachable for browsers';

    public function handle(): int
    {
        $broadcast = (string) config('broadcasting.default');
        $this->line('BROADCAST_CONNECTION: ' . $broadcast);

        if ($broadcast !== 'reverb') {
            $this->warn('Set BROADCAST_CONNECTION=reverb in .env and run: php artisan config:clear');

            return Command::FAILURE;
        }

        $key = (string) config('broadcasting.connections.reverb.key', '');
        if ($key === '' || ReverbClientConfig::isPlaceholder($key)) {
            $this->warn('REVERB_APP_KEY is missing or still a placeholder.');
            $this->line('Generate keys:');
            $this->line('  REVERB_APP_KEY=' . bin2hex(random_bytes(16)));
            $this->line('  REVERB_APP_SECRET=' . bin2hex(random_bytes(32)));

            return Command::FAILURE;
        }

        $client = ReverbClientConfig::clientConfig();
        if ($client === null) {
            $this->warn('Browser WebSocket config is incomplete. Check REVERB_HOST and APP_URL.');

            return Command::FAILURE;
        }

        $this->info('Browser WebSocket config OK:');
        $this->table(['Setting', 'Value'], [
            ['host', $client['host']],
            ['port', (string) $client['port']],
            ['scheme', $client['scheme']],
            ['key', substr($client['key'], 0, 8) . '…'],
        ]);

        $serverPort = (int) config('reverb.servers.reverb.port', 8080);
        $this->line('');
        $this->line('Reverb server should be running: php artisan reverb:start --port=' . $serverPort);
        $this->line('Production: proxy /app to 127.0.0.1:' . $serverPort . ' (see nginx-production.conf)');

        return Command::SUCCESS;
    }
}
