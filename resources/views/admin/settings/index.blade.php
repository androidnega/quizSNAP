@extends('layouts.dashboard')

@section('title', 'Settings')
@section('dashboard_heading', 'Settings')

@section('dashboard_content')
<div class="w-full space-y-6">
        <div class="mb-6">
            <div class="flex items-center gap-2 text-sm text-gray-600 mb-4">
                <a href="{{ route('dashboard') }}" class="hover:text-primary-600">Dashboard</a>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-gray-900 font-medium">Settings</span>
            </div>
            <h1 class="text-3xl font-bold text-gray-900">Admin Settings</h1>
            <p class="text-gray-600 mt-1">System configuration: general, email, AI, and storage</p>
            <a href="{{ route('dashboard.student-levels.index') }}" class="mt-2 inline-flex items-center gap-1 text-sm text-primary-600 hover:text-primary-800">Student Levels →</a>
        </div>

        <form action="{{ route('dashboard.settings.update') }}" method="post" class="space-y-8" id="settings-form" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="settings_tab" id="settings_tab" value="general">

            <!-- Tabs Navigation -->
            <div class="card overflow-hidden">
                <div class="border-b border-gray-200 overflow-x-auto overflow-y-hidden">
                    <nav class="flex -mb-px flex-nowrap min-w-0 w-max sm:w-full sm:flex-wrap" aria-label="Settings tabs">
                        <button type="button" class="settings-tab-btn whitespace-nowrap px-4 py-3 sm:px-6 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium text-sm touch-manipulation min-h-[44px]" data-tab="general" id="tab-btn-general">
                            <svg class="w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            General
                        </button>
                        @if(session('admin_role') === 'super_admin')
                        <button type="button" class="settings-tab-btn whitespace-nowrap px-4 py-3 sm:px-6 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium text-sm touch-manipulation min-h-[44px]" data-tab="student-dashboard" id="tab-btn-student-dashboard">
                            <svg class="w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                            Student dashboard
                        </button>
                        @endif
                        <button type="button" class="settings-tab-btn whitespace-nowrap px-4 py-3 sm:px-6 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium text-sm touch-manipulation min-h-[44px]" data-tab="email" id="tab-btn-email">
                            <svg class="w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            Email
                        </button>
                        <button type="button" class="settings-tab-btn whitespace-nowrap px-4 py-3 sm:px-6 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium text-sm touch-manipulation min-h-[44px]" data-tab="ai" id="tab-btn-ai">
                            <svg class="w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                            AI
                        </button>
                        <button type="button" class="settings-tab-btn whitespace-nowrap px-4 py-3 sm:px-6 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium text-sm touch-manipulation min-h-[44px]" data-tab="supabase" id="tab-btn-supabase">
                            <svg class="w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h16v12H4zM4 16l4 4h8l4-4"/>
                            </svg>
                            Supabase
                        </button>
                        <button type="button" class="settings-tab-btn whitespace-nowrap px-4 py-3 sm:px-6 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium text-sm touch-manipulation min-h-[44px]" data-tab="otp" id="tab-btn-otp">
                            <svg class="w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            OTP (SMS)
                        </button>
                        @if($can_manage_proctoring ?? false)
                        <button type="button" class="settings-tab-btn whitespace-nowrap px-4 py-3 sm:px-6 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium text-sm touch-manipulation min-h-[44px]" data-tab="proctoring" id="tab-btn-proctoring">
                            <svg class="w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            Proctoring
                        </button>
                        @endif
                        @if($show_backup_tab ?? false)
                        <button type="button" class="settings-tab-btn whitespace-nowrap px-4 py-3 sm:px-6 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium text-sm touch-manipulation min-h-[44px]" data-tab="backup" id="tab-btn-backup">
                            <svg class="w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            Digest
                        </button>
                        @endif
                    </nav>
                </div>

                <!-- Tab: General -->
                <div class="settings-tab-content p-6" data-tab-content="general" id="tab-content-general">
                    <div class="space-y-6">
                        <div class="rounded-lg border border-gray-200 bg-gray-50/50 p-5 space-y-4">
                            <h3 class="text-sm font-semibold text-gray-800">App & branding</h3>
                            <div>
                                <label for="app_name" class="block text-sm font-medium text-gray-700 mb-1.5">Application name</label>
                                <input type="text" name="app_name" id="app_name" value="{{ old('app_name', $app_name ?? '') }}" placeholder="QuizSnap" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
                                <p class="text-xs text-gray-500 mt-1">Used in page titles and emails. Leave blank to use default.</p>
                            </div>
                            <div>
                                <label for="app_timezone" class="block text-sm font-medium text-gray-700 mb-1.5">Timezone</label>
                                <input type="text" name="app_timezone" id="app_timezone" value="{{ old('app_timezone', $app_timezone ?? 'UTC') }}" placeholder="e.g. UTC, Africa/Nairobi, America/New_York" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
                                <p class="text-xs text-gray-500 mt-1">e.g. UTC, Africa/Nairobi, America/New_York</p>
                            </div>
                            <div>
                                <label for="footer_copyright" class="block text-sm font-medium text-gray-700 mb-1.5">Copyright / footer text</label>
                                <input type="text" name="footer_copyright" id="footer_copyright" value="{{ old('footer_copyright', $footer_copyright ?? '') }}" placeholder="© {year} QuizSnap. All rights reserved." maxlength="512" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
                                <p class="text-xs text-gray-500 mt-1">Shown at the bottom of Settings. Use <code class="px-1 py-0.5 bg-gray-100 rounded text-gray-700">{year}</code> for the current year (updates automatically).</p>
                            </div>
                        </div>

                        @if(session('admin_role') === 'super_admin')
                        <div class="rounded-lg border border-gray-200 bg-gray-50/50 p-5 space-y-4">
                            <h3 class="text-sm font-semibold text-gray-800">System theme</h3>
                            <p class="text-xs text-gray-500">Choose a color theme for the landing page, student dashboard, and staff areas. Changes apply site-wide after save.</p>
                            <input type="hidden" name="theme_preset" id="theme_preset" value="{{ old('theme_preset', $theme_preset ?? 'quizsnap-classic') }}">
                            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3" role="radiogroup" aria-label="System theme">
                                @foreach($theme_presets ?? [] as $presetId => $preset)
                                    @php $selected = old('theme_preset', $theme_preset ?? 'quizsnap-classic') === $presetId; @endphp
                                    <button type="button"
                                            class="theme-preset-card text-left rounded-xl border-2 p-4 transition-all duration-150 {{ $selected ? 'border-primary-500 ring-2 ring-primary-200 bg-white shadow-sm' : 'border-gray-200 bg-white hover:border-gray-300 hover:shadow-sm' }}"
                                            data-theme-preset="{{ $presetId }}"
                                            aria-pressed="{{ $selected ? 'true' : 'false' }}">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="h-7 w-7 rounded-lg shrink-0 border border-black/5" style="background: {{ $preset['brand'] }}"></span>
                                            <span class="h-7 w-7 rounded-lg shrink-0 border border-black/5" style="background: {{ $preset['primary'][600] ?? '#2563eb' }}"></span>
                                            <span class="h-7 w-7 rounded-lg shrink-0 border border-black/5" style="background: {{ $preset['wordmark_b'] ?? $preset['brand'] }}"></span>
                                        </div>
                                        <p class="text-sm font-semibold text-gray-900">{{ $preset['name'] }}</p>
                                        <p class="text-xs text-gray-500 mt-1 leading-relaxed">{{ $preset['description'] }}</p>
                                        <div class="mt-3 flex items-center gap-1.5 text-xs font-medium">
                                            <span class="theme-wordmark-a font-display font-bold" style="color: {{ $preset['wordmark_a'] }}">Quiz</span><span class="font-display font-bold" style="color: {{ $preset['wordmark_b'] }}">Snap</span>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @if(session('admin_role') === 'super_admin')
                        <div class="rounded-lg border border-gray-200 bg-gray-50/50 p-5 space-y-4">
                            <h3 class="text-sm font-semibold text-gray-800">Quiz access controls</h3>
                            <div class="space-y-3 pt-2 border-t border-gray-200">
                                <label class="flex items-start gap-3 cursor-pointer group">
                                    <input type="checkbox" name="disable_ip_device_restrictions" value="1" {{ old('disable_ip_device_restrictions', $disable_ip_device_restrictions ?? false) ? 'checked' : '' }} class="w-4 h-4 mt-0.5 text-primary-600 border-gray-300 rounded focus:ring-primary-500 shrink-0">
                                    <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900">Allow shared networks/PCs for quiz access</span>
                                </label>
                                <p class="text-xs text-gray-500 ml-7">Students can take the same quiz from the same network or reused computers. Disables IP/device uniqueness and session IP mismatch blocking.</p>
                            </div>
                        </div>

                        <div class="rounded-lg border border-gray-200 bg-gray-50/50 p-5 space-y-4">
                            <h3 class="text-sm font-semibold text-gray-800">Staff account creation – SMS</h3>
                            <label class="flex items-start gap-3 cursor-pointer group">
                                <input type="checkbox" name="send_sms_on_staff_creation" value="1" {{ old('send_sms_on_staff_creation', $send_sms_on_staff_creation ?? false) ? 'checked' : '' }} class="w-4 h-4 mt-0.5 text-primary-600 border-gray-300 rounded focus:ring-primary-500 shrink-0">
                                <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900">Send SMS to examiner/coordinator on account creation</span>
                            </label>
                            <p class="text-xs text-gray-500 ml-7">New staff receive an SMS with username, password and login URL. Requires phone number and Arkesel API key (OTP tab). When off, admin sets the password manually.</p>
                        </div>

                        <div class="rounded-lg border border-gray-200 bg-gray-50/50 p-5 space-y-4">
                            <h3 class="text-sm font-semibold text-gray-800">Mobile landing hero image</h3>
                            <label class="flex items-start gap-3 cursor-pointer group">
                                <input type="checkbox" name="landing_hero_enabled" value="1" {{ old('landing_hero_enabled', $landing_hero_enabled ?? true) ? 'checked' : '' }} class="w-4 h-4 mt-0.5 text-primary-600 border-gray-300 rounded focus:ring-primary-500 shrink-0">
                                <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900">Show hero section on mobile homepage</span>
                            </label>
                            <p class="text-xs text-gray-500 ml-7">Hero image is shown on phones only (below the header). Use a URL or upload a file (stored locally).</p>
                            @if(!empty(trim($landing_hero_image ?? '')))
                                @php $heroImgUrl = trim($landing_hero_image); @endphp
                                <div class="pt-2 border-t border-gray-200">
                                    <p class="text-xs font-medium text-gray-500 mb-1.5">Current image</p>
                                    <img src="{{ e($heroImgUrl) }}" alt="Landing hero" class="max-w-[200px] max-h-[120px] object-cover rounded-lg border border-gray-200" referrerpolicy="no-referrer" loading="lazy" onerror="this.style.display='none'; var n=this.nextElementSibling; if(n) n.style.display='block';">
                                    <p class="landing-hero-img-error text-xs text-amber-600 mt-1" style="display: none;">Image could not be loaded. Use a direct image link or upload a file.</p>
                                </div>
                            @endif
                            <div class="pt-2 border-t border-gray-200 space-y-4">
                                <div>
                                    <label for="landing_hero_image_url" class="block text-sm font-medium text-gray-700 mb-1.5">Image URL</label>
                                    <input type="text" name="landing_hero_image_url" id="landing_hero_image_url" value="{{ old('landing_hero_image_url', $landing_hero_image ?? '') }}" placeholder="/images/hero.jpg or https://example.com/image.jpg" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
                                    <p class="text-xs text-gray-500 mt-1">Paste a link to an image. Leave blank to keep current or use upload below.</p>
                                </div>
                                <div>
                                    <label for="landing_hero_image_file" class="block text-sm font-medium text-gray-700 mb-1.5">Or upload image</label>
                                    <input type="file" name="landing_hero_image_file" id="landing_hero_image_file" accept="image/*" class="block w-full text-sm text-gray-600 file:mr-2 file:py-2 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 file:border file:border-gray-200">
                                    <p class="text-xs text-gray-500 mt-1">Stored locally under public storage. Max 5 MB. If both URL and file are set, file is used.</p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-lg border border-gray-200 bg-gray-50/50 p-5 space-y-4">
                            <h3 class="text-sm font-semibold text-gray-800">Login page hero image</h3>
                            <p class="text-xs text-gray-500">Hero section on the staff login page (<code class="px-1 py-0.5 bg-gray-100 rounded text-gray-700">/login</code>). Use a direct image URL or upload from local. Default: <code class="px-1 py-0.5 bg-gray-100 rounded text-gray-700">public/assets/hero-section.jpg</code>.</p>
                            @if(!empty(trim($login_hero_image ?? '')))
                                @php $loginHeroImgUrl = trim($login_hero_image); @endphp
                                <div class="pt-2 border-t border-gray-200">
                                    <p class="text-xs font-medium text-gray-500 mb-1.5">Current image</p>
                                    <img src="{{ e($loginHeroImgUrl) }}" alt="Login hero" class="max-w-[200px] max-h-[120px] object-cover rounded-lg border border-gray-200" referrerpolicy="no-referrer" loading="lazy" onerror="this.style.display='none'; var n=this.nextElementSibling; if(n) n.style.display='block';">
                                    <p class="login-hero-img-error text-xs text-amber-600 mt-1" style="display: none;">Image could not be loaded. Use a direct image link or upload a file.</p>
                                </div>
                            @endif
                            <div class="pt-2 {{ !empty(trim($login_hero_image ?? '')) ? 'border-t border-gray-200' : '' }} space-y-4">
                                <div>
                                    <label for="login_hero_image_url" class="block text-sm font-medium text-gray-700 mb-1.5">Image URL</label>
                                    <input type="text" name="login_hero_image_url" id="login_hero_image_url" value="{{ old('login_hero_image_url', $login_hero_image ?? '') }}" placeholder="/images/hero.jpg or https://example.com/hero.jpg" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
                                    <p class="text-xs text-gray-500 mt-1">Paste a direct link to an image. Leave blank to keep current or use upload below.</p>
                                </div>
                                <div>
                                    <label for="login_hero_image_file" class="block text-sm font-medium text-gray-700 mb-1.5">Or upload from local</label>
                                    <input type="file" name="login_hero_image_file" id="login_hero_image_file" accept="image/*" class="block w-full text-sm text-gray-600 file:mr-2 file:py-2 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 file:border file:border-gray-200">
                                    <p class="text-xs text-gray-500 mt-1">Stored locally under public storage. Max 5 MB. If both URL and file are set, file is used.</p>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if(session('admin_role') === 'super_admin')
                        <div class="rounded-lg border border-gray-200 bg-gray-50/50 p-5 space-y-4">
                            <h3 class="text-sm font-semibold text-gray-800">Landing page – Quiz token</h3>
                            <label class="flex items-start gap-3 cursor-pointer group">
                                <input type="checkbox" name="landing_show_quiz_token" value="1" {{ old('landing_show_quiz_token', $landing_show_quiz_token ?? false) ? 'checked' : '' }} class="w-4 h-4 mt-0.5 text-primary-600 border-gray-300 rounded focus:ring-primary-500 shrink-0">
                                <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900">Show quiz token input on landing page</span>
                            </label>
                            <p class="text-xs text-gray-500 ml-7">When on, students see the quiz token field on the homepage. When off, the token field is hidden (students can still start quizzes via direct links or from their dashboard).</p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Tab: Email -->
                <div class="settings-tab-content p-6 hidden" data-tab-content="email" id="tab-content-email">
                    <div class="mb-6">
                        <h2 class="text-base font-semibold text-gray-900 mb-1">Email</h2>
                        <p class="text-sm text-gray-600">Outgoing mail configuration. Stored in database (password encrypted). Used for password reset and notifications.</p>
                    </div>
                    <div class="rounded-lg border border-primary-200 bg-primary-50/80 p-4 mb-6 text-sm text-primary-800">
                        <p class="font-medium">Secure SSL/TLS (recommended)</p>
                        <p class="mt-1 text-primary-700">Host: mail.ausweblabs.com — Port: 465 (SSL). Username: reset@ausweblabs.com. Use the account password. IMAP/POP3/SMTP require authentication.</p>
                    </div>
                    <div class="rounded-lg border border-amber-200 bg-amber-50/80 p-4 mb-6 text-sm text-amber-900">
                        <p class="font-medium">Avoid spam folder</p>
                        <p class="mt-1">Use a <strong>From address on the same domain</strong> as your SMTP username (e.g. quiz@manuelcode.info). In your hosting DNS, add <strong>SPF</strong>, <strong>DKIM</strong>, and <strong>DMARC</strong> records for that domain. Gmail and other providers trust authenticated, branded transactional mail much more than plain text tests.</p>
                    </div>
                    <div class="space-y-6">
                        <div class="rounded-lg border border-gray-200 bg-gray-50/50 p-5 space-y-4">
                            <h3 class="text-sm font-semibold text-gray-800">SMTP server</h3>
                            <div>
                                <label for="mail_mailer" class="block text-sm font-medium text-gray-700 mb-1.5">Mailer</label>
                                <select name="mail_mailer" id="mail_mailer" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
                                    <option value="smtp" {{ ($mail_mailer ?? '') === 'smtp' ? 'selected' : '' }}>SMTP</option>
                                    <option value="sendmail" {{ ($mail_mailer ?? '') === 'sendmail' ? 'selected' : '' }}>Sendmail</option>
                                    <option value="log" {{ ($mail_mailer ?? '') === 'log' ? 'selected' : '' }}>Log (no send)</option>
                                </select>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="mail_host" class="block text-sm font-medium text-gray-700 mb-1.5">Host</label>
                                    <input type="text" name="mail_host" id="mail_host" value="{{ old('mail_host', $mail_host ?? 'mail.ausweblabs.com') }}" placeholder="mail.example.com" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
                                    <p class="text-xs text-gray-500 mt-1">Mail server hostname (e.g. mail.example.com), not your email address. If you enter an email here, it will be saved as mail.{domain}.</p>
                                </div>
                                <div>
                                    <label for="mail_port" class="block text-sm font-medium text-gray-700 mb-1.5">Port</label>
                                    <input type="text" name="mail_port" id="mail_port" value="{{ old('mail_port', $mail_port ?? '465') }}" placeholder="465" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
                                </div>
                            </div>
                            <div>
                                <label for="mail_username" class="block text-sm font-medium text-gray-700 mb-1.5">Username</label>
                                <input type="email" name="mail_username" id="mail_username" value="{{ old('mail_username', $mail_username ?? 'reset@ausweblabs.com') }}" placeholder="quiz@example.com" autocomplete="off" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
                                <p class="text-xs text-gray-500 mt-1">Full email address for the mailbox (same as From address for most hosts).</p>
                            </div>
                            <div>
                                <label for="mail_password" class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                                <input type="password" name="mail_password" id="mail_password" autocomplete="new-password" placeholder="Leave blank to keep current" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
                                <p class="text-xs text-gray-500 mt-1">Stored encrypted. Leave blank to keep existing password.</p>
                            </div>
                            <div>
                                <label for="mail_encryption" class="block text-sm font-medium text-gray-700 mb-1.5">Encryption</label>
                                <select name="mail_encryption" id="mail_encryption" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
                                    <option value="tls" {{ ($mail_encryption ?? '') === 'tls' ? 'selected' : '' }}>TLS</option>
                                    <option value="ssl" {{ ($mail_encryption ?? 'ssl') === 'ssl' ? 'selected' : '' }}>SSL</option>
                                    <option value="" {{ ($mail_encryption ?? '') === '' ? 'selected' : '' }}>None</option>
                                </select>
                            </div>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-gray-50/50 p-5 space-y-4">
                            <h3 class="text-sm font-semibold text-gray-800">From (sender)</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="mail_from_address" class="block text-sm font-medium text-gray-700 mb-1.5">From address</label>
                                    <input type="email" name="mail_from_address" id="mail_from_address" value="{{ old('mail_from_address', $mail_from_address ?? 'reset@ausweblabs.com') }}" placeholder="reset@ausweblabs.com" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
                                </div>
                                <div>
                                    <label for="mail_from_name" class="block text-sm font-medium text-gray-700 mb-1.5">From name</label>
                                    <input type="text" name="mail_from_name" id="mail_from_name" value="{{ old('mail_from_name', $mail_from_name ?? 'QuizSnap') }}" placeholder="QuizSnap" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
                                </div>
                            </div>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-gray-50/50 p-5 space-y-4">
                            <h3 class="text-sm font-semibold text-gray-800">Result-ready notification</h3>
                            <p class="text-sm text-gray-600">When a student finishes a quiz and submits their answers, their result is “ready.” You can get an email each time that happens so you know without opening the dashboard.</p>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="notify_result_ready" value="1" {{ old('notify_result_ready', $notify_result_ready ?? false) ? 'checked' : '' }} class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                                <span class="text-sm font-medium text-gray-700">Send an email when a student submits a quiz</span>
                            </label>
                            <div>
                                <label for="notify_result_email" class="block text-sm font-medium text-gray-700 mb-1.5">Email address to notify</label>
                                <input type="email" name="notify_result_email" id="notify_result_email" value="{{ old('notify_result_email', $notify_result_email ?? '') }}" placeholder="e.g. examiner@example.com" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
                                <p class="text-xs text-gray-500 mt-1">Who receives the notification (e.g. examiner or admin). Leave blank to turn off; one email is sent per quiz submission.</p>
                            </div>
                        </div>
                        @if($can_manage_backup ?? false)
                        <div class="rounded-lg border border-gray-200 bg-gray-50/50 p-5 space-y-4">
                            <h3 class="text-sm font-semibold text-gray-800">Test email delivery</h3>
                            <p class="text-sm text-gray-600">Send test messages to confirm SMTP settings and preview the password reset template. Save settings first if you changed host, port, username, password, or from address.</p>
                            <div class="flex flex-wrap items-end gap-2">
                                <div>
                                    <label for="email-test-to" class="block text-xs font-medium text-gray-500 mb-0.5">Recipient email</label>
                                    <input type="email" id="email-test-to" value="{{ old('notify_result_email', $notify_result_email ?? $mail_from_address ?? 'kwofiee3@gmail.com') }}" placeholder="e.g. admin@example.com" class="block w-72 max-w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none">
                                </div>
                                <button type="button" id="email-test-btn" class="inline-flex items-center justify-center rounded-md border border-transparent bg-yellow-500 px-3 py-2 text-sm font-medium text-yellow-900 hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-1">Send test email</button>
                                <button type="button" id="password-reset-test-btn" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1">Send test password reset</button>
                            </div>
                            <div id="email-test-result" class="mt-3 hidden rounded-lg border p-3 text-sm"></div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Tab: AI -->
                <div class="settings-tab-content p-6 hidden" data-tab-content="ai" id="tab-content-ai">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">AI question generation</p>
                <p class="text-sm text-gray-500 mb-4">Examiners can generate quiz questions with DeepSeek or paste JSON. Set per-user limits under Users → edit examiner.</p>
                <div class="space-y-5">
                    <div class="rounded-lg border border-primary-200 bg-primary-50/50 p-4">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="ai_quiz_generation_enabled" value="1" class="mt-1 h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500" {{ old('ai_quiz_generation_enabled', $ai_quiz_generation_enabled ?? true) ? 'checked' : '' }}>
                            <span>
                                <span class="block text-sm font-medium text-gray-800">Allow AI question generation</span>
                                <span class="block text-xs text-gray-600 mt-1">When off, examiners can only import questions via JSON paste.</span>
                            </span>
                        </label>
                    </div>
                    <div>
                        <label for="deepseek_api_key" class="block text-xs font-medium text-gray-500 mb-0.5">DeepSeek API key</label>
                        @if($deepseek_key_set ?? false)
                            <p class="text-sm text-gray-600 mb-1.5">Current key: <code class="px-2 py-0.5 bg-gray-100 rounded text-gray-700">{{ $deepseek_key_masked ?? '' }}</code></p>
                            <input type="password" name="deepseek_api_key" id="deepseek_api_key" autocomplete="off" placeholder="Enter new key to replace, or leave blank to keep" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none @error('deepseek_api_key') border-red-500 @enderror">
                            <label class="flex items-center gap-2 cursor-pointer mt-1.5">
                                <input type="checkbox" name="clear_deepseek_key" value="1" class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500">
                                <span class="text-sm text-gray-600">Remove DeepSeek key</span>
                            </label>
                        @else
                            <input type="password" name="deepseek_api_key" id="deepseek_api_key" autocomplete="off" placeholder="sk-..." class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none @error('deepseek_api_key') border-red-500 @enderror">
                            <p class="text-xs text-gray-500 mt-1">Get a key from <a href="https://platform.deepseek.com/api_keys" target="_blank" rel="noopener" class="text-primary-600 hover:underline">DeepSeek Platform</a>. Ensure your account has balance for API usage.</p>
                        @endif
                        @error('deepseek_api_key')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="pt-4 border-t border-gray-100">
                        <label for="ai_quiz_cooldown_hours" class="block text-xs font-medium text-gray-500 mb-0.5">Token refill after exhaustion (hours)</label>
                        <input type="number" name="ai_quiz_cooldown_hours" id="ai_quiz_cooldown_hours" value="{{ old('ai_quiz_cooldown_hours', $ai_quiz_cooldown_hours ?? 24) }}" min="1" max="168" step="1" class="block w-28 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none @error('ai_quiz_cooldown_hours') border-red-500 @enderror">
                        <p class="text-xs text-gray-500 mt-1">When an examiner uses all AI tokens, generation is blocked until this period passes. Set each examiner&apos;s allocation under Users.</p>
                        @error('ai_quiz_cooldown_hours')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                </div>

                <!-- Tab: Supabase -->
                <div class="settings-tab-content p-6 hidden" data-tab-content="supabase" id="tab-content-supabase">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Supabase Storage</p>
                <p class="text-sm text-gray-500 mb-4">Configure Supabase Storage for student documents. Settings are stored in the database (service key encrypted) and used only on the backend.</p>
                <div class="space-y-5">
                    <div>
                        <label for="supabase_url" class="block text-xs font-medium text-gray-500 mb-0.5">Project URL</label>
                        <input type="url" name="supabase_url" id="supabase_url" value="{{ old('supabase_url', $supabase_url ?? '') }}" placeholder="https://your-project.supabase.co" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
                        <p class="text-xs text-gray-500 mt-1">Supabase project base URL (e.g. https://xyzcompany.supabase.co).</p>
                    </div>
                    <div>
                        <label for="supabase_bucket" class="block text-xs font-medium text-gray-500 mb-0.5">Bucket Name</label>
                        <input type="text" name="supabase_bucket" id="supabase_bucket" value="{{ old('supabase_bucket', $supabase_bucket ?? '') }}" placeholder="student-documents" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
                        <p class="text-xs text-gray-500 mt-1">Name of the storage bucket where student documents will be stored.</p>
                    </div>
                    <div>
                        <label for="supabase_service_key" class="block text-xs font-medium text-gray-500 mb-0.5">Service Key (service_role)</label>
                        @if($supabase_service_key_set ?? false)
                            <p class="text-sm text-gray-600 mb-1.5">
                                Current key:
                                <code class="px-2 py-0.5 bg-gray-100 rounded text-gray-700">
                                    {{ $supabase_service_key_masked ?? '••••' }}
                                </code>
                            </p>
                            <input type="password" name="supabase_service_key" id="supabase_service_key" autocomplete="off" placeholder="Enter new key to replace, or leave blank to keep" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none">
                            <label class="flex items-center gap-2 cursor-pointer mt-1.5">
                                <input type="checkbox" name="clear_supabase_service_key" value="1" class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500">
                                <span class="text-sm text-gray-600">Remove Supabase service key</span>
                            </label>
                        @else
                            <input type="password" name="supabase_service_key" id="supabase_service_key" autocomplete="off" placeholder="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..." class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none">
                            <p class="text-xs text-gray-500 mt-1">
                                Supabase <strong>service_role</strong> key. Stored encrypted and used only by the backend.
                            </p>
                        @endif
                    </div>
                    <div>
                        <label for="supabase_signed_url_ttl" class="block text-xs font-medium text-gray-500 mb-0.5">Signed URL expiry (minutes)</label>
                        <input type="number" name="supabase_signed_url_ttl" id="supabase_signed_url_ttl" value="{{ old('supabase_signed_url_ttl', $supabase_ttl ?? 60) }}" min="1" max="1440" class="block w-28 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none">
                        <p class="text-xs text-gray-500 mt-1">How long download links remain valid. Default: 60 minutes.</p>
                    </div>
                    @if($can_manage_backup ?? false)
                    <div class="pt-4 border-t border-gray-100">
                        <p class="text-xs font-medium text-gray-500 mb-0.5">Test connection</p>
                        <p class="text-xs text-gray-500 mb-2">Save settings first, then test. Verifies Supabase URL, service key, and bucket.</p>
                        <button type="button" id="supabase-test-btn" class="inline-flex items-center justify-center rounded-md border border-transparent bg-yellow-500 px-3 py-2 text-sm font-medium text-yellow-900 hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-1">
                            Test Supabase
                        </button>
                        <div id="supabase-test-result" class="mt-3 hidden rounded-lg border p-3 text-sm"></div>
                    </div>
                    @endif
                </div>
                </div>

                <!-- Tab: OTP (Arkesel) -->
                <div class="settings-tab-content p-6 hidden" data-tab-content="otp" id="tab-content-otp">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">OTP Providers (SMS)</p>
                <p class="text-sm text-gray-500 mb-4">Configure SMS OTP delivery via <a href="https://arkesel.com" target="_blank" rel="noopener" class="text-gray-600 hover:underline">Arkesel</a>. API keys are stored encrypted. Use the test below to verify delivery.</p>
                <div class="space-y-5">
                    <div>
                        <label for="otp_arkesel_api_key" class="block text-xs font-medium text-gray-500 mb-0.5">Arkesel API Key</label>
                        @if($otp_arkesel_key_set ?? false)
                            <p class="text-sm text-gray-600 mb-1.5">Current key: <code class="px-2 py-0.5 bg-gray-100 rounded text-gray-700">{{ $otp_arkesel_key_masked ?? '' }}</code></p>
                            <input type="password" name="otp_arkesel_api_key" id="otp_arkesel_api_key" autocomplete="off" placeholder="Enter new key to replace, or leave blank to keep" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none">
                            <label class="flex items-center gap-2 cursor-pointer mt-1.5">
                                <input type="checkbox" name="clear_otp_arkesel_key" value="1" class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500">
                                <span class="text-sm text-gray-600">Remove Arkesel API key</span>
                            </label>
                        @else
                            <input type="password" name="otp_arkesel_api_key" id="otp_arkesel_api_key" autocomplete="off" placeholder="Your Arkesel API key" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none">
                            <p class="text-xs text-gray-500 mt-1">Get your key from <a href="https://sms.arkesel.com/dashboard" target="_blank" rel="noopener" class="text-gray-600 hover:underline">Arkesel Dashboard</a> → SMS API. Stored encrypted.</p>
                        @endif
                    </div>
                    <div>
                        <label for="otp_arkesel_sender_id" class="block text-xs font-medium text-gray-500 mb-0.5">Sender ID (optional)</label>
                        <input type="text" name="otp_arkesel_sender_id" id="otp_arkesel_sender_id" value="{{ old('otp_arkesel_sender_id', $otp_arkesel_sender_id ?? 'QuizSnap') }}" placeholder="QuizSnap" maxlength="11" class="block w-full max-w-xs rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none">
                        <p class="text-xs text-gray-500 mt-1">Max 11 characters. Shown as SMS sender (e.g. QuizSnap).</p>
                    </div>
                    <div class="rounded-lg border border-primary-200 bg-primary-50/60 p-4 space-y-4">
                        <div>
                            <p class="text-xs font-semibold text-primary-800 uppercase tracking-wide mb-1">Student sign-in</p>
                            <p class="text-xs text-primary-900/80">Recommended: students verify their phone once during setup, then sign in with index + password on every visit.</p>
                        </div>
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="student_password_login_enabled" id="student_password_login_enabled" value="1" class="mt-1 h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500" {{ old('student_password_login_enabled', $student_password_login_enabled ?? true) ? 'checked' : '' }}>
                            <span>
                                <span class="block text-sm font-medium text-gray-800">Password sign-in (recommended)</span>
                                <span class="block text-xs text-gray-600 mt-1">First visit: index → phone SMS → password → name → email (if required). Every visit after: index + password only.</span>
                            </span>
                        </label>
                        <div id="student-password-options" class="ml-7 space-y-4 border-l-2 border-primary-200 pl-4 {{ old('student_password_login_enabled', $student_password_login_enabled ?? true) ? '' : 'hidden' }}">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" name="student_otp_return_login_enabled" id="student_otp_return_login_enabled" value="1" class="mt-1 h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500" {{ old('student_otp_return_login_enabled', $student_otp_return_login_enabled ?? false) ? 'checked' : '' }}>
                                <span>
                                    <span class="block text-sm font-medium text-gray-800">Allow SMS code after setup</span>
                                    <span class="block text-xs text-gray-600 mt-1">When off (default), returning students cannot skip password with “Get code by SMS”. SMS stays available only for first-time phone verification.</span>
                                </span>
                            </label>
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" name="student_onboarding_email_otp_enabled" value="1" class="mt-1 h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500" {{ old('student_onboarding_email_otp_enabled', $student_onboarding_email_otp_enabled ?? true) ? 'checked' : '' }}>
                                <span>
                                    <span class="block text-sm font-medium text-gray-800">Email OTP fallback during setup</span>
                                    <span class="block text-xs text-gray-600 mt-1">If SMS is not received during first-time sign-in, students can request a system-generated code by email. Requires SMTP host and username in Email settings.@if(!($mail_configured ?? false)) <strong class="text-amber-800">Disabled until email is configured.</strong>@endif</span>
                                </span>
                            </label>
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" name="student_email_required" value="1" class="mt-1 h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500" {{ old('student_email_required', $student_email_required ?? true) ? 'checked' : '' }}>
                                <span>
                                    <span class="block text-sm font-medium text-gray-800">Require email during first sign-in</span>
                                    <span class="block text-xs text-gray-600 mt-1">Collected after phone verification and password during first sign-in. Needed for password reset by email.</span>
                                </span>
                            </label>
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" name="student_password_reset_enabled" value="1" class="mt-1 h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500" {{ old('student_password_reset_enabled', $student_password_reset_enabled ?? true) ? 'checked' : '' }}>
                                <span>
                                    <span class="block text-sm font-medium text-gray-800">Password reset by email</span>
                                    <span class="block text-xs text-gray-600 mt-1">Students can request a reset link (index + email must match). Limited to 3 resets per 7 days.@if(!($student_password_reset_active ?? false) && ($student_password_reset_enabled ?? false)) <strong class="text-amber-800">Inactive until email is configured.</strong>@endif</span>
                                </span>
                            </label>
                        </div>
                        <p id="student-sms-only-note" class="text-xs text-gray-600 {{ old('student_password_login_enabled', $student_password_login_enabled ?? true) ? 'hidden' : '' }}">Password sign-in is off: students always sign in with index + phone and a one-time SMS code.</p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="student_otp_max_attempts" class="block text-xs font-medium text-gray-500 mb-0.5">Max failed OTP/password attempts</label>
                            <input type="number" name="student_otp_max_attempts" id="student_otp_max_attempts" min="3" max="20" value="{{ old('student_otp_max_attempts', $student_otp_max_attempts ?? 5) }}" class="block w-full max-w-xs rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none">
                        </div>
                        <div>
                            <label for="student_otp_lockout_minutes" class="block text-xs font-medium text-gray-500 mb-0.5">Lockout duration (minutes)</label>
                            <input type="number" name="student_otp_lockout_minutes" id="student_otp_lockout_minutes" min="5" max="120" value="{{ old('student_otp_lockout_minutes', $student_otp_lockout_minutes ?? 15) }}" class="block w-full max-w-xs rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none">
                        </div>
                    </div>
                    <div>
                        <label for="student_universal_otp_codes" class="block text-xs font-medium text-gray-500 mb-0.5">Universal student OTP codes (optional)</label>
                        <input type="text" name="student_universal_otp_codes" id="student_universal_otp_codes" value="{{ old('student_universal_otp_codes', $student_universal_otp_codes ?? '') }}" placeholder="111111,222222,333333" class="block w-full max-w-lg rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none" autocomplete="off" maxlength="500">
                        <p class="text-xs text-gray-500 mt-1">Comma-separated 6-digit institution codes used only when SMS delivery fails. Students enter them on the OTP step after a failed send. Leave blank to disable.</p>
                    </div>
                    <div class="pt-4 border-t border-gray-100">
                        <p class="text-xs font-medium text-gray-500 mb-0.5">Account balance</p>
                        <p class="text-xs text-gray-500 mb-2">Verify your Arkesel account has SMS credits (required for delivery).</p>
                        <button type="button" id="otp-balance-btn" class="inline-flex items-center justify-center rounded-md border border-transparent bg-yellow-500 px-3 py-2 text-sm font-medium text-yellow-900 hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-1">Check balance</button>
                        <div id="otp-balance-result" class="mt-3 hidden rounded-lg border p-3 text-sm"></div>
                    </div>
                    <div class="pt-4 border-t border-gray-100">
                        <p class="text-xs font-medium text-gray-500 mb-0.5">Test OTP delivery</p>
                        <p class="text-xs text-gray-500 mb-2">Save settings first if you changed the API key. Use international format (e.g. 233544919953 for Ghana). If you don’t receive the SMS, check balance above and your Arkesel dashboard for delivery status.</p>
                        <div class="flex flex-wrap items-end gap-2">
                            <div>
                                <label for="otp-test-phone" class="block text-xs font-medium text-gray-500 mb-0.5">Phone number</label>
                                <input type="text" id="otp-test-phone" placeholder="233544919953" autocomplete="off" class="block w-48 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none">
                            </div>
                            <button type="button" id="otp-test-btn" class="inline-flex items-center justify-center rounded-md border border-transparent bg-yellow-500 px-3 py-2 text-sm font-medium text-yellow-900 hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-1">Send test OTP</button>
                        </div>
                        <div id="otp-test-result" class="mt-3 hidden rounded-lg border p-3 text-sm"></div>
                    </div>
                </div>
                </div>

                @if($can_manage_proctoring ?? false)
                <!-- Tab: Proctoring (Super Admin only) -->
                <div class="settings-tab-content p-6 hidden" data-tab-content="proctoring" id="tab-content-proctoring">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Quiz Proctoring</p>
                <p class="text-sm text-gray-500 mb-5">Enable or disable quiz proctoring features. When disabled, the system will not enforce that feature. All options are on by default.</p>

                <div class="rounded-lg border border-gray-200 bg-gray-50/60 p-4 mb-5">
                    <p class="text-xs font-medium text-gray-500 mb-1">Violation image storage</p>
                    <p class="text-xs text-gray-500 mb-3">Proctoring violation images are stored on the server under <code class="px-1 py-0.5 bg-gray-100 rounded text-gray-700">storage/public/violations</code> by student index and date.</p>
                    <input type="hidden" name="violation_storage_driver" value="server">
                </div>

                <div class="rounded-lg border border-gray-200 bg-gray-50/60 p-4 mb-5">
                    <p class="text-xs font-medium text-gray-500 mb-1">Violation image retention (auto-delete)</p>
                    <p class="text-xs text-gray-500 mb-3">Server-stored violation images are automatically deleted after this many days. Primary is used by the daily cleanup job; secondary can be used for alternative policies (e.g. per-institution).</p>
                    <div class="flex flex-wrap items-center gap-6">
                        <div>
                            <label for="violation_retention_days_primary" class="block text-xs font-medium text-gray-600 mb-0.5">Primary (days)</label>
                            <input type="number" name="violation_retention_days_primary" id="violation_retention_days_primary" value="{{ old('violation_retention_days_primary', $violation_retention_days_primary ?? 21) }}" min="1" max="365" class="block w-24 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
                        </div>
                        <div>
                            <label for="violation_retention_days_secondary" class="block text-xs font-medium text-gray-600 mb-0.5">Secondary (days)</label>
                            <input type="number" name="violation_retention_days_secondary" id="violation_retention_days_secondary" value="{{ old('violation_retention_days_secondary', $violation_retention_days_secondary ?? 21) }}" min="1" max="365" class="block w-24 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
                        </div>
                    </div>
                </div>

                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-3">Student-side proctoring (during quiz)</p>
                <div class="space-y-4">
                    <div class="rounded-md border border-gray-100 bg-white p-3">
                        <p class="text-sm font-medium text-gray-800 mb-1">Camera</p>
                        <div class="flex flex-wrap items-center gap-4 mb-1">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="proctoring_camera_required" value="1" {{ old('proctoring_camera_required', $proctoring_camera_required ?? true) ? 'checked' : '' }} class="h-4 w-4 rounded-full border-gray-300 text-gray-600 focus:ring-gray-300">
                                <span class="text-sm text-gray-700">Camera required</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="proctoring_camera_required" value="0" {{ old('proctoring_camera_required', $proctoring_camera_required ?? true) ? '' : 'checked' }} class="h-4 w-4 rounded-full border-gray-300 text-gray-600 focus:ring-gray-300">
                                <span class="text-sm text-gray-600">Camera not required</span>
                            </label>
                        </div>
                        <p class="text-xs text-gray-500">When on, students must keep the camera on throughout the quiz; when off, camera is optional.</p>
                    </div>

                    <div class="rounded-md border border-gray-100 bg-white p-3">
                        <p class="text-sm font-medium text-gray-800 mb-1">Face monitoring (out of frame / no face)</p>
                        <div class="flex flex-wrap items-center gap-4 mb-1">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="proctoring_face_monitor" value="1" {{ old('proctoring_face_monitor', $proctoring_face_monitor ?? true) ? 'checked' : '' }} class="h-4 w-4 rounded-full border-gray-300 text-gray-600 focus:ring-gray-300">
                                <span class="text-sm text-gray-700">On</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="proctoring_face_monitor" value="0" {{ old('proctoring_face_monitor', $proctoring_face_monitor ?? true) ? '' : 'checked' }} class="h-4 w-4 rounded-full border-gray-300 text-gray-600 focus:ring-gray-300">
                                <span class="text-sm text-gray-600">Face monitoring off</span>
                            </label>
                        </div>
                        <p class="text-xs text-gray-500">Detect when face is missing or out of frame; repeated violations can auto-submit.</p>
                    </div>

                    <div class="rounded-md border border-gray-100 bg-white p-3">
                        <p class="text-sm font-medium text-gray-800 mb-1">Tab switch / blur detection</p>
                        <div class="flex flex-wrap items-center gap-4 mb-1">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="proctoring_tab_switch" value="1" {{ old('proctoring_tab_switch', $proctoring_tab_switch ?? true) ? 'checked' : '' }} class="h-4 w-4 rounded-full border-gray-300 text-gray-600 focus:ring-gray-300">
                                <span class="text-sm text-gray-700">On</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="proctoring_tab_switch" value="0" {{ old('proctoring_tab_switch', $proctoring_tab_switch ?? true) ? '' : 'checked' }} class="h-4 w-4 rounded-full border-gray-300 text-gray-600 focus:ring-gray-300">
                                <span class="text-sm text-gray-600">Tab switch detection off</span>
                            </label>
                        </div>
                        <p class="text-xs text-gray-500">Record violation when student switches tab or blurs the window.</p>
                    </div>

                    <div class="rounded-md border border-gray-100 bg-white p-3">
                        <p class="text-sm font-medium text-gray-800 mb-1">Object detection (phone, etc.)</p>
                        <div class="flex flex-wrap items-center gap-4 mb-1">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="proctoring_object_detect" value="1" {{ old('proctoring_object_detect', $proctoring_object_detect ?? true) ? 'checked' : '' }} class="h-4 w-4 rounded-full border-gray-300 text-gray-600 focus:ring-gray-300">
                                <span class="text-sm text-gray-700">On</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="proctoring_object_detect" value="0" {{ old('proctoring_object_detect', $proctoring_object_detect ?? true) ? '' : 'checked' }} class="h-4 w-4 rounded-full border-gray-300 text-gray-600 focus:ring-gray-300">
                                <span class="text-sm text-gray-600">Object detection off</span>
                            </label>
                        </div>
                        <p class="text-xs text-gray-500">Warn when prohibited objects (e.g. phone) are detected in the camera frame.</p>
                    </div>

                    <div class="rounded-md border border-gray-100 bg-white p-3">
                        <p class="text-sm font-medium text-gray-800 mb-1">Block right-click</p>
                        <div class="flex flex-wrap items-center gap-4 mb-1">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="proctoring_block_right_click" value="1" {{ old('proctoring_block_right_click', $proctoring_block_right_click ?? true) ? 'checked' : '' }} class="h-4 w-4 rounded-full border-gray-300 text-gray-600 focus:ring-gray-300">
                                <span class="text-sm text-gray-700">Block right-click</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="proctoring_block_right_click" value="0" {{ old('proctoring_block_right_click', $proctoring_block_right_click ?? true) ? '' : 'checked' }} class="h-4 w-4 rounded-full border-gray-300 text-gray-600 focus:ring-gray-300">
                                <span class="text-sm text-gray-600">Allow right-click</span>
                            </label>
                        </div>
                        <p class="text-xs text-gray-500">Prevent context menu and log violation on right-click.</p>
                    </div>

                    <div class="rounded-md border border-gray-100 bg-white p-3">
                        <p class="text-sm font-medium text-gray-800 mb-1">Block copy / paste</p>
                        <div class="flex flex-wrap items-center gap-4 mb-1">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="proctoring_block_copy_paste" value="1" {{ old('proctoring_block_copy_paste', $proctoring_block_copy_paste ?? true) ? 'checked' : '' }} class="h-4 w-4 rounded-full border-gray-300 text-gray-600 focus:ring-gray-300">
                                <span class="text-sm text-gray-700">Block copy / paste</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="proctoring_block_copy_paste" value="0" {{ old('proctoring_block_copy_paste', $proctoring_block_copy_paste ?? true) ? '' : 'checked' }} class="h-4 w-4 rounded-full border-gray-300 text-gray-600 focus:ring-gray-300">
                                <span class="text-sm text-gray-600">Allow copy / paste</span>
                            </label>
                        </div>
                        <p class="text-xs text-gray-500">Prevent copy, cut, and paste and log violation.</p>
                    </div>

                    <div class="rounded-md border border-amber-100 bg-amber-50/60 p-4 mt-2">
                        <p class="text-sm font-medium text-gray-800 mb-1">Auto-submit thresholds</p>
                        <p class="text-xs text-gray-500 mb-4">How many times or how long (in seconds) a rule must be broken before the quiz is auto-submitted.</p>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label for="proctoring_tab_switch_limit" class="block text-sm font-medium text-gray-700 mb-1">Tab switches</label>
                                <input type="number" name="proctoring_tab_switch_limit" id="proctoring_tab_switch_limit" min="1" max="20" value="{{ old('proctoring_tab_switch_limit', $proctoring_tab_switch_limit ?? 5) }}" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                <p class="text-xs text-gray-500 mt-1">Number of tab/window switches before auto-submit (default 5).</p>
                            </div>
                            <div>
                                <label for="proctoring_out_of_frame_seconds" class="block text-sm font-medium text-gray-700 mb-1">Face out of frame (seconds)</label>
                                <input type="number" name="proctoring_out_of_frame_seconds" id="proctoring_out_of_frame_seconds" min="5" max="120" value="{{ old('proctoring_out_of_frame_seconds', $proctoring_out_of_frame_seconds ?? 30) }}" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                <p class="text-xs text-gray-500 mt-1">Continuous seconds with no face visible (default 30).</p>
                            </div>
                            <div>
                                <label for="proctoring_multiple_faces_seconds" class="block text-sm font-medium text-gray-700 mb-1">Multiple faces (seconds)</label>
                                <input type="number" name="proctoring_multiple_faces_seconds" id="proctoring_multiple_faces_seconds" min="5" max="120" value="{{ old('proctoring_multiple_faces_seconds', $proctoring_multiple_faces_seconds ?? 35) }}" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                <p class="text-xs text-gray-500 mt-1">Continuous seconds with 2+ faces in frame (default 35).</p>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
                @endif
                @if(session('admin_role') === 'super_admin')
                <!-- Tab: Student dashboard banner -->
                <div class="settings-tab-content p-6 hidden" data-tab-content="student-dashboard" id="tab-content-student-dashboard">
                    <div class="mb-6">
                        <h2 class="text-base font-semibold text-gray-900 mb-1">Student dashboard</h2>
                        <p class="text-sm text-gray-600">Banner and mobile layout for the student overview page.</p>
                    </div>
                    @php
                        $mobileLayout = old('student_dashboard_mobile_layout', $student_dashboard_mobile_layout ?? 'classic');
                    @endphp
                    <div class="rounded-lg border border-gray-200 bg-gray-50/50 p-5 space-y-4 mb-6">
                        <h3 class="text-sm font-semibold text-gray-800">Mobile layout</h3>
                        <p class="text-xs text-gray-500">Phones use this layout on the overview page. Desktop always uses the compact grid.</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label class="flex items-start gap-3 cursor-pointer rounded-lg border {{ $mobileLayout === 'classic' ? 'border-primary-400 ring-1 ring-primary-200' : 'border-gray-200' }} bg-white p-4 hover:border-primary-300 transition-colors">
                                <input type="radio" name="student_dashboard_mobile_layout" value="classic" {{ $mobileLayout === 'classic' ? 'checked' : '' }} class="mt-1 text-primary-600 border-gray-300 focus:ring-primary-500">
                                <span>
                                    <span class="block text-sm font-medium text-gray-900">Classic</span>
                                    <span class="block text-xs text-gray-500 mt-0.5">Compact three-card grid with the quiz action panel below.</span>
                                </span>
                            </label>
                            <label class="flex items-start gap-3 cursor-pointer rounded-lg border {{ $mobileLayout === 'modern' ? 'border-primary-400 ring-1 ring-primary-200' : 'border-gray-200' }} bg-white p-4 hover:border-primary-300 transition-colors">
                                <input type="radio" name="student_dashboard_mobile_layout" value="modern" {{ $mobileLayout === 'modern' ? 'checked' : '' }} class="mt-1 text-primary-600 border-gray-300 focus:ring-primary-500">
                                <span>
                                    <span class="block text-sm font-medium text-gray-900">Modern cards</span>
                                    <span class="block text-xs text-gray-500 mt-0.5">LMS-style greeting, featured promo, course card, and quick-link tiles using your theme colors.</span>
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="mb-6">
                        <h2 class="text-base font-semibold text-gray-900 mb-1">Student dashboard banner</h2>
                        <p class="text-sm text-gray-600">Banner on the student overview page. Choose image only, or image with text (text left, image right).</p>
                    </div>
                    @php
                        $bannerMode = old('student_dashboard_banner_mode', $student_dashboard_banner_mode ?? 'image');
                        $bannerImage = old('student_dashboard_banner_image_url', $student_dashboard_banner_image ?? '');
                    @endphp
                    <div class="space-y-6">
                        <div class="rounded-lg border border-gray-200 bg-gray-50/50 p-5 space-y-4">
                            <label class="flex items-start gap-3 cursor-pointer group">
                                <input type="checkbox" name="student_dashboard_banner_enabled" value="1" {{ old('student_dashboard_banner_enabled', $student_dashboard_banner_enabled ?? true) ? 'checked' : '' }} class="w-4 h-4 mt-0.5 text-primary-600 border-gray-300 rounded focus:ring-primary-500 shrink-0">
                                <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900">Show banner on student dashboard</span>
                            </label>
                        </div>

                        <div class="rounded-lg border border-gray-200 bg-gray-50/50 p-5 space-y-4">
                            <h3 class="text-sm font-semibold text-gray-800">Banner style</h3>
                            <div class="space-y-3">
                                <label class="flex items-start gap-3 cursor-pointer rounded-lg border border-gray-200 bg-white p-4 hover:border-primary-300 transition-colors">
                                    <input type="radio" name="student_dashboard_banner_mode" value="image" {{ $bannerMode === 'image' ? 'checked' : '' }} class="mt-1 text-primary-600 border-gray-300 focus:ring-primary-500" onchange="window.toggleBannerTextFields && window.toggleBannerTextFields()">
                                    <span>
                                        <span class="block text-sm font-medium text-gray-900">Image only</span>
                                        <span class="block text-xs text-gray-500 mt-0.5">A compact banner showing your uploaded image across the full width.</span>
                                    </span>
                                </label>
                                <label class="flex items-start gap-3 cursor-pointer rounded-lg border border-gray-200 bg-white p-4 hover:border-primary-300 transition-colors">
                                    <input type="radio" name="student_dashboard_banner_mode" value="image_text" {{ $bannerMode === 'image_text' ? 'checked' : '' }} class="mt-1 text-primary-600 border-gray-300 focus:ring-primary-500" onchange="window.toggleBannerTextFields && window.toggleBannerTextFields()">
                                    <span>
                                        <span class="block text-sm font-medium text-gray-900">Image + text</span>
                                        <span class="block text-xs text-gray-500 mt-0.5">Headline and subtitle on the left, image on the right.</span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <div id="student-banner-text-fields" class="rounded-lg border border-gray-200 bg-gray-50/50 p-5 space-y-4 {{ $bannerMode === 'image' ? 'hidden' : '' }}">
                            <h3 class="text-sm font-semibold text-gray-800">Banner text</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="student_dashboard_banner_title" class="block text-sm font-medium text-gray-700 mb-1.5">Headline (first part)</label>
                                    <input type="text" name="student_dashboard_banner_title" id="student_dashboard_banner_title" value="{{ old('student_dashboard_banner_title', $student_dashboard_banner_title ?? 'Challenge Yourself.') }}" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
                                </div>
                                <div>
                                    <label for="student_dashboard_banner_title_accent" class="block text-sm font-medium text-gray-700 mb-1.5">Headline accent (highlight)</label>
                                    <input type="text" name="student_dashboard_banner_title_accent" id="student_dashboard_banner_title_accent" value="{{ old('student_dashboard_banner_title_accent', $student_dashboard_banner_title_accent ?? 'Achieve More.') }}" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
                                </div>
                            </div>
                            <div>
                                <label for="student_dashboard_banner_subtitle" class="block text-sm font-medium text-gray-700 mb-1.5">Subtitle</label>
                                <textarea name="student_dashboard_banner_subtitle" id="student_dashboard_banner_subtitle" rows="2" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">{{ old('student_dashboard_banner_subtitle', $student_dashboard_banner_subtitle ?? 'Take quizzes, track progress and achieve your goals every day.') }}</textarea>
                            </div>
                        </div>

                        <div class="rounded-lg border border-gray-200 bg-gray-50/50 p-5 space-y-4">
                            <h3 class="text-sm font-semibold text-gray-800">Banner image</h3>
                            @if(!empty(trim($bannerImage)))
                                <div>
                                    <p class="text-xs font-medium text-gray-500 mb-1.5">Current image</p>
                                    <img src="{{ e(trim($bannerImage)) }}" alt="Banner preview" class="max-w-full sm:max-w-md max-h-[160px] object-cover rounded-lg border border-gray-200" referrerpolicy="no-referrer" loading="lazy">
                                </div>
                            @endif
                            <div>
                                <label for="student_dashboard_banner_image_url" class="block text-sm font-medium text-gray-700 mb-1.5">Image URL</label>
                                <input type="text" name="student_dashboard_banner_image_url" id="student_dashboard_banner_image_url" value="{{ $bannerImage }}" placeholder="/images/banner.jpg or https://example.com/banner.jpg" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
                                <p class="text-xs text-gray-500 mt-1">Paste a site path (e.g. /images/…) or full URL. Leave blank to keep the current image.</p>
                            </div>
                            <div>
                                <label for="student_dashboard_banner_image_file" class="block text-sm font-medium text-gray-700 mb-1.5">Or upload image</label>
                                <input type="file" name="student_dashboard_banner_image_file" id="student_dashboard_banner_image_file" accept="image/*" class="block w-full text-sm text-gray-600 file:mr-2 file:py-2 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 file:border file:border-gray-200">
                                <p class="text-xs text-gray-500 mt-1">Stored locally. Max 5 MB. Recommended: wide landscape (e.g. 1200×400).</p>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                @if($show_backup_tab ?? false)
                <!-- Tab: Backup / Digest (primary administrator only) -->
                <div class="settings-tab-content p-6 hidden" data-tab-content="backup" id="tab-content-backup">
                    <div class="rounded-lg border border-gray-200 bg-gray-50/50 p-5 space-y-5">
                        <div>
                            <h2 class="text-base font-semibold text-gray-900 mb-1">Digest</h2>
                            <p class="text-sm text-gray-600">Optional contact for system digests and notifications.</p>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label for="notify_digest_recipient" class="block text-sm font-medium text-gray-700 mb-1.5">Email address</label>
                                <input type="email" name="notify_digest_recipient" id="notify_digest_recipient" value="{{ old('notify_digest_recipient') }}" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none" placeholder="{{ ($backup_email_configured ?? false) ? '••••••@••••••' : 'e.g. user@example.com' }}" autocomplete="off">
                                @if($backup_email_configured ?? false)
                                    <p class="text-xs text-gray-500 mt-1.5">Currently set. Leave blank to clear, or enter a new address to replace.</p>
                                @else
                                    <p class="text-xs text-gray-500 mt-1.5">Enter a valid email address. Leave blank to leave unset.</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-gray-200 bg-gray-50/50 p-5 space-y-4 mt-6">
                        <h2 class="text-base font-semibold text-gray-900 mb-1">Digest export options</h2>
                        @if($study_guide_unlocked ?? false)
                            <p class="text-sm text-gray-600">Cohort study guides (links valid 1 hour).</p>
                            @if(($class_groups_for_study_guide ?? collect())->isEmpty())
                                <p class="text-sm text-gray-500">No class groups.</p>
                            @else
                                <ul class="list-none space-y-1.5 text-sm">
                                    @foreach($class_groups_for_study_guide as $cg)
                                        <li>
                                            <a href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('dashboard.study-guide.show', now()->addHours(1), ['classGroup' => $cg->id]) }}" class="text-gray-700 hover:text-gray-900" style="text-decoration: none;">{{ $cg->name }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        @else
                            <p class="text-sm text-gray-600">Optional: enter the backup code to view digest export links.</p>
                            <div class="flex flex-wrap items-end gap-3" id="study-guide-unlock-wrap">
                                <div>
                                    <label for="study_guide_password" class="block text-sm font-medium text-gray-700 mb-1">Backup code</label>
                                    <input type="password" id="study_guide_password" autocomplete="off" class="block w-full min-w-[200px] rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none" placeholder="Optional">
                                </div>
                                <button type="button" id="study-guide-unlock-btn" class="inline-flex items-center justify-center rounded-md border border-gray-200 bg-gray-50 px-4 py-2 text-sm font-medium text-gray-50 focus:outline-none focus:ring-1 focus:ring-gray-300 min-w-[5rem]" title="Submit">&#8203;</button>
                            </div>
                            @if(session('error'))
                                <p class="text-sm text-red-600 mt-1">{{ session('error') }}</p>
                            @endif
                        @endif
                    </div>
                </div>
                @endif
            </div>

            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center gap-2 rounded-md border border-transparent bg-yellow-500 px-4 py-2 text-sm font-medium text-yellow-900 hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Save settings for this tab
                </button>
            </div>
        </form>

        {{-- Standalone form for study guide unlock (cannot nest form inside settings form) --}}
        @if($show_backup_tab ?? false)
        <form id="study-guide-unlock-form" action="{{ route('dashboard.settings.study-guide.unlock') }}" method="post" class="hidden">
            @csrf
            <input type="hidden" name="study_guide_password" id="study_guide_password_hidden">
        </form>
        @endif

    </div>
</div>

@push('scripts')
<script>
window.toggleBannerTextFields = function () {
    var fields = document.getElementById('student-banner-text-fields');
    var modeImage = document.querySelector('input[name="student_dashboard_banner_mode"][value="image"]');
    if (!fields || !modeImage) return;
    fields.classList.toggle('hidden', modeImage.checked);
};
</script>
<script>
// Tab switching + persist tab in URL hash so refresh keeps user on same tab
document.addEventListener('DOMContentLoaded', function() {
    if (window.toggleBannerTextFields) window.toggleBannerTextFields();

    var themeInput = document.getElementById('theme_preset');
    document.querySelectorAll('.theme-preset-card').forEach(function(card) {
        card.addEventListener('click', function() {
            var preset = this.getAttribute('data-theme-preset');
            if (themeInput && preset) themeInput.value = preset;
            document.querySelectorAll('.theme-preset-card').forEach(function(c) {
                var active = c === card;
                c.setAttribute('aria-pressed', active ? 'true' : 'false');
                c.classList.toggle('border-primary-500', active);
                c.classList.toggle('ring-2', active);
                c.classList.toggle('ring-primary-200', active);
                c.classList.toggle('shadow-sm', active);
                c.classList.toggle('border-gray-200', !active);
            });
        });
    });

    const tabBtns = document.querySelectorAll('.settings-tab-btn');
    const tabContents = document.querySelectorAll('.settings-tab-content');
    const validTabs = ['general', 'email', 'ai', 'supabase', 'otp', 'proctoring', 'backup', 'student-dashboard'];
    let activeTab = 'general';

    function switchToTab(targetTab) {
        if (!validTabs.includes(targetTab)) targetTab = 'general';
        activeTab = targetTab;
        location.hash = targetTab;
        tabBtns.forEach(function(b) {
            if (b.getAttribute('data-tab') === targetTab) {
                b.classList.add('border-primary-500', 'text-primary-600');
                b.classList.remove('border-transparent', 'text-gray-500');
            } else {
                b.classList.remove('border-primary-500', 'text-primary-600');
                b.classList.add('border-transparent', 'text-gray-500');
            }
        });
        tabContents.forEach(function(content) {
            content.classList.toggle('hidden', content.getAttribute('data-tab-content') !== targetTab);
        });
    }

    tabBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            switchToTab(this.getAttribute('data-tab'));
        });
    });

    var hash = (location.hash || '').replace(/^#/, '');
    if (validTabs.includes(hash)) {
        switchToTab(hash);
    } else {
        switchToTab('general');
    }

    var form = document.getElementById('settings-form');
    if (form) {
        var submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.addEventListener('click', function() {
                tabContents.forEach(function(content) {
                    if (content.classList.contains('hidden')) {
                        content.querySelectorAll('input, select, textarea').forEach(function(el) {
                            el.disabled = true;
                        });
                    }
                });
            });
        }
        form.addEventListener('submit', function() {
            var tabInput = document.getElementById('settings_tab');
            if (tabInput) tabInput.value = activeTab || (location.hash || '#general').replace(/^#/, '') || 'general';
        });
    }

    var passwordLoginToggle = document.getElementById('student_password_login_enabled');
    var passwordOptions = document.getElementById('student-password-options');
    var smsOnlyNote = document.getElementById('student-sms-only-note');
    if (passwordLoginToggle && passwordOptions) {
        function syncStudentAuthToggles() {
            var on = passwordLoginToggle.checked;
            passwordOptions.classList.toggle('hidden', !on);
            if (smsOnlyNote) smsOnlyNote.classList.toggle('hidden', on);
        }
        passwordLoginToggle.addEventListener('change', syncStudentAuthToggles);
        syncStudentAuthToggles();
    }

    var unlockBtn = document.getElementById('study-guide-unlock-btn');
    var unlockForm = document.getElementById('study-guide-unlock-form');
    var passwordInput = document.getElementById('study_guide_password');
    var passwordHidden = document.getElementById('study_guide_password_hidden');
    if (unlockBtn && unlockForm && passwordInput && passwordHidden) {
        unlockBtn.addEventListener('click', function() {
            var pwd = (passwordInput.value || '').trim();
            if (!pwd) {
                passwordInput.focus();
                return;
            }
            passwordHidden.value = pwd;
            unlockForm.submit();
        });
    }
});

// Supabase Test (all environments; controller restricts to primary super admin)
var supabaseBtn = document.getElementById('supabase-test-btn');
if (supabaseBtn) {
    supabaseBtn.addEventListener('click', function() {
        var btn = this;
        var resultEl = document.getElementById('supabase-test-result');
        resultEl.classList.remove('hidden', 'bg-success-50', 'border-success-200', 'text-success-800', 'bg-danger-50', 'border-danger-200', 'text-danger-800');
        resultEl.textContent = 'Testing…';
        btn.disabled = true;
        fetch('{{ route('dashboard.settings.supabase-test') }}', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json().then(function(d) { return { ok: r.ok, data: d }; }); })
        .then(function(res) {
            var d = res.data;
            resultEl.classList.remove('hidden');
            if (d.success) {
                resultEl.classList.add('bg-success-50', 'border', 'border-success-200', 'text-success-800');
                resultEl.textContent = d.message || 'Supabase connection OK.';
            } else {
                resultEl.classList.add('bg-danger-50', 'border', 'border-danger-200', 'text-danger-800');
                resultEl.textContent = (d.message || 'Supabase test failed.') + (d.detail ? ' ' + d.detail : '');
            }
        })
        .catch(function(err) {
            resultEl.classList.remove('hidden');
            resultEl.classList.add('bg-danger-50', 'border', 'border-danger-200', 'text-danger-800');
            resultEl.textContent = 'Request failed: ' + (err.message || 'Network error');
        })
        .finally(function() { btn.disabled = false; });
    });
}

// OTP Balance check & Test (available in all environments)
document.addEventListener('DOMContentLoaded', function() {
    var emailTestBtn = document.getElementById('email-test-btn');
    if (emailTestBtn) {
        emailTestBtn.addEventListener('click', function() {
            var toInput = document.getElementById('email-test-to');
            var resultEl = document.getElementById('email-test-result');
            var to = toInput && toInput.value ? toInput.value.trim() : '';
            if (!to) {
                resultEl.classList.remove('hidden');
                resultEl.classList.add('bg-danger-50', 'border', 'border-danger-200', 'text-danger-800');
                resultEl.textContent = 'Enter an email address first.';
                return;
            }
            resultEl.classList.remove('hidden', 'bg-success-50', 'border-success-200', 'text-success-800', 'bg-danger-50', 'border-danger-200', 'text-danger-800');
            resultEl.textContent = 'Sending test email…';
            emailTestBtn.disabled = true;
            var formData = new FormData();
            formData.append('to', to);
            formData.append('_token', document.querySelector('input[name="_token"]') && document.querySelector('input[name="_token"]').value);
            fetch('{{ route('dashboard.settings.email-test') }}', {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(function(r) { return r.json().then(function(d) { return { ok: r.ok, data: d }; }); })
            .then(function(res) {
                var d = res.data || {};
                resultEl.classList.remove('hidden');
                if (d.success) {
                    resultEl.classList.add('bg-success-50', 'border', 'border-success-200', 'text-success-800');
                    resultEl.textContent = d.message || 'Test email sent.';
                } else {
                    resultEl.classList.add('bg-danger-50', 'border', 'border-danger-200', 'text-danger-800');
                    resultEl.textContent = (d.message || 'Failed to send test email.') + (d.detail ? ' ' + d.detail : '');
                }
            })
            .catch(function(err) {
                resultEl.classList.remove('hidden');
                resultEl.classList.add('bg-danger-50', 'border', 'border-danger-200', 'text-danger-800');
                resultEl.textContent = 'Request failed: ' + (err.message || 'Network error');
            })
            .finally(function() { emailTestBtn.disabled = false; });
        });
    }

    var passwordResetTestBtn = document.getElementById('password-reset-test-btn');
    if (passwordResetTestBtn) {
        passwordResetTestBtn.addEventListener('click', function() {
            var toInput = document.getElementById('email-test-to');
            var resultEl = document.getElementById('email-test-result');
            var to = toInput && toInput.value ? toInput.value.trim() : '';
            if (!to) {
                resultEl.classList.remove('hidden');
                resultEl.classList.add('bg-danger-50', 'border', 'border-danger-200', 'text-danger-800');
                resultEl.textContent = 'Enter an email address first.';
                return;
            }
            resultEl.classList.remove('hidden', 'bg-success-50', 'border-success-200', 'text-success-800', 'bg-danger-50', 'border-danger-200', 'text-danger-800');
            resultEl.textContent = 'Sending password reset preview…';
            passwordResetTestBtn.disabled = true;
            var formData = new FormData();
            formData.append('to', to);
            formData.append('_token', document.querySelector('input[name="_token"]') && document.querySelector('input[name="_token"]').value);
            fetch('{{ route('dashboard.settings.password-reset-test') }}', {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(function(r) { return r.json().then(function(d) { return { ok: r.ok, data: d }; }); })
            .then(function(res) {
                var d = res.data || {};
                resultEl.classList.remove('hidden');
                if (d.success) {
                    resultEl.classList.add('bg-success-50', 'border', 'border-success-200', 'text-success-800');
                    resultEl.textContent = d.message || 'Password reset preview sent.';
                } else {
                    resultEl.classList.add('bg-danger-50', 'border', 'border-danger-200', 'text-danger-800');
                    resultEl.textContent = (d.message || 'Failed to send password reset preview.') + (d.detail ? ' ' + d.detail : '');
                }
            })
            .catch(function(err) {
                resultEl.classList.remove('hidden');
                resultEl.classList.add('bg-danger-50', 'border', 'border-danger-200', 'text-danger-800');
                resultEl.textContent = 'Request failed: ' + (err.message || 'Network error');
            })
            .finally(function() { passwordResetTestBtn.disabled = false; });
        });
    }

    var otpBalanceBtn = document.getElementById('otp-balance-btn');
    var otpBalanceResult = document.getElementById('otp-balance-result');
    if (otpBalanceBtn && otpBalanceResult) {
        otpBalanceBtn.addEventListener('click', function() {
            otpBalanceResult.classList.remove('hidden', 'bg-success-50', 'border-success-200', 'text-success-800', 'bg-danger-50', 'border-danger-200', 'text-danger-800');
            otpBalanceResult.textContent = 'Checking…';
            otpBalanceBtn.disabled = true;
            fetch('{{ route('dashboard.settings.otp-balance') }}', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(r) { return r.json().then(function(d) { return { ok: r.ok, data: d }; }); })
                .then(function(res) {
                    var d = res.data;
                    otpBalanceResult.classList.remove('hidden');
                    if (d.success) {
                        otpBalanceResult.classList.add('bg-success-50', 'border', 'border-success-200', 'text-success-800');
                        otpBalanceResult.textContent = 'SMS balance: ' + (d.sms_balance != null ? d.sms_balance : '—') + ' | Main balance: ' + (d.main_balance != null ? d.main_balance : '—');
                    } else {
                        otpBalanceResult.classList.add('bg-danger-50', 'border', 'border-danger-200', 'text-danger-800');
                        otpBalanceResult.textContent = (d.message || 'Could not check balance.') + (d.detail ? ' (' + d.detail + ')' : '');
                    }
                })
                .catch(function(err) {
                    otpBalanceResult.classList.remove('hidden');
                    otpBalanceResult.classList.add('bg-danger-50', 'border', 'border-danger-200', 'text-danger-800');
                    otpBalanceResult.textContent = 'Request failed: ' + (err.message || 'Network error');
                })
                .finally(function() { otpBalanceBtn.disabled = false; });
        });
    }

    var otpTestBtn = document.getElementById('otp-test-btn');
    if (otpTestBtn) {
        otpTestBtn.addEventListener('click', function() {
            var phoneInput = document.getElementById('otp-test-phone');
            var resultEl = document.getElementById('otp-test-result');
            var phone = phoneInput && phoneInput.value ? phoneInput.value.trim() : '';
            if (!phone) {
                resultEl.classList.remove('hidden');
                resultEl.classList.add('bg-danger-50', 'border', 'border-danger-200', 'text-danger-800');
                resultEl.textContent = 'Enter a phone number first.';
                return;
            }
            resultEl.classList.remove('hidden', 'bg-success-50', 'border-success-200', 'text-success-800', 'bg-danger-50', 'border-danger-200', 'text-danger-800');
            resultEl.textContent = 'Sending test OTP…';
            otpTestBtn.disabled = true;
            var formData = new FormData();
            formData.append('phone', phone);
            formData.append('_token', document.querySelector('input[name="_token"]') && document.querySelector('input[name="_token"]').value);
            fetch('{{ route('dashboard.settings.otp-test') }}', {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(function(r) { return r.json().then(function(d) { return { ok: r.ok, data: d }; }); })
            .then(function(res) {
                var d = res.data;
                resultEl.classList.remove('hidden');
                if (d.success) {
                    if (d.sms_delivered === false) {
                        resultEl.classList.add('bg-yellow-50', 'border', 'border-yellow-300', 'text-yellow-900');
                    } else {
                        resultEl.classList.add('bg-success-50', 'border', 'border-success-200', 'text-success-800');
                    }
                    resultEl.textContent = d.message || 'Test OTP sent.';
                    if (d.test_code) {
                        resultEl.textContent += ' Code: ' + d.test_code;
                    }
                } else {
                    resultEl.classList.add('bg-danger-50', 'border', 'border-danger-200', 'text-danger-800');
                    resultEl.textContent = (d.message || 'Failed to send test OTP.') + (d.detail ? ' (' + d.detail + ')' : '');
                }
            })
            .catch(function(err) {
                resultEl.classList.remove('hidden');
                resultEl.classList.add('bg-danger-50', 'border', 'border-danger-200', 'text-danger-800');
                resultEl.textContent = 'Request failed: ' + (err.message || 'Network error');
            })
            .finally(function() { otpTestBtn.disabled = false; });
        });
    }
});
</script>
@endpush
@endsection
