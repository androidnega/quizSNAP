<?php

namespace App\Services\Monitoring;

use App\Services\ReverbClientConfig;

class WebSocketMonitoringService
{
    public function status(): array
    {
        $config = ReverbClientConfig::clientConfig();
        $enabled = ! empty($config['key']);

        return [
            'enabled' => $enabled,
            'host' => $config['host'] ?? null,
            'port' => $config['port'] ?? null,
            'scheme' => $config['scheme'] ?? null,
            'connected_users' => null,
            'connected_channels' => $enabled ? ['quizsnap', 'quizsnap-monitoring'] : [],
            'messages_per_minute' => null,
            'broadcast_failures' => 0,
            'connection_failures' => 0,
        ];
    }
}
