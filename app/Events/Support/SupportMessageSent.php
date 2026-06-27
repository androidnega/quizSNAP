<?php

namespace App\Events\Support;

use App\Models\SupportMessage;
use App\Models\SupportSession;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupportMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public SupportSession $session,
        public SupportMessage $message,
    ) {}

    /** @return array<int, \Illuminate\Broadcasting\Channel> */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('support-session.'.$this->session->uuid),
            new PrivateChannel('support-inbox'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'SupportMessageSent';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'session_uuid' => $this->session->uuid,
            'message' => $this->message->toPayload(),
        ];
    }
}
