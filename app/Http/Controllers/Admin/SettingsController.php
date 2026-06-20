<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassGroup;
use App\Models\Setting;
use App\Models\User;
use App\Services\MailConfigService;
use App\Services\StudentOnboardingEmailOtpService;
use App\Models\Student;
use App\Services\ArkeselService;
use App\Services\LocalUploadService;
use App\Services\StudentUniversalOtp;
use App\Services\SupabaseStorageService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * Show settings page (general, email, AI).
     */
    public function index(): View
    {
        $settings = Setting::getMany(Setting::settingsIndexKeys(), [
            Setting::KEY_AI_QUIZ_GENERATION_ENABLED => '1',
            Setting::KEY_APP_NAME => config('app.name'),
            Setting::KEY_APP_TIMEZONE => config('app.timezone', 'UTC'),
            Setting::KEY_FOOTER_COPYRIGHT => '© {year} '.config('app.name', 'QuizSnap').'. All rights reserved.',
            Setting::KEY_MAIL_MAILER => 'smtp',
            Setting::KEY_MAIL_HOST => 'mail.quizsnap.online',
            Setting::KEY_MAIL_PORT => '465',
            Setting::KEY_MAIL_USERNAME => 'deveopers@quizsnap.online',
            Setting::KEY_MAIL_ENCRYPTION => 'ssl',
            Setting::KEY_MAIL_FROM_ADDRESS => 'deveopers@quizsnap.online',
            Setting::KEY_MAIL_FROM_NAME => 'QuizSnap',
            Setting::KEY_NOTIFY_RESULT_READY => '0',
            Setting::KEY_SEND_SMS_ON_STAFF_CREATION => '0',
            Setting::KEY_DISABLE_IP_DEVICE_RESTRICTIONS => '0',
            Setting::KEY_OTP_ARKESEL_SENDER_ID => 'QuizSnap',
            Setting::KEY_STUDENT_PASSWORD_LOGIN_ENABLED => '1',
            Setting::KEY_STUDENT_OTP_RETURN_LOGIN_ENABLED => '0',
            Setting::KEY_STUDENT_ONBOARDING_EMAIL_OTP_ENABLED => '1',
            Setting::KEY_STUDENT_EMAIL_REQUIRED => '1',
            Setting::KEY_STUDENT_PASSWORD_RESET_ENABLED => '1',
            Setting::KEY_STUDENT_OTP_MAX_ATTEMPTS => '5',
            Setting::KEY_STUDENT_OTP_LOCKOUT_MINUTES => '15',
            Setting::KEY_LIVE_PROCTOR_ENABLED => '1',
            Setting::KEY_VIOLATION_STORAGE_DRIVER => 'server',
            Setting::KEY_VIOLATION_RETENTION_DAYS_PRIMARY => '21',
            Setting::KEY_VIOLATION_RETENTION_DAYS_SECONDARY => '21',
            Setting::KEY_AI_QUIZ_COOLDOWN_HOURS => '24',
            Setting::KEY_LANDING_HERO_ENABLED => '1',
            Setting::KEY_LANDING_SHOW_QUIZ_TOKEN => '0',
            Setting::KEY_STUDENT_DASHBOARD_BANNER_ENABLED => '1',
            Setting::KEY_STUDENT_DASHBOARD_BANNER_MODE => 'image',
            Setting::KEY_STUDENT_DASHBOARD_BANNER_TITLE => 'Challenge Yourself.',
            Setting::KEY_STUDENT_DASHBOARD_BANNER_TITLE_ACCENT => 'Achieve More.',
            Setting::KEY_STUDENT_DASHBOARD_BANNER_SUBTITLE => 'Take quizzes, track progress and achieve your goals every day.',
            Setting::KEY_STUDENT_DASHBOARD_BANNER_IMAGES => '[]',
            Setting::KEY_STUDENT_DASHBOARD_MOBILE_LAYOUT => 'classic',
            Setting::KEY_SUPABASE_SIGNED_URL_TTL => '60',
            Setting::KEY_PROCTORING_CAMERA_REQUIRED => '1',
            Setting::KEY_PROCTORING_FACE_MONITOR => '1',
            Setting::KEY_PROCTORING_TAB_SWITCH => '1',
            Setting::KEY_PROCTORING_OBJECT_DETECT => '1',
            Setting::KEY_PROCTORING_BLOCK_RIGHT_CLICK => '1',
            Setting::KEY_PROCTORING_BLOCK_COPY_PASTE => '1',
        ]);

        $deepseekKey = $settings[Setting::KEY_DEEPSEEK_API] ?? null;
        $deepseekKeyMasked = $deepseekKey ? substr($deepseekKey, 0, 8).'…'.substr($deepseekKey, -4) : null;
        $otpKey = $settings[Setting::KEY_OTP_ARKESEL_API_KEY] ?? null;
        $supabaseKey = $settings[Setting::KEY_SUPABASE_SERVICE_KEY] ?? null;
        $bannerImagesRaw = $settings[Setting::KEY_STUDENT_DASHBOARD_BANNER_IMAGES] ?? '[]';
        $bannerImages = json_decode($bannerImagesRaw ?: '[]', true) ?: [];

        $currentUser = auth()->user() ?? User::find(session('admin_user_id'));
        $primarySuperAdminId = User::where('role', User::ROLE_SUPER_ADMIN)->min('id');
        $canManageProctoring = $currentUser && $currentUser->isSuperAdmin();
        $isSuperAdmin = ($currentUser && $currentUser->isSuperAdmin()) || session('admin_role') === User::ROLE_SUPER_ADMIN;
        $isPrimarySuperAdmin = $primarySuperAdminId !== null && (
            ($currentUser && (int) $currentUser->id === (int) $primarySuperAdminId)
            || ((int) session('admin_user_id') === (int) $primarySuperAdminId)
        );
        $canManageBackup = $isSuperAdmin && $isPrimarySuperAdmin;
        $digestRecipient = $canManageBackup ? Setting::getDigestRecipientValue() : null;
        $backupEmailConfigured = $digestRecipient !== null && trim($digestRecipient) !== '';

        return view('admin.settings.index', [
            'deepseek_key_set' => (bool) $deepseekKey,
            'deepseek_key_masked' => $deepseekKeyMasked,
            'ai_quiz_generation_enabled' => ($settings[Setting::KEY_AI_QUIZ_GENERATION_ENABLED] ?? '1') === '1',
            'app_name' => $settings[Setting::KEY_APP_NAME] ?? config('app.name'),
            'app_timezone' => $settings[Setting::KEY_APP_TIMEZONE] ?? config('app.timezone', 'UTC'),
            'footer_copyright' => $settings[Setting::KEY_FOOTER_COPYRIGHT] ?? '© {year} '.config('app.name', 'QuizSnap').'. All rights reserved.',
            'mail_mailer' => $settings[Setting::KEY_MAIL_MAILER] ?? 'smtp',
            'mail_host' => $settings[Setting::KEY_MAIL_HOST] ?? 'mail.quizsnap.online',
            'mail_port' => $settings[Setting::KEY_MAIL_PORT] ?? '465',
            'mail_username' => $settings[Setting::KEY_MAIL_USERNAME] ?? 'deveopers@quizsnap.online',
            'mail_encryption' => $settings[Setting::KEY_MAIL_ENCRYPTION] ?? 'ssl',
            'mail_from_address' => $settings[Setting::KEY_MAIL_FROM_ADDRESS] ?? 'deveopers@quizsnap.online',
            'mail_from_name' => $settings[Setting::KEY_MAIL_FROM_NAME] ?? 'QuizSnap',
            'notify_result_ready' => ($settings[Setting::KEY_NOTIFY_RESULT_READY] ?? '0') === '1',
            'notify_result_email' => $settings[Setting::KEY_NOTIFY_RESULT_EMAIL] ?? '',
            'send_sms_on_staff_creation' => ($settings[Setting::KEY_SEND_SMS_ON_STAFF_CREATION] ?? '0') === '1',
            'disable_ip_device_restrictions' => ($settings[Setting::KEY_DISABLE_IP_DEVICE_RESTRICTIONS] ?? '0') === '1',
            'otp_arkesel_key_set' => (bool) $otpKey,
            'otp_arkesel_key_masked' => $otpKey ? (strlen($otpKey) > 8 ? substr($otpKey, 0, 4).'…'.substr($otpKey, -4) : '••••') : null,
            'otp_arkesel_sender_id' => $settings[Setting::KEY_OTP_ARKESEL_SENDER_ID] ?? 'QuizSnap',
            'student_password_login_enabled' => ($settings[Setting::KEY_STUDENT_PASSWORD_LOGIN_ENABLED] ?? '1') === '1',
            'student_otp_return_login_enabled' => ($settings[Setting::KEY_STUDENT_OTP_RETURN_LOGIN_ENABLED] ?? '0') === '1',
            'student_onboarding_email_otp_enabled' => ($settings[Setting::KEY_STUDENT_ONBOARDING_EMAIL_OTP_ENABLED] ?? '1') === '1',
            'student_email_required' => ($settings[Setting::KEY_STUDENT_EMAIL_REQUIRED] ?? '1') === '1',
            'student_password_reset_enabled' => ($settings[Setting::KEY_STUDENT_PASSWORD_RESET_ENABLED] ?? '1') === '1',
            'mail_configured' => MailConfigService::isConfigured($settings),
            'student_onboarding_email_otp_active' => StudentOnboardingEmailOtpService::isEnabled($settings),
            'student_password_reset_active' => ($settings[Setting::KEY_STUDENT_PASSWORD_RESET_ENABLED] ?? '1') === '1',
            'student_otp_max_attempts' => $settings[Setting::KEY_STUDENT_OTP_MAX_ATTEMPTS] ?? '5',
            'student_otp_lockout_minutes' => $settings[Setting::KEY_STUDENT_OTP_LOCKOUT_MINUTES] ?? '15',
            'student_universal_otp_codes' => $settings[Setting::KEY_STUDENT_UNIVERSAL_OTP_CODES] ?? '',
            'proctoring_camera_required' => ($settings[Setting::KEY_PROCTORING_CAMERA_REQUIRED] ?? '1') === '1',
            'proctoring_face_monitor' => ($settings[Setting::KEY_PROCTORING_FACE_MONITOR] ?? '1') === '1',
            'proctoring_tab_switch' => ($settings[Setting::KEY_PROCTORING_TAB_SWITCH] ?? '1') === '1',
            'proctoring_object_detect' => ($settings[Setting::KEY_PROCTORING_OBJECT_DETECT] ?? '1') === '1',
            'proctoring_block_right_click' => ($settings[Setting::KEY_PROCTORING_BLOCK_RIGHT_CLICK] ?? '1') === '1',
            'proctoring_block_copy_paste' => ($settings[Setting::KEY_PROCTORING_BLOCK_COPY_PASTE] ?? '1') === '1',
            'live_proctor_enabled' => ($settings[Setting::KEY_LIVE_PROCTOR_ENABLED] ?? '1') === '1',
            'violation_storage_driver' => $settings[Setting::KEY_VIOLATION_STORAGE_DRIVER] ?? 'server',
            'violation_retention_days_primary' => $settings[Setting::KEY_VIOLATION_RETENTION_DAYS_PRIMARY] ?? '21',
            'violation_retention_days_secondary' => $settings[Setting::KEY_VIOLATION_RETENTION_DAYS_SECONDARY] ?? '21',
            'ai_quiz_cooldown_hours' => $settings[Setting::KEY_AI_QUIZ_COOLDOWN_HOURS] ?? '24',
            'landing_hero_image' => $settings[Setting::KEY_LANDING_HERO_IMAGE] ?? null,
            'landing_hero_enabled' => ($settings[Setting::KEY_LANDING_HERO_ENABLED] ?? '1') === '1',
            'landing_show_quiz_token' => ($settings[Setting::KEY_LANDING_SHOW_QUIZ_TOKEN] ?? '0') === '1',
            'login_hero_image' => $settings[Setting::KEY_LOGIN_HERO_IMAGE] ?? null,
            'theme_preset' => app(\App\Services\ThemeService::class)->activePresetId(),
            'theme_presets' => app(\App\Services\ThemeService::class)->allPresets(),
            'student_dashboard_banner_enabled' => ($settings[Setting::KEY_STUDENT_DASHBOARD_BANNER_ENABLED] ?? '1') === '1',
            'student_dashboard_banner_mode' => $settings[Setting::KEY_STUDENT_DASHBOARD_BANNER_MODE] ?? 'image',
            'student_dashboard_banner_title' => $settings[Setting::KEY_STUDENT_DASHBOARD_BANNER_TITLE] ?? 'Challenge Yourself.',
            'student_dashboard_banner_title_accent' => $settings[Setting::KEY_STUDENT_DASHBOARD_BANNER_TITLE_ACCENT] ?? 'Achieve More.',
            'student_dashboard_banner_subtitle' => $settings[Setting::KEY_STUDENT_DASHBOARD_BANNER_SUBTITLE] ?? 'Take quizzes, track progress and achieve your goals every day.',
            'student_dashboard_banner_image' => is_array($bannerImages) ? ($bannerImages[0] ?? '') : '',
            'student_dashboard_banner_images' => is_array($bannerImages) ? $bannerImages : [],
            'student_dashboard_mobile_layout' => Setting::getStudentDashboardMobileLayout(),
            'supabase_url' => $settings[Setting::KEY_SUPABASE_URL] ?? '',
            'supabase_bucket' => $settings[Setting::KEY_SUPABASE_BUCKET] ?? '',
            'supabase_ttl' => $settings[Setting::KEY_SUPABASE_SIGNED_URL_TTL] ?? '60',
            'supabase_service_key_set' => (bool) $supabaseKey,
            'supabase_service_key_masked' => $supabaseKey
                ? (strlen($supabaseKey) > 8 ? substr($supabaseKey, 0, 4).'…'.substr($supabaseKey, -4) : '••••')
                : null,
            'can_manage_proctoring' => $canManageProctoring,
            'can_manage_backup' => $canManageBackup,
            'show_backup_tab' => $canManageBackup,
            'backup_email_configured' => $backupEmailConfigured,
            'study_guide_unlocked' => session('study_guide_unlocked', false),
            'class_groups_for_study_guide' => ($canManageBackup && session('study_guide_unlocked', false))
                ? ClassGroup::orderBy('name')->get(['id', 'name'])
                : collect(),
        ]);
    }

    /**
     * Validate study guide password and unlock access (Settings → Digest). Primary super admin only.
     */
    public function studyGuideUnlock(Request $request): RedirectResponse
    {
        $currentUser = auth()->user() ?? User::find(session('admin_user_id'));
        $primarySuperAdminId = User::where('role', User::ROLE_SUPER_ADMIN)->min('id');
        $isPrimary = $primarySuperAdminId !== null && (
            ($currentUser && (int) $currentUser->id === (int) $primarySuperAdminId)
            || ((int) session('admin_user_id') === (int) $primarySuperAdminId)
        );

        if (! $isPrimary) {
            return redirect()->route('dashboard.settings.index')->with('error', 'Access denied.')->withFragment('backup');
        }

        $request->validate(['study_guide_password' => 'required|string']);

        $expected = trim((string) (config('study-guide.unlock_password') ?? env('STUDY_GUIDE_UNLOCK_PASSWORD', 'Atomic2@2020^') ?? ''));
        $given = trim((string) $request->input('study_guide_password', ''));
        if ($expected === '' || ! hash_equals($expected, $given)) {
            return redirect()->route('dashboard.settings.index')->with('error', 'Invalid password.')->withFragment('backup');
        }

        session(['study_guide_unlocked' => true]);
        session()->forget('error');

        return redirect()->route('dashboard.settings.index')->with('success', 'Unlocked.')->withFragment('backup');
    }

    /**
     * Update settings for the active tab only (avoids resetting checkboxes on other tabs).
     */
    public function update(Request $request): RedirectResponse
    {
        $validTabs = ['general', 'email', 'ai', 'supabase', 'otp', 'proctoring', 'backup', 'student-dashboard'];
        $tab = $request->input('settings_tab', 'general');
        if (! in_array($tab, $validTabs, true)) {
            $tab = 'general';
        }

        $currentUser = auth()->user() ?? User::find(session('admin_user_id'));
        $primarySuperAdminId = User::where('role', User::ROLE_SUPER_ADMIN)->min('id');
        $canManageProctoring = $currentUser && $currentUser->isSuperAdmin();
        $isPrimarySuperAdmin = $primarySuperAdminId !== null && (
            ($currentUser && (int) $currentUser->id === (int) $primarySuperAdminId)
            || ((int) session('admin_user_id') === (int) $primarySuperAdminId)
        );
        $canManageBackup = $isPrimarySuperAdmin;
        $isSuperAdmin = session('admin_role') === User::ROLE_SUPER_ADMIN;

        $request->validate($this->validationRulesForTab($tab, $canManageProctoring, $canManageBackup, $isSuperAdmin));

        match ($tab) {
            'general' => $this->saveGeneralTabSettings($request, $isSuperAdmin),
            'email' => $this->saveEmailTabSettings($request),
            'ai' => $this->saveAiTabSettings($request),
            'supabase' => $this->saveSupabaseTabSettings($request),
            'otp' => $this->saveOtpTabSettings($request),
            'proctoring' => $canManageProctoring ? $this->saveProctoringTabSettings($request) : null,
            'student-dashboard' => $isSuperAdmin ? $this->saveStudentDashboardTabSettings($request) : null,
            'backup' => $canManageBackup ? $this->saveBackupTabSettings($request) : null,
            default => null,
        };

        return redirect()->route('dashboard.settings.index')->with('success', 'Settings saved.')->withFragment($tab);
    }

    /**
     * @return array<string, mixed>
     */
    private function validationRulesForTab(string $tab, bool $canManageProctoring, bool $canManageBackup, bool $isSuperAdmin): array
    {
        $rules = ['settings_tab' => 'nullable|string|max:50'];

        return match ($tab) {
            'general' => array_merge($rules, [
                'app_name' => 'nullable|string|max:255',
                'app_timezone' => 'nullable|string|max:100',
                'footer_copyright' => 'nullable|string|max:512',
                'disable_ip_device_restrictions' => 'nullable|boolean',
                'send_sms_on_staff_creation' => 'nullable|boolean',
                'landing_hero_enabled' => 'nullable|boolean',
                'landing_show_quiz_token' => 'nullable|boolean',
                'landing_hero_image_url' => 'nullable|string|max:2048',
                'landing_hero_image_file' => 'nullable|image|max:5120',
                'login_hero_image_file' => 'nullable|image|max:5120',
                'login_hero_image_url' => 'nullable|string|max:2048',
                'theme_preset' => 'nullable|string|max:64',
            ]),
            'email' => array_merge($rules, [
                'mail_mailer' => 'nullable|string|max:50',
                'mail_host' => 'nullable|string|max:255',
                'mail_port' => 'nullable|string|max:10',
                'mail_username' => 'nullable|string|max:255',
                'mail_password' => 'nullable|string|max:512',
                'mail_encryption' => 'nullable|string|max:20',
                'mail_from_address' => 'nullable|email|max:255',
                'mail_from_name' => 'nullable|string|max:255',
                'notify_result_ready' => 'nullable|boolean',
                'notify_result_email' => 'nullable|email|max:255',
            ]),
            'ai' => array_merge($rules, [
                'ai_quiz_generation_enabled' => 'nullable|boolean',
                'deepseek_api_key' => 'nullable|string|max:512',
                'clear_deepseek_key' => 'nullable|boolean',
                'ai_quiz_cooldown_hours' => 'nullable|integer|min:1|max:168',
            ]),
            'supabase' => array_merge($rules, [
                'supabase_url' => 'nullable|url|max:255',
                'supabase_service_key' => 'nullable|string|max:1024',
                'clear_supabase_service_key' => 'nullable|boolean',
                'supabase_bucket' => 'nullable|string|max:255',
                'supabase_signed_url_ttl' => 'nullable|integer|min:1|max:1440',
            ]),
            'otp' => array_merge($rules, [
                'otp_arkesel_api_key' => 'nullable|string|max:512',
                'clear_otp_arkesel_key' => 'nullable|boolean',
                'otp_arkesel_sender_id' => 'nullable|string|max:11',
                'student_password_login_enabled' => 'nullable|boolean',
                'student_otp_return_login_enabled' => 'nullable|boolean',
                'student_onboarding_email_otp_enabled' => 'nullable|boolean',
                'student_email_required' => 'nullable|boolean',
                'student_password_reset_enabled' => 'nullable|boolean',
                'student_otp_max_attempts' => 'nullable|integer|min:3|max:20',
                'student_otp_lockout_minutes' => 'nullable|integer|min:5|max:120',
                'student_universal_otp_codes' => 'nullable|string|max:500',
            ]),
            'proctoring' => $canManageProctoring ? array_merge($rules, [
                'live_proctor_enabled' => 'nullable|in:0,1',
                'proctoring_camera_required' => 'nullable|in:0,1',
                'proctoring_face_monitor' => 'nullable|in:0,1',
                'proctoring_tab_switch' => 'nullable|in:0,1',
                'proctoring_object_detect' => 'nullable|in:0,1',
                'proctoring_block_right_click' => 'nullable|in:0,1',
                'proctoring_block_copy_paste' => 'nullable|in:0,1',
                'violation_storage_driver' => 'nullable|string|in:server',
                'violation_retention_days_primary' => 'nullable|integer|min:1|max:365',
                'violation_retention_days_secondary' => 'nullable|integer|min:1|max:365',
            ]) : $rules,
            'student-dashboard' => $isSuperAdmin ? array_merge($rules, [
                'student_dashboard_banner_enabled' => 'nullable|boolean',
                'student_dashboard_banner_mode' => 'nullable|string|in:image,image_text',
                'student_dashboard_banner_title' => 'nullable|string|max:120',
                'student_dashboard_banner_title_accent' => 'nullable|string|max:120',
                'student_dashboard_banner_subtitle' => 'nullable|string|max:500',
                'student_dashboard_banner_image_url' => 'nullable|string|max:2048',
                'student_dashboard_banner_image_file' => 'nullable|image|max:5120',
                'student_dashboard_mobile_layout' => 'nullable|string|in:classic,modern',
            ]) : $rules,
            'backup' => $canManageBackup ? array_merge($rules, [
                'notify_digest_recipient' => 'nullable|email|max:255',
            ]) : $rules,
            default => $rules,
        };
    }

    private function saveGeneralTabSettings(Request $request, bool $isSuperAdmin): void
    {
        Setting::setValue(Setting::KEY_APP_NAME, $request->filled('app_name') ? trim($request->app_name) : null);
        Setting::setValue(Setting::KEY_APP_TIMEZONE, $request->filled('app_timezone') ? trim($request->app_timezone) : null);
        Setting::setValue(Setting::KEY_FOOTER_COPYRIGHT, $request->filled('footer_copyright') ? trim($request->footer_copyright) : null);
        Setting::setValue(Setting::KEY_DISABLE_IP_DEVICE_RESTRICTIONS, $request->boolean('disable_ip_device_restrictions') ? '1' : '0');

        if ($isSuperAdmin && $request->filled('theme_preset')) {
            $preset = trim((string) $request->theme_preset);
            if (app(\App\Services\ThemeService::class)->isValidPreset($preset)) {
                Setting::setValue(Setting::KEY_THEME_PRESET, $preset);
            }
        }

        if ($isSuperAdmin) {
            Setting::setValue(Setting::KEY_SEND_SMS_ON_STAFF_CREATION, $request->boolean('send_sms_on_staff_creation') ? '1' : '0');
            Cache::forget('setting:' . Setting::KEY_SEND_SMS_ON_STAFF_CREATION);
            Setting::setValue(Setting::KEY_LANDING_HERO_ENABLED, $request->boolean('landing_hero_enabled') ? '1' : '0');
            Cache::forget('setting:' . Setting::KEY_LANDING_HERO_ENABLED);
            Setting::setValue(Setting::KEY_LANDING_SHOW_QUIZ_TOKEN, $request->boolean('landing_show_quiz_token') ? '1' : '0');
            Cache::forget('setting:' . Setting::KEY_LANDING_SHOW_QUIZ_TOKEN);

            if ($request->hasFile('landing_hero_image_file')) {
                $url = LocalUploadService::storePublicImage($request->file('landing_hero_image_file'), 'uploads/hero');
                if ($url) {
                    Setting::setValue(Setting::KEY_LANDING_HERO_IMAGE, $url);
                    Cache::forget('setting:' . Setting::KEY_LANDING_HERO_IMAGE);
                }
            } elseif ($request->filled('landing_hero_image_url')) {
                $url = trim(preg_replace('/[\r\n]+/', '', $request->landing_hero_image_url));
                if ($url !== '' && (preg_match('#^https?://#i', $url) || filter_var($url, FILTER_VALIDATE_URL))) {
                    Setting::setValue(Setting::KEY_LANDING_HERO_IMAGE, $url);
                    Cache::forget('setting:' . Setting::KEY_LANDING_HERO_IMAGE);
                }
            }

            if ($request->hasFile('login_hero_image_file')) {
                $url = LocalUploadService::storePublicImage($request->file('login_hero_image_file'), 'uploads/hero');
                if ($url) {
                    Setting::setValue(Setting::KEY_LOGIN_HERO_IMAGE, $url);
                    Cache::forget('setting:' . Setting::KEY_LOGIN_HERO_IMAGE);
                }
            } elseif ($request->filled('login_hero_image_url')) {
                $url = trim(preg_replace('/[\r\n]+/', '', $request->login_hero_image_url));
                if ($url !== '' && (preg_match('#^https?://#i', $url) || filter_var($url, FILTER_VALIDATE_URL))) {
                    Setting::setValue(Setting::KEY_LOGIN_HERO_IMAGE, $url);
                    Cache::forget('setting:' . Setting::KEY_LOGIN_HERO_IMAGE);
                }
            }
        }
    }

    private function saveEmailTabSettings(Request $request): void
    {
        Setting::setValue(Setting::KEY_MAIL_MAILER, $request->filled('mail_mailer') ? trim($request->mail_mailer) : null);
        Setting::setValue(Setting::KEY_MAIL_HOST, $request->filled('mail_host') ? trim($request->mail_host) : null);
        Setting::setValue(Setting::KEY_MAIL_PORT, $request->filled('mail_port') ? trim($request->mail_port) : null);
        Setting::setValue(Setting::KEY_MAIL_USERNAME, $request->filled('mail_username') ? trim($request->mail_username) : null);
        if ($request->filled('mail_password')) {
            Setting::setValue(Setting::KEY_MAIL_PASSWORD, trim($request->mail_password));
        }
        Setting::setValue(Setting::KEY_MAIL_ENCRYPTION, $request->filled('mail_encryption') ? trim($request->mail_encryption) : null);
        Setting::setValue(Setting::KEY_MAIL_FROM_ADDRESS, $request->filled('mail_from_address') ? trim($request->mail_from_address) : null);
        Setting::setValue(Setting::KEY_MAIL_FROM_NAME, $request->filled('mail_from_name') ? trim($request->mail_from_name) : null);
        Setting::setValue(Setting::KEY_NOTIFY_RESULT_READY, $request->boolean('notify_result_ready') ? '1' : '0');
        Setting::setValue(Setting::KEY_NOTIFY_RESULT_EMAIL, $request->filled('notify_result_email') ? trim($request->notify_result_email) : null);
    }

    private function saveAiTabSettings(Request $request): void
    {
        Setting::setValue(Setting::KEY_AI_QUIZ_GENERATION_ENABLED, $request->boolean('ai_quiz_generation_enabled') ? '1' : '0');

        if ($request->boolean('clear_deepseek_key')) {
            Setting::setValue(Setting::KEY_DEEPSEEK_API, null);
        } elseif ($request->filled('deepseek_api_key')) {
            Setting::setValue(Setting::KEY_DEEPSEEK_API, trim($request->deepseek_api_key));
        }

        if ($request->has('ai_quiz_cooldown_hours')) {
            $hours = max(1, min(168, (int) $request->ai_quiz_cooldown_hours));
            Setting::setValue(Setting::KEY_AI_QUIZ_COOLDOWN_HOURS, (string) $hours);
            Cache::forget('setting:' . Setting::KEY_AI_QUIZ_COOLDOWN_HOURS);
        }

        Cache::forget('setting:' . Setting::KEY_AI_QUIZ_GENERATION_ENABLED);
        Cache::forget('setting:' . Setting::KEY_DEEPSEEK_API);
    }

    private function saveSupabaseTabSettings(Request $request): void
    {
        if ($request->filled('supabase_url')) {
            Setting::setValue(Setting::KEY_SUPABASE_URL, trim($request->supabase_url));
        }
        if ($request->boolean('clear_supabase_service_key')) {
            Setting::setValue(Setting::KEY_SUPABASE_SERVICE_KEY, null);
        } elseif ($request->filled('supabase_service_key')) {
            Setting::setValue(Setting::KEY_SUPABASE_SERVICE_KEY, trim($request->supabase_service_key));
        }
        if ($request->filled('supabase_bucket')) {
            Setting::setValue(Setting::KEY_SUPABASE_BUCKET, trim($request->supabase_bucket));
        }
        if ($request->has('supabase_signed_url_ttl')) {
            $ttl = max(1, min(1440, (int) $request->supabase_signed_url_ttl));
            Setting::setValue(Setting::KEY_SUPABASE_SIGNED_URL_TTL, (string) $ttl);
        }

        Cache::forget('setting:' . Setting::KEY_SUPABASE_URL);
        Cache::forget('setting:' . Setting::KEY_SUPABASE_SERVICE_KEY);
        Cache::forget('setting:' . Setting::KEY_SUPABASE_BUCKET);
        Cache::forget('setting:' . Setting::KEY_SUPABASE_SIGNED_URL_TTL);
    }

    private function saveOtpTabSettings(Request $request): void
    {
        if ($request->boolean('clear_otp_arkesel_key')) {
            Setting::setValue(Setting::KEY_OTP_ARKESEL_API_KEY, null);
        } elseif ($request->filled('otp_arkesel_api_key')) {
            Setting::setValue(Setting::KEY_OTP_ARKESEL_API_KEY, trim($request->otp_arkesel_api_key));
        }
        Setting::setValue(Setting::KEY_OTP_ARKESEL_SENDER_ID, $request->filled('otp_arkesel_sender_id') ? substr(trim($request->otp_arkesel_sender_id), 0, 11) : 'QuizSnap');

        Setting::setValue(Setting::KEY_STUDENT_PASSWORD_LOGIN_ENABLED, $request->boolean('student_password_login_enabled') ? '1' : '0');
        Setting::setValue(
            Setting::KEY_STUDENT_OTP_RETURN_LOGIN_ENABLED,
            $request->boolean('student_password_login_enabled') && $request->boolean('student_otp_return_login_enabled') ? '1' : '0'
        );
        Setting::setValue(Setting::KEY_STUDENT_ONBOARDING_EMAIL_OTP_ENABLED, $request->boolean('student_onboarding_email_otp_enabled') ? '1' : '0');
        Setting::setValue(Setting::KEY_STUDENT_EMAIL_REQUIRED, $request->boolean('student_email_required') ? '1' : '0');
        Setting::setValue(Setting::KEY_STUDENT_PASSWORD_RESET_ENABLED, $request->boolean('student_password_reset_enabled') ? '1' : '0');
        if ($request->filled('student_otp_max_attempts')) {
            Setting::setValue(Setting::KEY_STUDENT_OTP_MAX_ATTEMPTS, (string) (int) $request->student_otp_max_attempts);
        }
        if ($request->filled('student_otp_lockout_minutes')) {
            Setting::setValue(Setting::KEY_STUDENT_OTP_LOCKOUT_MINUTES, (string) (int) $request->student_otp_lockout_minutes);
        }

        $uniRaw = (string) $request->input('student_universal_otp_codes', '');
        $uniNorm = implode(',', StudentUniversalOtp::parseRawToSixDigitCodes($uniRaw));
        if ($uniNorm === '') {
            Setting::where('key', Setting::KEY_STUDENT_UNIVERSAL_OTP_CODES)->delete();
        } else {
            Setting::setValue(Setting::KEY_STUDENT_UNIVERSAL_OTP_CODES, $uniNorm);
        }

        foreach ([
            Setting::KEY_OTP_ARKESEL_API_KEY,
            Setting::KEY_OTP_ARKESEL_SENDER_ID,
            Setting::KEY_STUDENT_PASSWORD_LOGIN_ENABLED,
            Setting::KEY_STUDENT_OTP_RETURN_LOGIN_ENABLED,
            Setting::KEY_STUDENT_ONBOARDING_EMAIL_OTP_ENABLED,
            Setting::KEY_STUDENT_EMAIL_REQUIRED,
            Setting::KEY_STUDENT_PASSWORD_RESET_ENABLED,
            Setting::KEY_STUDENT_OTP_MAX_ATTEMPTS,
            Setting::KEY_STUDENT_OTP_LOCKOUT_MINUTES,
            Setting::KEY_STUDENT_UNIVERSAL_OTP_CODES,
        ] as $key) {
            Cache::forget('setting:' . $key);
        }
    }

    private function saveProctoringTabSettings(Request $request): void
    {
        Setting::setValue(Setting::KEY_LIVE_PROCTOR_ENABLED, $request->input('live_proctor_enabled', '1') === '1' ? '1' : '0');
        Setting::setValue(Setting::KEY_PROCTORING_CAMERA_REQUIRED, $request->input('proctoring_camera_required', '1') === '1' ? '1' : '0');
        Setting::setValue(Setting::KEY_PROCTORING_FACE_MONITOR, $request->input('proctoring_face_monitor', '1') === '1' ? '1' : '0');
        Setting::setValue(Setting::KEY_PROCTORING_TAB_SWITCH, $request->input('proctoring_tab_switch', '1') === '1' ? '1' : '0');
        Setting::setValue(Setting::KEY_PROCTORING_OBJECT_DETECT, $request->input('proctoring_object_detect', '1') === '1' ? '1' : '0');
        Setting::setValue(Setting::KEY_PROCTORING_BLOCK_RIGHT_CLICK, $request->input('proctoring_block_right_click', '1') === '1' ? '1' : '0');
        Setting::setValue(Setting::KEY_PROCTORING_BLOCK_COPY_PASTE, $request->input('proctoring_block_copy_paste', '1') === '1' ? '1' : '0');

        Setting::setValue(Setting::KEY_VIOLATION_STORAGE_DRIVER, 'server');
        if ($request->filled('violation_retention_days_primary')) {
            Setting::setValue(Setting::KEY_VIOLATION_RETENTION_DAYS_PRIMARY, (string) max(1, min(365, (int) $request->violation_retention_days_primary)));
        }
        if ($request->filled('violation_retention_days_secondary')) {
            Setting::setValue(Setting::KEY_VIOLATION_RETENTION_DAYS_SECONDARY, (string) max(1, min(365, (int) $request->violation_retention_days_secondary)));
        }

        foreach ([
            Setting::KEY_LIVE_PROCTOR_ENABLED,
            Setting::KEY_PROCTORING_CAMERA_REQUIRED,
            Setting::KEY_PROCTORING_FACE_MONITOR,
            Setting::KEY_PROCTORING_TAB_SWITCH,
            Setting::KEY_PROCTORING_OBJECT_DETECT,
            Setting::KEY_PROCTORING_BLOCK_RIGHT_CLICK,
            Setting::KEY_PROCTORING_BLOCK_COPY_PASTE,
            Setting::KEY_VIOLATION_STORAGE_DRIVER,
            Setting::KEY_VIOLATION_RETENTION_DAYS_PRIMARY,
            Setting::KEY_VIOLATION_RETENTION_DAYS_SECONDARY,
        ] as $key) {
            Cache::forget('setting:' . $key);
        }
    }

    private function saveStudentDashboardTabSettings(Request $request): void
    {
        Setting::setValue(Setting::KEY_STUDENT_DASHBOARD_BANNER_ENABLED, $request->boolean('student_dashboard_banner_enabled') ? '1' : '0');

        $mode = $request->input('student_dashboard_banner_mode', 'image');
        Setting::setValue(Setting::KEY_STUDENT_DASHBOARD_BANNER_MODE, in_array($mode, ['image', 'image_text'], true) ? $mode : 'image');

        Setting::setValue(Setting::KEY_STUDENT_DASHBOARD_BANNER_TITLE, $request->filled('student_dashboard_banner_title') ? trim($request->student_dashboard_banner_title) : 'Challenge Yourself.');
        Setting::setValue(Setting::KEY_STUDENT_DASHBOARD_BANNER_TITLE_ACCENT, $request->filled('student_dashboard_banner_title_accent') ? trim($request->student_dashboard_banner_title_accent) : 'Achieve More.');
        Setting::setValue(Setting::KEY_STUDENT_DASHBOARD_BANNER_SUBTITLE, $request->filled('student_dashboard_banner_subtitle') ? trim($request->student_dashboard_banner_subtitle) : '');

        $existingImages = json_decode(Setting::getValue(Setting::KEY_STUDENT_DASHBOARD_BANNER_IMAGES, '[]') ?: '[]', true);
        $imageUrl = is_array($existingImages) ? ($existingImages[0] ?? '') : '';
        if ($request->hasFile('student_dashboard_banner_image_file')) {
            $uploaded = LocalUploadService::storePublicImage($request->file('student_dashboard_banner_image_file'), 'uploads/banners');
            if ($uploaded) {
                $imageUrl = $uploaded;
            }
        } elseif ($request->filled('student_dashboard_banner_image_url')) {
            $url = trim(preg_replace('/[\r\n]+/', '', $request->student_dashboard_banner_image_url));
            if ($url !== '' && (preg_match('#^https?://#i', $url) || filter_var($url, FILTER_VALIDATE_URL))) {
                $imageUrl = $url;
            }
        }
        Setting::setValue(Setting::KEY_STUDENT_DASHBOARD_BANNER_IMAGES, json_encode($imageUrl !== '' ? [$imageUrl] : []));

        $mobileLayout = $request->input('student_dashboard_mobile_layout', 'classic');
        Setting::setValue(
            Setting::KEY_STUDENT_DASHBOARD_MOBILE_LAYOUT,
            in_array($mobileLayout, ['classic', 'modern'], true) ? $mobileLayout : 'classic'
        );

        foreach ([
            Setting::KEY_STUDENT_DASHBOARD_BANNER_ENABLED,
            Setting::KEY_STUDENT_DASHBOARD_BANNER_MODE,
            Setting::KEY_STUDENT_DASHBOARD_BANNER_TITLE,
            Setting::KEY_STUDENT_DASHBOARD_BANNER_TITLE_ACCENT,
            Setting::KEY_STUDENT_DASHBOARD_BANNER_SUBTITLE,
            Setting::KEY_STUDENT_DASHBOARD_BANNER_IMAGES,
            Setting::KEY_STUDENT_DASHBOARD_MOBILE_LAYOUT,
        ] as $key) {
            Cache::forget('setting:' . $key);
        }
    }

    private function saveBackupTabSettings(Request $request): void
    {
        if ($request->filled('notify_digest_recipient')) {
            Setting::setDigestRecipientValue(trim($request->notify_digest_recipient));
            Cache::forget('setting:' . Setting::KEY_NOTIFY_DIGEST_RECIPIENT_STORAGE);
        }
    }

    /**
     * Test Supabase Storage connection. Returns JSON for Settings page.
     */
    public function supabaseTest(): JsonResponse
    {
        $currentUser = auth()->user() ?? User::find(session('admin_user_id'));
        $primarySuperAdminId = User::where('role', User::ROLE_SUPER_ADMIN)->min('id');
        $isPrimarySuperAdmin = $primarySuperAdminId !== null && (
            ($currentUser && (int) $currentUser->id === (int) $primarySuperAdminId)
            || ((int) session('admin_user_id') === (int) $primarySuperAdminId)
        );

        if (! $isPrimarySuperAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Only the primary super admin can test Supabase from settings.',
            ], 403);
        }

        $result = SupabaseStorageService::testConnection();
        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Send a test email using current mail settings.
     * Restricted to the primary super admin.
     */
    public function emailTest(Request $request): JsonResponse
    {
        $request->validate(['to' => 'required|email|max:255']);

        $currentUser = auth()->user() ?? User::find(session('admin_user_id'));
        $primarySuperAdminId = User::where('role', User::ROLE_SUPER_ADMIN)->min('id');
        $isPrimarySuperAdmin = $primarySuperAdminId !== null && (
            ($currentUser && (int) $currentUser->id === (int) $primarySuperAdminId)
            || ((int) session('admin_user_id') === (int) $primarySuperAdminId)
        );

        if (! $isPrimarySuperAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Only the primary super admin can send test email from settings.',
            ], 403);
        }

        try {
            $this->applyMailConfigFromSettings();

            $to = trim((string) $request->input('to'));
            $mailer = (string) Setting::getValue(Setting::KEY_MAIL_MAILER, (string) config('mail.default'));
            $host = (string) Setting::getValue(Setting::KEY_MAIL_HOST, (string) config('mail.mailers.smtp.host'));
            $port = (string) Setting::getValue(Setting::KEY_MAIL_PORT, (string) (config('mail.mailers.smtp.port') ?? ''));
            $encryption = (string) Setting::getValue(Setting::KEY_MAIL_ENCRYPTION, (string) (config('mail.mailers.smtp.encryption') ?? ''));
            $fromAddress = (string) Setting::getValue(Setting::KEY_MAIL_FROM_ADDRESS, (string) config('mail.from.address'));
            $fromName = (string) Setting::getValue(Setting::KEY_MAIL_FROM_NAME, (string) config('mail.from.name'));

            $body = "QuizSnap test email\n\n"
                . 'Time: ' . now()->toDateTimeString() . "\n"
                . 'Mailer: ' . ($mailer ?: '—') . "\n"
                . 'Host: ' . ($host ?: '—') . "\n"
                . 'Port: ' . ($port ?: '—') . "\n"
                . 'Encryption: ' . ($encryption !== '' ? $encryption : 'none') . "\n"
                . 'From: ' . ($fromName ?: 'QuizSnap') . ' <' . ($fromAddress ?: 'noreply@quizsnap.local') . ">\n\n"
                . "If you received this, your mail settings are working.";

            Mail::raw($body, function ($message) use ($to) {
                $message->to($to)->subject('QuizSnap mail test');
            });

            return response()->json([
                'success' => true,
                'message' => 'Test email sent to ' . $to . '.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test email.',
                'detail' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Test OTP delivery (Arkesel). Sends a test SMS with a 6-digit code to the given phone number.
     */
    public function otpTest(Request $request): JsonResponse
    {
        $request->validate(['phone' => 'required|string|max:20']);
        $phone = \App\Models\Student::normalizePhoneForStorage($request->input('phone'));
        if ($phone === null || strlen($phone) < 10) {
            return response()->json(['success' => false, 'message' => 'Enter a valid phone number (e.g. 233544919953 or 0544919953).'], 422);
        }
        $result = ArkeselService::sendTestOtp($phone);
        $status = $result['success'] ? 200 : (! empty($result['connection_error']) ? 503 : 422);

        return response()->json($result, $status);
    }

    /**
     * Check Arkesel SMS/main balance. Helps debug "not receiving" (e.g. zero balance).
     */
    public function otpBalance(): JsonResponse
    {
        $result = ArkeselService::checkBalance(true);
        $status = $result['success'] ? 200 : (! empty($result['connection_error']) ? 503 : 422);

        return response()->json($result, $status);
    }

    private function applyMailConfigFromSettings(): void
    {
        $mailer = Setting::getValue(Setting::KEY_MAIL_MAILER, config('mail.default'));
        $host = Setting::getValue(Setting::KEY_MAIL_HOST, config('mail.mailers.smtp.host'));
        $port = (int) Setting::getValue(Setting::KEY_MAIL_PORT, (string) (config('mail.mailers.smtp.port') ?? 587));
        $username = Setting::getValue(Setting::KEY_MAIL_USERNAME);
        $password = Setting::getValue(Setting::KEY_MAIL_PASSWORD);
        $encryption = Setting::getValue(Setting::KEY_MAIL_ENCRYPTION, (string) (config('mail.mailers.smtp.encryption') ?? 'tls'));
        $fromAddress = Setting::getValue(Setting::KEY_MAIL_FROM_ADDRESS, config('mail.from.address'));
        $fromName = Setting::getValue(Setting::KEY_MAIL_FROM_NAME, config('mail.from.name'));

        Config::set('mail.default', $mailer);
        Config::set('mail.from.address', $fromAddress ?: 'noreply@quizsnap.local');
        Config::set('mail.from.name', $fromName ?: 'QuizSnap');
        Config::set('mail.mailers.smtp.host', $host);
        Config::set('mail.mailers.smtp.port', $port);
        Config::set('mail.mailers.smtp.username', $username);
        Config::set('mail.mailers.smtp.password', $password);
        Config::set('mail.mailers.smtp.encryption', $encryption ?: null);
    }

    /**
     * Toggle update/maintenance mode. When on, only staff can log in; others see maintenance page.
     */
    public function toggleUpdateMode(Request $request): RedirectResponse
    {
        $current = Setting::getValue(Setting::KEY_UPDATE_MODE, '0') === '1';
        Setting::setValue(Setting::KEY_UPDATE_MODE, $current ? '0' : '1');
        if ($current) {
            Setting::setValue(Setting::KEY_UPDATE_STARTED_AT, null);
            Setting::setValue(Setting::KEY_UPDATE_ESTIMATED_END, null);
        } else {
            Setting::setValue(Setting::KEY_UPDATE_STARTED_AT, now()->toIso8601String());
        }
        Cache::forget('setting:' . Setting::KEY_UPDATE_MODE);
        Cache::forget('setting:' . Setting::KEY_UPDATE_STARTED_AT);
        Cache::forget('setting:' . Setting::KEY_UPDATE_ESTIMATED_END);
        return redirect()->route('dashboard')->with('success', $current ? 'Update mode turned off. Site is live.' : 'Update mode is on. Only staff can sign in.');
    }

    /**
     * Set optional estimated end time for maintenance (shown on maintenance page).
     */
    public function setUpdateEstimatedEnd(Request $request): RedirectResponse
    {
        $request->validate(['estimated_end' => ['nullable', 'date']]);
        $value = $request->input('estimated_end') ? \Carbon\Carbon::parse($request->input('estimated_end'))->toIso8601String() : null;
        Setting::setValue(Setting::KEY_UPDATE_ESTIMATED_END, $value);
        Cache::forget('setting:' . Setting::KEY_UPDATE_ESTIMATED_END);
        return redirect()->route('dashboard')->with('success', $value ? 'Estimated end time saved.' : 'Estimated end time cleared.');
    }
}
