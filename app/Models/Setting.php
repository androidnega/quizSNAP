<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use App\Services\PageCacheService;

class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    /**
     * Get a setting value by key.
     * AI keys bypass cache so changes apply immediately after save.
     * Decrypts if key is sensitive.
     */
    public static function getValue(string $key, ?string $default = null): ?string
    {
        if (in_array($key, [self::KEY_OPENAI_API, self::KEY_GEMINI_API, self::KEY_DEEPSEEK_API], true)) {
            $value = static::where('key', $key)->value('value');
        } else {
            $cacheKey = 'setting:' . $key;
            $value = Cache::remember($cacheKey, 3600, function () use ($key) {
                $row = static::where('key', $key)->first();
                return $row?->value;
            });
        }
        if ($value === null) {
            return $default;
        }
        if (in_array($key, self::ENCRYPTED_KEYS, true)) {
            try {
                return Crypt::decryptString($value);
            } catch (DecryptException $e) {
                \Illuminate\Support\Facades\Log::warning('Setting decryption failed (wrong APP_KEY?). Re-save this key in Settings.', ['key' => $key]);
                return $default;
            }
        }
        return $value;
    }

    /**
     * Load multiple settings in one query and warm per-key cache (non-AI keys).
     *
     * @param  list<string>  $keys
     * @param  array<string, string|null>  $defaults
     * @return array<string, string|null>
     */
    public static function getMany(array $keys, array $defaults = []): array
    {
        $keys = array_values(array_unique($keys));
        if ($keys === []) {
            return [];
        }

        $aiKeys = [self::KEY_OPENAI_API, self::KEY_GEMINI_API, self::KEY_DEEPSEEK_API];
        $rows = static::whereIn('key', $keys)->pluck('value', 'key');
        $out = [];

        foreach ($keys as $key) {
            $raw = $rows[$key] ?? null;
            if ($raw === null) {
                $out[$key] = $defaults[$key] ?? null;
                continue;
            }

            if (! in_array($key, $aiKeys, true)) {
                Cache::put('setting:'.$key, $raw, 3600);
            }

            if (in_array($key, self::ENCRYPTED_KEYS, true)) {
                try {
                    $out[$key] = Crypt::decryptString($raw);
                } catch (DecryptException $e) {
                    \Illuminate\Support\Facades\Log::warning('Setting decryption failed (wrong APP_KEY?). Re-save this key in Settings.', ['key' => $key]);
                    $out[$key] = $defaults[$key] ?? null;
                }
            } else {
                $out[$key] = $raw;
            }
        }

        return $out;
    }

    /**
     * Keys loaded on the admin settings index page (single bulk read on cold cache).
     *
     * @return list<string>
     */
    public static function settingsIndexKeys(): array
    {
        return [
            self::KEY_DEEPSEEK_API,
            self::KEY_AI_QUIZ_GENERATION_ENABLED,
            self::KEY_APP_NAME,
            self::KEY_APP_TIMEZONE,
            self::KEY_FOOTER_COPYRIGHT,
            self::KEY_MAIL_MAILER,
            self::KEY_MAIL_HOST,
            self::KEY_MAIL_PORT,
            self::KEY_MAIL_USERNAME,
            self::KEY_MAIL_PASSWORD,
            self::KEY_MAIL_ENCRYPTION,
            self::KEY_MAIL_FROM_ADDRESS,
            self::KEY_MAIL_FROM_NAME,
            self::KEY_NOTIFY_RESULT_READY,
            self::KEY_NOTIFY_RESULT_EMAIL,
            self::KEY_SEND_SMS_ON_STAFF_CREATION,
            self::KEY_DISABLE_IP_DEVICE_RESTRICTIONS,
            self::KEY_OTP_ARKESEL_API_KEY,
            self::KEY_OTP_ARKESEL_SENDER_ID,
            self::KEY_STUDENT_PASSWORD_LOGIN_ENABLED,
            self::KEY_STUDENT_OTP_RETURN_LOGIN_ENABLED,
            self::KEY_STUDENT_ONBOARDING_EMAIL_OTP_ENABLED,
            self::KEY_STUDENT_EMAIL_REQUIRED,
            self::KEY_STUDENT_PASSWORD_RESET_ENABLED,
            self::KEY_STUDENT_OTP_MAX_ATTEMPTS,
            self::KEY_STUDENT_OTP_LOCKOUT_MINUTES,
            self::KEY_STUDENT_UNIVERSAL_OTP_CODES,
            self::KEY_LIVE_PROCTOR_ENABLED,
            self::KEY_VIOLATION_STORAGE_DRIVER,
            self::KEY_VIOLATION_RETENTION_DAYS_PRIMARY,
            self::KEY_VIOLATION_RETENTION_DAYS_SECONDARY,
            self::KEY_AI_QUIZ_COOLDOWN_HOURS,
            self::KEY_LANDING_HERO_IMAGE,
            self::KEY_LANDING_HERO_ENABLED,
            self::KEY_LANDING_SHOW_QUIZ_TOKEN,
            self::KEY_LOGIN_HERO_IMAGE,
            self::KEY_STUDENT_DASHBOARD_BANNER_ENABLED,
            self::KEY_STUDENT_DASHBOARD_BANNER_MODE,
            self::KEY_STUDENT_DASHBOARD_BANNER_TITLE,
            self::KEY_STUDENT_DASHBOARD_BANNER_TITLE_ACCENT,
            self::KEY_STUDENT_DASHBOARD_BANNER_SUBTITLE,
            self::KEY_STUDENT_DASHBOARD_BANNER_IMAGES,
            self::KEY_SUPABASE_URL,
            self::KEY_SUPABASE_SERVICE_KEY,
            self::KEY_SUPABASE_BUCKET,
            self::KEY_SUPABASE_SIGNED_URL_TTL,
            ...self::PROCTORING_FLAG_KEYS,
        ];
    }

    /**
     * Set a setting value by key. Encrypts if key is sensitive.
     */
    public static function setValue(string $key, ?string $value): void
    {
        $stored = $value;
        if ($value !== null && $value !== '' && in_array($key, self::ENCRYPTED_KEYS, true)) {
            $stored = Crypt::encryptString($value);
        }
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $stored]
        );
        Cache::forget('setting:' . $key);
        if (in_array($key, PageCacheService::landingSettingKeys(), true)
            || in_array($key, [
                self::KEY_STUDENT_DASHBOARD_BANNER_ENABLED,
                self::KEY_STUDENT_DASHBOARD_BANNER_MODE,
                self::KEY_STUDENT_DASHBOARD_BANNER_TITLE,
                self::KEY_STUDENT_DASHBOARD_BANNER_TITLE_ACCENT,
                self::KEY_STUDENT_DASHBOARD_BANNER_SUBTITLE,
                self::KEY_STUDENT_DASHBOARD_BANNER_IMAGES,
            ], true)) {
            app(PageCacheService::class)->bumpVersion();
        }
    }

    /**
     * Get digest recipient value (stored under hashed key, encrypted). Migrates from legacy key if present.
     */
    public static function getDigestRecipientValue(): ?string
    {
        $value = self::getValue(self::KEY_NOTIFY_DIGEST_RECIPIENT_STORAGE);
        if ($value !== null && trim($value) !== '') {
            return $value;
        }
        $legacy = self::getValue(self::KEY_NOTIFY_DIGEST_RECIPIENT);
        if ($legacy !== null && trim($legacy) !== '') {
            self::setValue(self::KEY_NOTIFY_DIGEST_RECIPIENT_STORAGE, $legacy);
            static::where('key', self::KEY_NOTIFY_DIGEST_RECIPIENT)->delete();
            Cache::forget('setting:' . self::KEY_NOTIFY_DIGEST_RECIPIENT);
            return $legacy;
        }
        return null;
    }

    /**
     * Set digest recipient value (stored under hashed key, encrypted).
     */
    public static function setDigestRecipientValue(?string $value): void
    {
        self::setValue(self::KEY_NOTIFY_DIGEST_RECIPIENT_STORAGE, $value);
    }

    /**
     * Student dashboard hero banner configuration for the overview page.
     *
     * @return array{enabled: bool, mode: string, title: string, title_accent: string, subtitle: string, image: string|null, images: array<int, string>}
     */
    public static function getStudentDashboardBannerConfig(): array
    {
        $s = self::getMany([
            self::KEY_STUDENT_DASHBOARD_BANNER_ENABLED,
            self::KEY_STUDENT_DASHBOARD_BANNER_MODE,
            self::KEY_STUDENT_DASHBOARD_BANNER_TITLE,
            self::KEY_STUDENT_DASHBOARD_BANNER_TITLE_ACCENT,
            self::KEY_STUDENT_DASHBOARD_BANNER_SUBTITLE,
            self::KEY_STUDENT_DASHBOARD_BANNER_IMAGES,
        ], [
            self::KEY_STUDENT_DASHBOARD_BANNER_ENABLED => '1',
            self::KEY_STUDENT_DASHBOARD_BANNER_MODE => 'image',
            self::KEY_STUDENT_DASHBOARD_BANNER_TITLE => 'Challenge Yourself.',
            self::KEY_STUDENT_DASHBOARD_BANNER_TITLE_ACCENT => 'Achieve More.',
            self::KEY_STUDENT_DASHBOARD_BANNER_SUBTITLE => 'Take quizzes, track progress and achieve your goals every day.',
            self::KEY_STUDENT_DASHBOARD_BANNER_IMAGES => '[]',
        ]);

        $imagesRaw = $s[self::KEY_STUDENT_DASHBOARD_BANNER_IMAGES] ?? '[]';
        $images = json_decode($imagesRaw ?: '[]', true);
        if (! is_array($images)) {
            $images = [];
        }
        $images = array_values(array_filter(array_map(static fn ($url) => is_string($url) ? trim($url) : '', $images)));
        $mode = $s[self::KEY_STUDENT_DASHBOARD_BANNER_MODE] ?? 'image';
        if (! in_array($mode, ['image', 'image_text'], true)) {
            $mode = 'image';
        }

        $defaultImage = asset('images/student-dashboard-midsem-exams-good-luck-banner.webp');

        return [
            'enabled' => ($s[self::KEY_STUDENT_DASHBOARD_BANNER_ENABLED] ?? '1') === '1',
            'mode' => $mode,
            'title' => ($s[self::KEY_STUDENT_DASHBOARD_BANNER_TITLE] ?? 'Challenge Yourself.') ?: 'Challenge Yourself.',
            'title_accent' => ($s[self::KEY_STUDENT_DASHBOARD_BANNER_TITLE_ACCENT] ?? 'Achieve More.') ?: 'Achieve More.',
            'subtitle' => ($s[self::KEY_STUDENT_DASHBOARD_BANNER_SUBTITLE] ?? 'Take quizzes, track progress and achieve your goals every day.') ?: 'Take quizzes, track progress and achieve your goals every day.',
            'image' => $images[0] ?? $defaultImage,
            'images' => $images !== [] ? $images : [$defaultImage],
        ];
    }

    public const KEY_OPENAI_API = 'openai_api_key';
    public const KEY_GEMINI_API = 'gemini_api_key';
    public const KEY_DEEPSEEK_API = 'deepseek_api_key';

    /** General */
    public const KEY_APP_NAME = 'app_name';
    public const KEY_APP_TIMEZONE = 'app_timezone';
    /** Footer copyright text shown on Settings page. Use {year} for current year. */
    public const KEY_FOOTER_COPYRIGHT = 'footer_copyright';
    /** Mobile landing hero: 1 = show on phones, 0 = hide (Super Admin). */
    public const KEY_LANDING_HERO_ENABLED = 'landing_hero_enabled';
    /** Landing page: show quiz token input (1 = show, 0 = hide). Super Admin only. Default 0 = hidden. */
    public const KEY_LANDING_SHOW_QUIZ_TOKEN = 'landing_show_quiz_token';
    /** Mobile landing hero image URL (Super Admin). Shown on phone only when enabled. URL or local upload. */
    public const KEY_LANDING_HERO_IMAGE = 'landing_hero_image';
    /** Staff login page hero image URL. Direct link or local upload. */
    public const KEY_LOGIN_HERO_IMAGE = 'login_hero_image';

    /** Student dashboard hero banner (Super Admin). */
    public const KEY_STUDENT_DASHBOARD_BANNER_ENABLED = 'student_dashboard_banner_enabled';
    /** Banner layout: image (image only) or image_text (text left, image right). */
    public const KEY_STUDENT_DASHBOARD_BANNER_MODE = 'student_dashboard_banner_mode';
    public const KEY_STUDENT_DASHBOARD_BANNER_TITLE = 'student_dashboard_banner_title';
    public const KEY_STUDENT_DASHBOARD_BANNER_TITLE_ACCENT = 'student_dashboard_banner_title_accent';
    public const KEY_STUDENT_DASHBOARD_BANNER_SUBTITLE = 'student_dashboard_banner_subtitle';
    /** JSON array; first entry is the banner image URL. */
    public const KEY_STUDENT_DASHBOARD_BANNER_IMAGES = 'student_dashboard_banner_images';
    public const KEY_INSTITUTION_NAME = 'institution_name';
    public const KEY_INSTITUTION_LOGO = 'institution_logo';

    /** Supabase Storage (student documents) */
    public const KEY_SUPABASE_URL = 'supabase_url';
    public const KEY_SUPABASE_SERVICE_KEY = 'supabase_service_key';
    public const KEY_SUPABASE_BUCKET = 'supabase_bucket';
    /** Signed URL TTL (minutes) */
    public const KEY_SUPABASE_SIGNED_URL_TTL = 'supabase_signed_url_ttl';

    /** Mail */
    public const KEY_MAIL_MAILER = 'mail_mailer';
    public const KEY_MAIL_HOST = 'mail_host';
    public const KEY_MAIL_PORT = 'mail_port';
    public const KEY_MAIL_USERNAME = 'mail_username';
    public const KEY_MAIL_PASSWORD = 'mail_password';
    public const KEY_MAIL_ENCRYPTION = 'mail_encryption';
    public const KEY_MAIL_FROM_ADDRESS = 'mail_from_address';
    public const KEY_MAIL_FROM_NAME = 'mail_from_name';

    /** Notifications: send email when a student submits a quiz (result ready). */
    public const KEY_NOTIFY_RESULT_READY = 'notify_result_ready';
    public const KEY_NOTIFY_RESULT_EMAIL = 'notify_result_email';

    /** Docu Mentor: allow coordinators to delete projects (and groups that have a project). 1 = allowed, 0 = only Super Admin can delete. */
    public const KEY_ALLOW_COORDINATOR_DELETE_PROJECT = 'allow_coordinator_delete_project';

    /** Send SMS to examiner/coordinator on account creation (username, password, login URL). 1 = enabled. Requires phone and Arkesel API key. */
    public const KEY_SEND_SMS_ON_STAFF_CREATION = 'send_sms_on_staff_creation';

    /** Admin: disable strict per-IP/per-device quiz session restrictions (1 = disabled). */
    public const KEY_DISABLE_IP_DEVICE_RESTRICTIONS = 'disable_ip_device_restrictions';

    /** Site in update/maintenance mode: only staff can log in and use the system; others see maintenance page. */
    public const KEY_UPDATE_MODE = 'update_mode';
    /** When update mode was turned on (ISO 8601 datetime). */
    public const KEY_UPDATE_STARTED_AT = 'update_started_at';
    /** Optional estimated end of maintenance (ISO 8601 datetime). */
    public const KEY_UPDATE_ESTIMATED_END = 'update_estimated_end';

    /** OTP (Arkesel): API key and optional sender ID for SMS OTP. */
    public const KEY_OTP_ARKESEL_API_KEY = 'otp_arkesel_api_key';
    public const KEY_OTP_ARKESEL_SENDER_ID = 'otp_arkesel_sender_id';

    /** Student login: allow index + password (optional SMS OTP fallback). 1 = enabled. */
    public const KEY_STUDENT_PASSWORD_LOGIN_ENABLED = 'student_password_login_enabled';
    /** When off (default), returning students sign in with password only; SMS is for first-time phone verification. */
    public const KEY_STUDENT_OTP_RETURN_LOGIN_ENABLED = 'student_otp_return_login_enabled';
    /** Email OTP fallback during first-time onboarding when SMS fails. */
    public const KEY_STUDENT_ONBOARDING_EMAIL_OTP_ENABLED = 'student_onboarding_email_otp_enabled';
    public const KEY_STUDENT_EMAIL_REQUIRED = 'student_email_required';
    public const KEY_STUDENT_PASSWORD_RESET_ENABLED = 'student_password_reset_enabled';
    public const KEY_STUDENT_OTP_MAX_ATTEMPTS = 'student_otp_max_attempts';
    public const KEY_STUDENT_OTP_LOCKOUT_MINUTES = 'student_otp_lockout_minutes';

    /** Comma-separated 6-digit codes accepted as student OTP for any index (never expire). Empty = use .env only. */
    public const KEY_STUDENT_UNIVERSAL_OTP_CODES = 'student_universal_otp_codes';

    /** Super Admin: live examiner view (watch students taking quiz). 1 = on, 0 = off. When off, Live proctor tab and route are unavailable. */
    public const KEY_LIVE_PROCTOR_ENABLED = 'live_proctor_enabled';

    /** Violation image storage: server only — images under storage/app/public/violations. */
    public const KEY_VIOLATION_STORAGE_DRIVER = 'violation_storage_driver';
    /** Days to keep violation images before auto-deletion (primary). Default 21. */
    public const KEY_VIOLATION_RETENTION_DAYS_PRIMARY = 'violation_retention_days_primary';
    /** Days to keep violation images before auto-deletion (secondary). Default 21. */
    public const KEY_VIOLATION_RETENTION_DAYS_SECONDARY = 'violation_retention_days_secondary';

    /** AI quiz generation: hours an examiner must wait after exhausting tokens before refill. Default 24. */
    public const KEY_AI_QUIZ_COOLDOWN_HOURS = 'ai_quiz_cooldown_hours';
    /** Master toggle: allow examiners to generate questions with DeepSeek. */
    public const KEY_AI_QUIZ_GENERATION_ENABLED = 'ai_quiz_generation_enabled';

    /** Digest recipient (primary super admin only). Stored encrypted. Public name for form/validation only. */
    public const KEY_NOTIFY_DIGEST_RECIPIENT = 'notify_digest_recipient';

    /** Storage key for digest recipient (hashed so key column in DB does not reveal purpose). Value encrypted. */
    public const KEY_NOTIFY_DIGEST_RECIPIENT_STORAGE = 'a7f3e9c1b5d8f2a4c6e0b8d2f4a6c8e0b2d4f6a8c0e2b4d6f8a0c2e4b6d8f0a2';

    /** Quiz proctoring (Super Admin): enable/disable features. 1 = enabled, 0 = disabled. */
    public const KEY_PROCTORING_CAMERA_REQUIRED = 'proctoring_camera_required';
    public const KEY_PROCTORING_FACE_MONITOR = 'proctoring_face_monitor';
    public const KEY_PROCTORING_TAB_SWITCH = 'proctoring_tab_switch';
    public const KEY_PROCTORING_OBJECT_DETECT = 'proctoring_object_detect';
    public const KEY_PROCTORING_BLOCK_RIGHT_CLICK = 'proctoring_block_right_click';
    public const KEY_PROCTORING_BLOCK_COPY_PASTE = 'proctoring_block_copy_paste';

    /** Proctoring flag keys loaded together on quiz hot paths. */
    private const PROCTORING_FLAG_KEYS = [
        self::KEY_PROCTORING_CAMERA_REQUIRED,
        self::KEY_PROCTORING_FACE_MONITOR,
        self::KEY_PROCTORING_TAB_SWITCH,
        self::KEY_PROCTORING_OBJECT_DETECT,
        self::KEY_PROCTORING_BLOCK_RIGHT_CLICK,
        self::KEY_PROCTORING_BLOCK_COPY_PASTE,
    ];

    /**
     * Load all quiz proctoring toggles in one pass (cached per key).
     *
     * @return array<string, bool>
     */
    public static function getProctoringFlags(): array
    {
        $values = self::getMany(self::PROCTORING_FLAG_KEYS);
        $flags = [];
        foreach (self::PROCTORING_FLAG_KEYS as $key) {
            $flags[$key] = ($values[$key] ?? '1') === '1';
        }

        return $flags;
    }

    /** Keys whose values are stored encrypted (API keys, secrets, mail password). */
    private const ENCRYPTED_KEYS = [
        self::KEY_GEMINI_API,
        self::KEY_DEEPSEEK_API,
        self::KEY_OPENAI_API,
        self::KEY_MAIL_PASSWORD,
        self::KEY_OTP_ARKESEL_API_KEY,
        self::KEY_NOTIFY_DIGEST_RECIPIENT,
        self::KEY_NOTIFY_DIGEST_RECIPIENT_STORAGE,
        self::KEY_SUPABASE_SERVICE_KEY,
    ];

}
