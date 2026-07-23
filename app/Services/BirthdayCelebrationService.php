<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class BirthdayCelebrationService
{
    public const DEFAULT_IMAGE_PATH = '/images/celebrations/augustine-dankwah-yeboah.webp';

    public const DEFAULT_IMAGE_MOBILE_PATH = '/images/celebrations/augustine-dankwah-yeboah-640.webp';

    /** Public homepage hero (right column) during an active celebration window. */
    public const DEFAULT_HOMEPAGE_HERO_PATH = '/images/celebrations/augustine-homepage-birthday-banner.webp';

    public const DEFAULT_HOMEPAGE_HERO_MOBILE_PATH = '/images/celebrations/augustine-homepage-birthday-banner-640.webp';

    /** @deprecated Legacy PNG path — migrated to WebP. */
    public const LEGACY_DEFAULT_IMAGE_PATH = '/images/celebrations/augustine-dankwah-yeboah.png';

    public const DEFAULT_SONG_PATH = '/audio/celebrations/happy-birthday-dashboard.mp3';

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
            Setting::KEY_BIRTHDAY_CELEBRATION_DASHBOARD_MAX_SHOWS,
            Setting::KEY_BIRTHDAY_CELEBRATION_RESET_TOKEN,
        ];

        $settings = Setting::getMany($keys, $this->defaults());

        $maxShows = (int) ($settings[Setting::KEY_BIRTHDAY_CELEBRATION_DASHBOARD_MAX_SHOWS] ?? '1');
        if ($maxShows < 0) {
            $maxShows = 0;
        }

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
            'dashboard_max_shows' => $maxShows,
            'reset_token' => trim((string) ($settings[Setting::KEY_BIRTHDAY_CELEBRATION_RESET_TOKEN] ?? '1')),
        ];
    }

    /** Bump reset token so honorees see the dashboard surprise again (new browser storage key). */
    public function resetDashboardSurprises(): int
    {
        $current = (int) Setting::getValue(Setting::KEY_BIRTHDAY_CELEBRATION_RESET_TOKEN, '1');
        $next = max(1, $current + 1);
        Setting::setValue(Setting::KEY_BIRTHDAY_CELEBRATION_RESET_TOKEN, (string) $next);

        return $next;
    }

    public function isActive(?Carbon $now = null): bool
    {
        $cfg = $this->config();
        if (! $cfg['enabled']) {
            return false;
        }

        $now = $now ?? $this->nowInAppTimezone();
        $start = $this->parseScheduleBoundary($cfg['start'], true);
        $end = $this->parseScheduleBoundary($cfg['end'], false);

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
     * Replace the student dashboard right-column banner while celebration is active.
     *
     * @return array<string, mixed>|null
     */
    public function studentDashboardBannerOverlay(): ?array
    {
        $cfg = $this->config();
        if (! $cfg['enabled'] || ! $this->isActive()) {
            return null;
        }

        $name = $cfg['honoree_name'] !== '' ? $cfg['honoree_name'] : 'Mr Yaboah Dankwah Augustine';

        return [
            'enabled' => true,
            'mode' => 'image',
            'image' => Setting::STUDENT_DASHBOARD_BIRTHDAY_BANNER_PATH,
            'images' => [Setting::STUDENT_DASHBOARD_BIRTHDAY_BANNER_PATH],
            'bundled_slug' => Setting::STUDENT_DASHBOARD_BIRTHDAY_BANNER_SLUG,
            'aspect_width' => 1024,
            'aspect_height' => 375,
            'alt' => 'Happy Birthday — '.$name.', Team Lead QuizSnap',
        ];
    }

    /**
     * Public homepage right-column hero image while celebration is active (copy stays default).
     *
     * @return array<string, string>|null
     */
    public function homepagePayload(): ?array
    {
        if (! $this->isActive()) {
            return null;
        }

        $cfg = $this->config();
        $name = $cfg['honoree_name'] !== '' ? $cfg['honoree_name'] : 'Mr. Augustine Dankwah Yeboah';
        $desktopPath = self::DEFAULT_HOMEPAGE_HERO_PATH;

        return [
            'image_url' => $this->versionedPublicAsset($desktopPath),
            'image_mobile_url' => $this->versionedPublicAsset(self::DEFAULT_HOMEPAGE_HERO_MOBILE_PATH),
            'honoree_name' => $name,
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
        $resetToken = $cfg['reset_token'] !== '' ? $cfg['reset_token'] : '1';
        $periodKey = $cfg['start'].'_'.$cfg['end'];

        return [
            'title' => $cfg['dashboard_title'] ?: 'It\'s your birthday today!',
            'headline' => $this->dashboardHeadline($user, $cfg['honoree_name']),
            'message' => $cfg['dashboard_message'] ?: $this->defaultDashboardMessage($cfg['honoree_name']),
            'image_url' => $this->versionedPublicAsset($this->normalizeImagePath($cfg['image'])),
            'play_song' => $cfg['play_song'],
            'song_url' => $this->resolveSongUrl($cfg['song_url']),
            'first_name' => $firstName,
            'max_shows' => $cfg['dashboard_max_shows'],
            'storage_key' => 'quizsnap_birthday_'.$user->id.'_'.$periodKey.'_r'.$resetToken,
        ];
    }

    public function resolveImageUrl(?string $stored): string
    {
        $stored = $this->normalizeImagePath($stored);

        if (preg_match('#^https?://#i', $stored) || str_starts_with($stored, '/')) {
            if (preg_match('#^https?://#i', $stored)) {
                return $stored;
            }

            return $this->versionedPublicAsset($stored);
        }

        if (str_starts_with($stored, 'uploads/') || str_starts_with($stored, 'celebrations/')) {
            return Storage::disk('public')->url($stored);
        }

        return $this->versionedPublicAsset('/'.ltrim($stored, '/'));
    }

    public function normalizeImagePath(?string $stored): string
    {
        $stored = trim((string) $stored);
        if ($stored === '' || $stored === self::LEGACY_DEFAULT_IMAGE_PATH) {
            return self::DEFAULT_IMAGE_PATH;
        }

        return $stored;
    }

    public function mobileCompanionPath(string $desktopPath): string
    {
        if ($desktopPath === self::DEFAULT_IMAGE_PATH) {
            return self::DEFAULT_IMAGE_MOBILE_PATH;
        }

        if ($desktopPath === self::DEFAULT_HOMEPAGE_HERO_PATH) {
            return self::DEFAULT_HOMEPAGE_HERO_MOBILE_PATH;
        }

        if (str_ends_with($desktopPath, '.webp') && ! str_contains($desktopPath, '-640.')) {
            $candidate = preg_replace('/\.webp$/', '-640.webp', $desktopPath);
            if (is_string($candidate) && is_file(public_path(ltrim($candidate, '/')))) {
                return $candidate;
            }
        }

        return $desktopPath;
    }

    public function versionedPublicAsset(string $path): string
    {
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        $relative = ltrim($path, '/');
        $ver = is_file(public_path($relative)) ? (string) filemtime(public_path($relative)) : '1';

        return asset($path).'?v='.$ver;
    }

    public function resolveSongUrl(?string $stored): ?string
    {
        $stored = trim((string) $stored);
        if ($stored === '') {
            $stored = self::DEFAULT_SONG_PATH;
        }

        if (preg_match('#^https?://#i', $stored)) {
            return $stored;
        }

        if (str_starts_with($stored, '/')) {
            $relative = ltrim($stored, '/');
            if (! is_file(public_path($relative))) {
                return null;
            }

            return asset($stored);
        }

        if (str_starts_with($stored, 'uploads/') || str_starts_with($stored, 'audio/')) {
            return Storage::disk('public')->url($stored);
        }

        $path = ltrim($stored, '/');
        if (is_file(public_path($path))) {
            return asset('/'.$path);
        }

        return null;
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
            Setting::KEY_BIRTHDAY_CELEBRATION_SONG_URL => self::DEFAULT_SONG_PATH,
            Setting::KEY_BIRTHDAY_CELEBRATION_DASHBOARD_MAX_SHOWS => '1',
            Setting::KEY_BIRTHDAY_CELEBRATION_RESET_TOKEN => '1',
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

    public function appTimezone(): string
    {
        $tz = Setting::getValue(Setting::KEY_APP_TIMEZONE, config('app.timezone', 'UTC'));

        return is_string($tz) && $tz !== '' ? $tz : (string) config('app.timezone', 'UTC');
    }

    /**
     * @return array{date: string, time: string}
     */
    public function splitScheduleFields(?string $stored): array
    {
        $stored = trim((string) $stored);
        if ($stored === '') {
            return ['date' => '', 'time' => ''];
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $stored)) {
            return ['date' => $stored, 'time' => ''];
        }

        try {
            $dt = Carbon::parse($stored, $this->appTimezone());

            return ['date' => $dt->format('Y-m-d'), 'time' => $dt->format('H:i')];
        } catch (\Throwable) {
            return ['date' => '', 'time' => ''];
        }
    }

    public function combineScheduleFields(?string $date, ?string $time, bool $isStart): ?string
    {
        $date = trim((string) $date);
        if ($date === '') {
            return null;
        }

        $time = trim((string) $time);
        if ($time === '') {
            return $date;
        }

        if (! preg_match('/^\d{1,2}:\d{2}$/', $time)) {
            return $date;
        }

        try {
            return Carbon::parse($date.' '.$time, $this->appTimezone())->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return $date;
        }
    }

    public function parseScheduleBoundary(string $value, bool $isStart): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        try {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                $dt = Carbon::parse($value, $this->appTimezone());

                return $isStart ? $dt->copy()->startOfDay() : $dt->copy()->endOfDay();
            }

            return Carbon::parse($value, $this->appTimezone());
        } catch (\Throwable) {
            return null;
        }
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
        return now()->timezone($this->appTimezone());
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
