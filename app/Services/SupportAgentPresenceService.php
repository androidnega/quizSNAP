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

    /** @return list<array{id: int, name: string, username: string}> */
    public function onlineRespondersExcluding(?int $excludeUserId = null): array
    {
        return User::query()
            ->get()
            ->filter(function (User $user) use ($excludeUserId) {
                if (! LiveSupportAccess::canRespond($user) || ! $this->isOnline($user)) {
                    return false;
                }
                if ($excludeUserId !== null && (int) $user->id === $excludeUserId) {
                    return false;
                }

                return true;
            })
            ->map(fn (User $user) => [
                'id' => (int) $user->id,
                'name' => (string) ($user->name ?: $user->username),
                'username' => (string) $user->username,
            ])
            ->values()
            ->all();
    }

    private function cacheKey(int $userId): string
    {
        return 'support:agent:presence:'.$userId;
    }
}
