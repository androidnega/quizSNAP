<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class BirthdayCelebrationService
{
    public const DEFAULT_IMAGE_PATH = '/images/celebrations/augustine-dankwah-yeboah.png';

    public const DEFAULT_HONOREE_USER_IDS = '3,4';

    /**
     * @return array<string, mixed>
     */
    public function config(): array
    {
        $keys = [
            Setting::KEY_BIRTHDAY_CELEBRATION_ENABLED,
            Setting::KEY_BIRTHDAY_CELEBRATION_START,
            Setting::KEY_BIRTHDAY_CELEBRATION_END,
            Setting::KEY_BIRTHDAY_CELEBRATION_USER_IDS,
            Setting::KEY_BIRTHDAY_CELEBRATION_HONOREE_NAME,
            Setting::KEY_BIRTHDAY_CELEBRATION_HOMEPAGE_BADGE,
            Setting::KEY_BIRTHDAY_CELEBRATION_HOMEPAGE_TITLE,
            Setting::KEY_BIRTHDAY_CELEBRATION_HOMEPAGE_MESSAGE,
            Setting::KEY_BIRTHDAY_CELEBRATION_DASHBOARD_TITLE,
            Setting::KEY_BIRTHDAY_CELEBRATION_DASHBOARD_MESSAGE,
            Setting::KEY_BIRTHDAY_CELEBRATION_IMAGE,
            Setting::KEY_BIRTHDAY_CELEBRATION_PLAY_SONG,
            Setting::KEY_BIRTHDAY_CELEBRATION_SONG_URL,
        ];

        $settings = Setting::getMany($keys, $this->defaults());

        return [
            'enabled' => ($settings[Setting::KEY_BIRTHDAY_CELEBRATION_ENABLED] ?? '0') === '1',
            'start' => trim((string) ($settings[Setting::KEY_BIRTHDAY_CELEBRATION_START] ?? '')),
            'end' => trim((string) ($settings[Setting::KEY_BIRTHDAY_CELEBRATION_END] ?? '')),
            'user_ids' => $this->parseUserIds($settings[Setting::KEY_BIRTHDAY_CELEBRATION_USER_IDS] ?? self::DEFAULT_HONOREE_USER_IDS),
            'honoree_name' => trim((string) ($settings[Setting::KEY_BIRTHDAY_CELEBRATION_HONOREE_NAME] ?? '')),
            'homepage_badge' => trim((string) ($settings[Setting::KEY_BIRTHDAY_CELEBRATION_HOMEPAGE_BADGE] ?? '')),
            'homepage_title' => trim((string) ($settings[Setting::KEY_BIRTHDAY_CELEBRATION_HOMEPAGE_TITLE] ?? '')),
            'homepage_message' => trim((string) ($settings[Setting::KEY_BIRTHDAY_CELEBRATION_HOMEPAGE_MESSAGE] ?? '')),
            'dashboard_title' => trim((string) ($settings[Setting::KEY_BIRTHDAY_CELEBRATION_DASHBOARD_TITLE] ?? '')),
            'dashboard_message' => trim((string) ($settings[Setting::KEY_BIRTHDAY_CELEBRATION_DASHBOARD_MESSAGE] ?? '')),
            'image' => trim((string) ($settings[Setting::KEY_BIRTHDAY_CELEBRATION_IMAGE] ?? '')),
            'play_song' => ($settings[Setting::KEY_BIRTHDAY_CELEBRATION_PLAY_SONG] ?? '1') === '1',
            'song_url' => trim((string) ($settings[Setting::KEY_BIRTHDAY_CELEBRATION_SONG_URL] ?? '')),
        ];
    }

    public function isActive(?Carbon $now = null): bool
    {
        $cfg = $this->config();
        if (! $cfg['enabled']) {
            return false;
        }

        $now = $now ?? $this->nowInAppTimezone();
        $start = $this->parseDateBoundary($cfg['start'], true);
        $end = $this->parseDateBoundary($cfg['end'], false);

        if (! $start || ! $end) {
            return false;
        }

        return $now->betweenIncluded($start, $end);
    }

    public function isHonoreeUser(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return in_array((int) $user->id, $this->config()['user_ids'], true);
    }

    /**
     * Public homepage hero swap while celebration is active.
     *
     * @return array<string, string>|null
     */
    public function homepagePayload(): ?array
    {
        if (! $this->isActive()) {
            return null;
        }

        $cfg = $this->config();

        return [
            'badge' => $cfg['homepage_badge'] ?: 'Celebrating today',
            'title' => $cfg['homepage_title'] ?: 'Happy Birthday, '.$cfg['honoree_name'],
            'message' => $cfg['homepage_message'] ?: $this->defaultHomepageMessage($cfg['honoree_name']),
            'image_url' => $this->resolveImageUrl($cfg['image']),
            'honoree_name' => $cfg['honoree_name'],
        ];
    }

    /**
     * Surprise modal payload for honoree staff on dashboard home.
     *
     * @return array<string, mixed>|null
     */
    public function dashboardSurpriseFor(?User $user): ?array
    {
        if (! $this->isActive() || ! $this->isHonoreeUser($user)) {
            return null;
        }

        $cfg = $this->config();
        $firstName = $this->honoreeFirstName($user, $cfg['honoree_name']);

        return [
            'title' => $cfg['dashboard_title'] ?: 'It\'s your birthday today!',
            'headline' => $this->dashboardHeadline($user, $cfg['honoree_name']),
            'message' => $cfg['dashboard_message'] ?: $this->defaultDashboardMessage($cfg['honoree_name']),
            'image_url' => $this->resolveImageUrl($cfg['image']),
            'play_song' => $cfg['play_song'],
            'song_url' => $cfg['song_url'] !== '' ? $cfg['song_url'] : null,
            'first_name' => $firstName,
            'storage_key' => 'quizsnap_birthday_surprise_'.$user->id.'_'.$this->nowInAppTimezone()->format('Y-m-d'),
        ];
    }

    public function resolveImageUrl(?string $stored): string
    {
        $stored = trim((string) $stored);
        if ($stored === '') {
            return asset(self::DEFAULT_IMAGE_PATH);
        }

        if (preg_match('#^https?://#i', $stored) || str_starts_with($stored, '/')) {
            return $stored;
        }

        if (str_starts_with($stored, 'uploads/') || str_starts_with($stored, 'celebrations/')) {
            return Storage::disk('public')->url($stored);
        }

        return asset($stored);
    }

    /**
     * @return array<string, string>
     */
    public function defaults(): array
    {
        $name = 'Mr. Augustine Dankwah Yeboah';
        $today = now()->format('Y-m-d');

        return [
            Setting::KEY_BIRTHDAY_CELEBRATION_ENABLED => '0',
            Setting::KEY_BIRTHDAY_CELEBRATION_START => $today,
            Setting::KEY_BIRTHDAY_CELEBRATION_END => $today,
            Setting::KEY_BIRTHDAY_CELEBRATION_USER_IDS => self::DEFAULT_HONOREE_USER_IDS,
            Setting::KEY_BIRTHDAY_CELEBRATION_HONOREE_NAME => $name,
            Setting::KEY_BIRTHDAY_CELEBRATION_HOMEPAGE_BADGE => 'Celebrating today',
            Setting::KEY_BIRTHDAY_CELEBRATION_HOMEPAGE_TITLE => 'Happy Birthday, '.$name,
            Setting::KEY_BIRTHDAY_CELEBRATION_HOMEPAGE_MESSAGE => $this->defaultHomepageMessage($name),
            Setting::KEY_BIRTHDAY_CELEBRATION_DASHBOARD_TITLE => 'Surprise — it\'s your birthday!',
            Setting::KEY_BIRTHDAY_CELEBRATION_DASHBOARD_MESSAGE => $this->defaultDashboardMessage($name),
            Setting::KEY_BIRTHDAY_CELEBRATION_IMAGE => self::DEFAULT_IMAGE_PATH,
            Setting::KEY_BIRTHDAY_CELEBRATION_PLAY_SONG => '1',
            Setting::KEY_BIRTHDAY_CELEBRATION_SONG_URL => '',
        ];
    }

    public function defaultHomepageMessage(string $name): string
    {
        return 'Today we celebrate '.$name.' — lead developer, mentor, and a driving force behind QuizSnap. Thank you for building tools that help education stay honest, secure, and accessible online.';
    }

    public function defaultDashboardMessage(string $name): string
    {
        return 'Happy Birthday, '.$name.'! Your work as a creator and developer is seen and appreciated. You help make the world a better place through education, integrity, and innovation online. Keep being a proud builder — the whole team celebrates you today.';
    }

    /**
     * @return list<int>
     */
    private function parseUserIds(string $raw): array
    {
        $parts = preg_split('/[\s,]+/', $raw) ?: [];
        $ids = [];
        foreach ($parts as $part) {
            $id = (int) trim($part);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    private function nowInAppTimezone(): Carbon
    {
        $tz = Setting::getValue(Setting::KEY_APP_TIMEZONE, config('app.timezone', 'UTC'));

        return now()->timezone(is_string($tz) && $tz !== '' ? $tz : config('app.timezone', 'UTC'));
    }

    private function parseDateBoundary(string $value, bool $startOfDay): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        try {
            $dt = Carbon::parse($value, $this->nowInAppTimezone()->timezone);
        } catch (\Throwable) {
            return null;
        }

        return $startOfDay ? $dt->copy()->startOfDay() : $dt->copy()->endOfDay();
    }

    private function honoreeFirstName(?User $user, string $configuredName): string
    {
        if ($user && is_string($user->name) && trim($user->name) !== '') {
            $parts = preg_split('/\s+/', trim($user->name)) ?: [];

            return $parts[0] ?? 'Friend';
        }

        $parts = preg_split('/\s+/', trim(preg_replace('/^Mr\.?\s+/i', '', $configuredName))) ?: [];

        return $parts[0] ?? 'Friend';
    }

    private function dashboardHeadline(?User $user, string $configuredName): string
    {
        if ($user && is_string($user->name) && trim($user->name) !== '') {
            return 'It\'s your birthday today, '.trim($user->name).'!';
        }

        return 'It\'s your birthday today, Yeboah Dankwah Augustine!';
    }
}
