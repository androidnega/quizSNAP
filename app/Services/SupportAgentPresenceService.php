<?php

namespace App\Services;

use App\Models\User;
use App\Support\LiveSupportAccess;
use Illuminate\Support\Facades\Cache;

class SupportAgentPresenceService
{
    private const TTL_SECONDS = 90;

    public function ping(User $user): void
    {
        if (! LiveSupportAccess::canRespond($user)) {
            return;
        }

        Cache::put($this->cacheKey($user->id), true, now()->addSeconds(self::TTL_SECONDS));
    }

    public function isOnline(User $user): bool
    {
        return Cache::has($this->cacheKey($user->id));
    }

    public function anyAgentOnline(): bool
    {
        return User::query()
            ->get()
            ->contains(fn (User $user) => LiveSupportAccess::canRespond($user) && $this->isOnline($user));
    }

    /** @return list<int> */
    public function onlineResponderIds(): array
    {
        return User::query()
            ->get()
            ->filter(fn (User $user) => LiveSupportAccess::canRespond($user) && $this->isOnline($user))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    private function cacheKey(int $userId): string
    {
        return 'support:agent:presence:'.$userId;
    }
}
