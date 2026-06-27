<?php

namespace App\Events\Support;

use App\Models\SupportSession;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupportTyping implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public SupportSession $session,
        public string $senderType,
        public string $senderLabel,
        public bool $isTyping,
    ) {}

    /** @return array<int, \Illuminate\Broadcasting\Channel> */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('support-session.'.$this->session->uuid),
        ];
    }

    public function broadcastAs(): string
    {
        return 'SupportTyping';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'session_uuid' => $this->session->uuid,
            'sender_type' => $this->senderType,
            'sender_label' => $this->senderLabel,
            'is_typing' => $this->isTyping,
        ];
    }
}
