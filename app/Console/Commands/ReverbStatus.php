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
        $this->line('BROADCAST_CONNECTION: '.$broadcast);

        if ($broadcast !== 'reverb') {
            $this->warn('Set BROADCAST_CONNECTION=reverb in .env and run: php artisan config:cache');

            return Command::FAILURE;
        }

        $key = (string) config('broadcasting.connections.reverb.key', '');
        if ($key === '' || ReverbClientConfig::isPlaceholder($key)) {
            $this->warn('REVERB_APP_KEY is missing or still a placeholder.');
            $this->line('Generate keys:');
            $this->line('  REVERB_APP_KEY='.bin2hex(random_bytes(16)));
            $this->line('  REVERB_APP_SECRET='.bin2hex(random_bytes(32)));

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
            ['browser URL', sprintf('%s://%s:%d/app/{key}', $client['scheme'] === 'https' ? 'wss' : 'ws', $client['host'], $client['port'])],
            ['key', substr($client['key'], 0, 8).'…'],
        ]);

        $serverHost = (string) config('reverb.servers.reverb.host', '127.0.0.1');
        $serverPort = (int) config('reverb.servers.reverb.port', 8080);
        $this->newLine();
        $this->line("Reverb server bind: {$serverHost}:{$serverPort}");

        $listening = $this->isPortListening($serverHost, $serverPort);
        if ($listening) {
            $this->info("Port {$serverPort} is listening.");
        } else {
            $this->error("Port {$serverPort} is NOT listening — browsers will show WebSocket connection failed.");
            $this->line('Fix: sudo bash scripts/vps/consolidate-reverb.sh');
            $this->line('     supervisorctl status quizsnap-reverb');
        }

        $this->newLine();
        $this->line('nginx must proxy WebSocket path /app → http://127.0.0.1:'.$serverPort);
        $this->line('Check: sudo bash scripts/vps/check-reverb-websocket.sh');
        $this->line('Reference: nginx-production.conf (location /app { ... })');

        return $listening ? Command::SUCCESS : Command::FAILURE;
    }

    protected function isPortListening(string $host, int $port): bool
    {
        $target = $host === '0.0.0.0' ? '127.0.0.1' : $host;
        $socket = @fsockopen($target, $port, $errno, $errstr, 2);

        if ($socket === false) {
            return false;
        }

        fclose($socket);

        return true;
    }
}
