@extends('layouts.student')

@section('title', 'Enter your index number')
@section('body_class', 'bg-offwhite')

@php
    $universalOtpConfigured = \App\Services\StudentUniversalOtp::isConfigured();
@endphp
@section('content')
<div class="min-h-[100dvh] min-h-screen flex items-center justify-center px-4 py-8 pl-[max(1rem,env(safe-area-inset-left))] pr-[max(1rem,env(safe-area-inset-right))] pb-[max(1.5rem,env(safe-area-inset-bottom))]">
    <div class="max-w-md w-full">
        <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Enter your index number</h1>
            @if(isset($quiz) && $quiz)
                <p class="text-gray-600 text-sm mb-2">{{ $quiz->title }}</p>
            @endif
            @if(!empty($password_login_enabled))
                <p class="text-gray-600 text-xs mb-3">First time: verify phone once by SMS and set a password. After that, sign in with your password only.</p>
            @endif

            {{-- Step 1: Index number --}}
            <div id="step-index" class="space-y-4">
                <p class="text-gray-600 text-sm mb-4">Enter your index number to continue.</p>
                <form id="login-form" class="space-y-4">
                    <div>
                        <label for="index_number" class="block text-sm font-medium text-gray-700 mb-1">Index number</label>
                        <input type="text" id="index_number" name="index_number" required placeholder="e.g. BC/ITS/24/047" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500" style="text-transform: uppercase;" autocomplete="off">
                    </div>
                    <div id="login-error" class="hidden">
                        <div class="bg-danger-50 border border-danger-200 rounded-lg p-3 text-sm text-danger-800" id="login-error-text"></div>
                        <div id="login-error-index-guidance" class="hidden mt-2 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-950">
                            <p class="font-semibold mb-1">Contact your class rep or lecturer</p>
                            <p>Your index is not on the class list yet. Ask your <strong>class rep</strong> or <strong>lecturer</strong> to add you — please use live chat only for technical issues, not index problems.</p>
                        </div>
                        <p id="login-error-support-wrap" class="hidden mt-2 text-sm text-gray-600">
                            Need technical help?
                            <button type="button" id="login-error-live-support" class="text-indigo-600 hover:underline font-medium">Open live chat</button>
                        </p>
                    </div>
                    <button type="submit" class="w-full py-2.5 px-4 text-sm font-semibold rounded-lg text-white bg-primary-600 hover:bg-primary-700 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                        <span id="btn-text">Continue</span>
                    </button>
                </form>
            </div>

            <div id="step-email" class="space-y-4 hidden">
                <p class="text-sm text-gray-600" id="email-step-message">Enter your email address for account recovery.</p>
                <div>
                    <label for="student_email" class="block text-sm font-medium text-gray-700 mb-1">Email address</label>
                    <input type="email" id="student_email" placeholder="you@example.com" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-primary-500" autocomplete="email">
                </div>
                <div id="email-error" class="hidden">
                    <div class="bg-danger-50 border border-danger-200 rounded-lg p-3 text-sm text-danger-800" id="email-error-text"></div>
                </div>
                <button type="button" id="btn-save-email" class="w-full py-2.5 px-4 text-sm font-semibold rounded-lg text-white bg-primary-600 hover:bg-primary-700">Continue</button>
                <button type="button" id="btn-back-email-to-index" class="w-full py-2 px-4 text-sm font-medium rounded-lg text-gray-700 bg-gray-200 hover:bg-gray-300">← Back</button>
            </div>

            @if(!empty($password_login_enabled))
            <div id="step-password" class="space-y-4 hidden">
                <p class="text-sm text-gray-600" id="password-step-message">Enter your password.</p>
                <div>
                    <label for="login_password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" id="login_password" autocomplete="current-password" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>
                <div id="password-error" class="hidden">
                    <div class="bg-danger-50 border border-danger-200 rounded-lg p-3 text-sm text-danger-800" id="password-error-text"></div>
                </div>
                <button type="button" id="btn-verify-password" class="w-full py-2.5 px-4 text-sm font-semibold rounded-lg text-white bg-primary-600 hover:bg-primary-700 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">Continue</button>
                @if(!empty($otp_return_login_enabled))
                <button type="button" id="btn-password-use-sms" class="w-full py-2 px-4 text-sm font-medium rounded-lg text-primary-700 bg-primary-50 border border-primary-200 hover:bg-primary-100">Get a code by SMS instead</button>
                @endif
                <button type="button" id="btn-back-password-to-index" class="w-full py-2 px-4 text-sm font-medium rounded-lg text-gray-700 bg-gray-200 hover:bg-gray-300">← Back</button>
            </div>
            @endif

            {{-- Step 2: Phone (required before first quiz; we save it to your index for future logins) --}}
            <div id="step-phone" class="space-y-4 hidden">
                <div class="rounded-lg bg-primary-50 border border-primary-200 p-3 mb-2 text-sm text-primary-900">
                    <p class="font-medium mb-1">Use an active phone number</p>
                    <p>We'll send a one-time code by SMS. <strong>Keep that code—it will be your login for the next 14 days</strong> so you can open your dashboard and see your results. We'll also save your phone and name to your index for future logins.</p>
                </div>
                <p class="text-sm text-gray-600" id="phone-step-message">Enter your active phone number (e.g. 233XXXXXXXXX).</p>
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone number</label>
                    <input type="tel" id="phone" name="phone" placeholder="233XXXXXXXXX" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500" autocomplete="tel">
                </div>
                @if(!empty($password_login_enabled))
                <div id="phone-password-setup-wrap" class="space-y-3 hidden">
                    <div>
                        <label for="setup_password" class="block text-sm font-medium text-gray-700 mb-1">Choose password (min {{ \App\Models\Student::PASSWORD_MIN_LENGTH }} characters)</label>
                        <input type="password" id="setup_password" autocomplete="new-password" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div>
                        <label for="setup_password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm password</label>
                        <input type="password" id="setup_password_confirmation" autocomplete="new-password" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>
                @endif
                <div id="phone-error" class="hidden">
                    <div class="bg-danger-50 border border-danger-200 rounded-lg p-3 text-sm text-danger-800" id="phone-error-text"></div>
                </div>
                <button type="button" id="btn-send-otp" class="w-full py-2.5 px-4 text-sm font-semibold rounded-lg text-white bg-primary-600 hover:bg-primary-700 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">Send code</button>
                <button type="button" id="btn-back-to-index" class="w-full py-2 px-4 text-sm font-medium rounded-lg text-gray-700 bg-gray-200 hover:bg-gray-300">← Back</button>
            </div>

            {{-- Step 3: OTP (6 separate boxes; auto-submit when last digit entered) --}}
            <div id="step-otp" class="space-y-4 hidden">
                <p class="text-sm text-gray-600" id="otp-step-message">Enter the 6-digit code sent to your phone. Keep it—it's your login for the next 14 days.</p>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Code</label>
                    <div class="flex justify-center gap-2" id="otp-boxes-wrap">
                        @for($i = 0; $i < 6; $i++)
                        <input type="text" inputmode="numeric" pattern="[0-9]" maxlength="1" data-otp-index="{{ $i }}" autocomplete="off"
                            class="w-11 h-12 text-center text-xl font-semibold border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 otp-digit">
                        @endfor
                    </div>
                    <input type="hidden" id="otp_code" name="code" value="">
                </div>
                <div>
                    <label for="otp_name" class="block text-sm font-medium text-gray-700 mb-1">Your name (optional)</label>
                    <input type="text" id="otp_name" name="student_name" placeholder="Full name" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500" autocomplete="name" style="text-transform: capitalize;">
                </div>
                <div id="otp-error" class="hidden">
                    <div class="bg-danger-50 border border-danger-200 rounded-lg p-3 text-sm text-danger-800" id="otp-error-text"></div>
                </div>
                <button type="button" id="btn-verify-otp" class="w-full py-2.5 px-4 text-sm font-semibold rounded-lg text-white bg-primary-600 hover:bg-primary-700 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">Verify and continue</button>
                <p class="text-center text-sm text-gray-500">Didn't get the code? <button type="button" id="btn-resend-otp" class="text-primary-600 hover:underline font-medium">Resend code</button></p>
                <div id="otp-universal-fallback-wrap" class="hidden rounded-lg border border-blue-200 bg-blue-50 p-3">
                    <p id="otp-universal-fallback-hint" class="text-xs text-blue-900"></p>
                </div>
                @if(!empty($onboarding_email_otp_enabled) && !empty($mail_configured))
                <div id="otp-email-fallback-wrap" class="hidden rounded-lg border border-amber-200 bg-amber-50 p-3 space-y-3">
                    <p id="otp-email-fallback-hint" class="text-xs text-amber-900 hidden">Having trouble with SMS? Get a one-time code by email instead (setup only).</p>
                    <button type="button" id="btn-show-email-fallback" class="w-full py-2 px-4 text-sm font-medium rounded-lg text-amber-900 bg-white border border-amber-300 hover:bg-amber-100">Get code by email instead</button>
                    <div id="otp-email-fallback-fields" class="hidden space-y-3">
                        <div>
                            <label for="fallback_email" class="block text-sm font-medium text-gray-700 mb-1">Email address</label>
                            <input type="email" id="fallback_email" placeholder="you@example.com" autocomplete="email" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            <p class="text-xs text-gray-600 mt-1">We will save this to your account and send a code that expires in 15 minutes.</p>
                        </div>
                        <button type="button" id="btn-send-email-otp" class="w-full py-2.5 px-4 text-sm font-semibold rounded-lg text-white bg-primary-600 hover:bg-primary-700">Send code to email</button>
                    </div>
                </div>
                @endif
                <p id="otp-days-remaining" class="text-center text-sm text-gray-500 mt-1 hidden" aria-live="polite"></p>
                <button type="button" id="btn-back-to-phone" class="w-full py-2 px-4 text-sm font-medium rounded-lg text-gray-700 bg-gray-200 hover:bg-gray-300">← Back</button>
            </div>

            @if(!empty($password_login_enabled))
            <div id="step-setup-password" class="space-y-4 hidden">
                <p class="text-sm text-gray-600" id="setup-password-message">Phone verified. Create a password for your account.</p>
                <div>
                    <label for="onboard_setup_password" class="block text-sm font-medium text-gray-700 mb-1">Password (min {{ \App\Models\Student::PASSWORD_MIN_LENGTH }} characters)</label>
                    <input type="password" id="onboard_setup_password" autocomplete="new-password" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>
                <div>
                    <label for="onboard_setup_password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm password</label>
                    <input type="password" id="onboard_setup_password_confirmation" autocomplete="new-password" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>
                <div id="setup-password-error" class="hidden">
                    <div class="bg-danger-50 border border-danger-200 rounded-lg p-3 text-sm text-danger-800" id="setup-password-error-text"></div>
                </div>
                <button type="button" id="btn-setup-password" class="w-full py-2.5 px-4 text-sm font-semibold rounded-lg text-white bg-primary-600 hover:bg-primary-700">Continue</button>
            </div>

            <div id="step-setup-name" class="space-y-4 hidden">
                <p class="text-sm text-gray-600" id="setup-name-message">What name should we show on your account?</p>
                <div>
                    <label for="setup_student_name" class="block text-sm font-medium text-gray-700 mb-1">Your name</label>
                    <input type="text" id="setup_student_name" placeholder="Full name" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500" autocomplete="name" style="text-transform: capitalize;">
                </div>
                <div id="setup-name-error" class="hidden">
                    <div class="bg-danger-50 border border-danger-200 rounded-lg p-3 text-sm text-danger-800" id="setup-name-error-text"></div>
                </div>
                <button type="button" id="btn-setup-name" class="w-full py-2.5 px-4 text-sm font-semibold rounded-lg text-white bg-primary-600 hover:bg-primary-700">Continue</button>
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    var studentPasswordMinLength = {{ \App\Models\Student::PASSWORD_MIN_LENGTH }};
    var csrf = document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content;
    var csrfRefreshUrl = '{{ route("student.account.csrf-token") }}';
    var jsonHeaders = function(token) {
        return {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token || csrf || '',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        };
    };
    function ensureFreshCsrf() {
        if (!csrfRefreshUrl) return Promise.resolve(csrf);
        return fetch(csrfRefreshUrl, { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data && data.token) {
                    csrf = data.token;
                    var m = document.querySelector('meta[name="csrf-token"]');
                    if (m) m.setAttribute('content', csrf);
                }
                return csrf;
            });
    }
    function parseJsonResponse(r) {
        var ct = (r.headers.get('content-type') || '').toLowerCase();
        if (ct.indexOf('application/json') === -1) {
            return Promise.reject(new Error(r.status >= 500
                ? 'Server error. Please try again in a moment.'
                : 'Unexpected response from server.'));
        }
        return r.json().then(function(data) {
            if (!r.ok && data && !data.message) {
                data.message = r.status >= 500
                    ? 'Server error. Please try again in a moment.'
                    : 'Request failed. Please try again.';
            }
            return data;
        });
    }
    var passwordLoginEnabled = @json(!empty($password_login_enabled));
    var onboardingEmailOtpEnabled = @json(!empty($onboarding_email_otp_enabled) && !empty($mail_configured));
    var smsResendCount = 0;
    var stepIndex = document.getElementById('step-index');
    var stepEmail = document.getElementById('step-email');
    var stepPhone = document.getElementById('step-phone');
    var stepOtp = document.getElementById('step-otp');
    var stepPassword = document.getElementById('step-password');
    var stepSetupPassword = document.getElementById('step-setup-password');
    var stepSetupName = document.getElementById('step-setup-name');
    var indexInput = document.getElementById('index_number');
    var emailInput = document.getElementById('student_email');
    var phoneInput = document.getElementById('phone');
    var otpInput = document.getElementById('otp_code');
    var nameInput = document.getElementById('otp_name');
    var setupPasswordWrap = document.getElementById('phone-password-setup-wrap');
    var currentIndexNumber = '';
    var lastPhoneUsed = '';
    var requirePasswordSetup = false;
    var universalOtpConfigured = @json($universalOtpConfigured ?? false);

    function showStep(step) {
        stepIndex.classList.add('hidden');
        if (stepEmail) stepEmail.classList.add('hidden');
        stepPhone.classList.add('hidden');
        stepOtp.classList.add('hidden');
        if (stepPassword) stepPassword.classList.add('hidden');
        if (stepSetupPassword) stepSetupPassword.classList.add('hidden');
        if (stepSetupName) stepSetupName.classList.add('hidden');
        if (step === 'index') stepIndex.classList.remove('hidden');
        else if (step === 'email' && stepEmail) stepEmail.classList.remove('hidden');
        else if (step === 'phone') stepPhone.classList.remove('hidden');
        else if (step === 'password' && stepPassword) stepPassword.classList.remove('hidden');
        else if (step === 'setup_password' && stepSetupPassword) stepSetupPassword.classList.remove('hidden');
        else if (step === 'setup_name' && stepSetupName) stepSetupName.classList.remove('hidden');
        else if (step === 'otp') stepOtp.classList.remove('hidden');
    }

    function handleLoginStepData(data) {
        if (!data) return;
        if (data.index_number) currentIndexNumber = data.index_number;
        if (data.redirect) {
            window.location.href = data.redirect;
            return;
        }
        if ((data.step === 'email' || data.step === 'setup_email') && stepEmail) {
            document.getElementById('email-step-message').textContent = data.message || 'Enter your email address.';
            if (emailInput) emailInput.value = data.prefill_email || emailInput.value || '';
            showError('email-error', '');
            showStep('email');
        } else if (data.step === 'setup_password' && stepSetupPassword) {
            document.getElementById('setup-password-message').textContent = data.message || 'Create a password for your account.';
            showError('setup-password-error', '');
            var osp = document.getElementById('onboard_setup_password');
            var ospc = document.getElementById('onboard_setup_password_confirmation');
            if (osp) osp.value = '';
            if (ospc) ospc.value = '';
            showStep('setup_password');
        } else if (data.step === 'setup_name' && stepSetupName) {
            document.getElementById('setup-name-message').textContent = data.message || 'What name should we show on your account?';
            showError('setup-name-error', '');
            var sn = document.getElementById('setup_student_name');
            if (sn) sn.value = data.prefill_name || '';
            showStep('setup_name');
        } else if (data.step === 'password' && passwordLoginEnabled && stepPassword) {
            document.getElementById('password-step-message').textContent = data.message || 'Enter your password.';
            showError('password-error', '');
            var lp = document.getElementById('login_password');
            if (lp) lp.value = '';
            showStep('password');
        } else if (data.step === 'phone') {
            document.getElementById('phone-step-message').textContent = data.message || 'Enter your active phone number.';
            showStep('phone');
        } else if (data.step === 'otp') {
            applyOtpStepData(data);
        }
    }

    function isIndexNotFoundError(data, text) {
        if (data && data.error_code === 'index_not_found') return true;
        if (!text) return false;
        var lower = String(text).toLowerCase();
        return lower.indexOf('index number not found') !== -1
            || lower.indexOf('not on the class list') !== -1
            || lower.indexOf('class rep') !== -1;
    }

    function showError(elId, text, data) {
        var wrap = document.getElementById(elId);
        var textEl = document.getElementById(elId + '-text');
        if (!wrap || !textEl) return;
        textEl.textContent = text || '';
        wrap.classList.toggle('hidden', !text);
        var supportWrap = document.getElementById('login-error-support-wrap');
        var supportLink = document.getElementById('login-error-support');
        var indexGuidance = document.getElementById('login-error-index-guidance');
        var liveBtn = document.getElementById('login-error-live-support');
        if (elId === 'login-error') {
            var indexIssue = isIndexNotFoundError(data, text);
            if (indexGuidance) indexGuidance.classList.toggle('hidden', !indexIssue);
            if (supportWrap && supportLink) {
                if (text && !indexIssue) {
                    supportLink.dataset.supportHint = text;
                    supportLink.dataset.supportIndex = (indexInput && indexInput.value) ? indexInput.value.trim() : '';
                    supportWrap.classList.remove('hidden');
                } else {
                    delete supportLink.dataset.supportHint;
                    delete supportLink.dataset.supportIndex;
                    supportWrap.classList.add('hidden');
                }
            }
            if (liveBtn && text && !indexIssue) {
                liveBtn.onclick = function() {
                    if (window.QuizSnapLiveSupport) {
                        window.QuizSnapLiveSupport.open({
                            student_index: (indexInput && indexInput.value) ? indexInput.value.trim() : '',
                            page_url: window.location.pathname,
                            issue_category: 'login',
                            initial_message: 'Login issue: ' + text
                        });
                    }
                };
            }
        }
    }

    function setLoading(btn, loading) {
        if (!btn) return;
        btn.disabled = loading;
        btn.dataset.originalText = btn.dataset.originalText || btn.textContent;
        btn.textContent = loading ? 'Please wait…' : (btn.dataset.originalText || 'Continue');
    }

    function updateUniversalFallbackUi(data, forceShow) {
        var wrap = document.getElementById('otp-universal-fallback-wrap');
        var hint = document.getElementById('otp-universal-fallback-hint');
        if (!wrap || !hint) return;
        var available = universalOtpConfigured || !!(data && data.universal_fallback_available);
        var promote = forceShow || universalOtpConfigured || !!(data && data.show_universal_fallback);
        wrap.classList.toggle('hidden', !available || !promote);
        if (promote) {
            hint.textContent = (data && data.universal_fallback_message)
                || 'If SMS is unavailable, enter your institution login code below.';
        }
    }

    function updateEmailFallbackUi(data, forceShow) {
        if (!onboardingEmailOtpEnabled) return;
        var wrap = document.getElementById('otp-email-fallback-wrap');
        var hint = document.getElementById('otp-email-fallback-hint');
        if (!wrap) return;
        var available = !!(data && data.email_fallback_available);
        var promote = forceShow || !!(data && data.show_email_fallback) || smsResendCount >= 1;
        wrap.classList.toggle('hidden', !(available && promote));
        if (hint) hint.classList.toggle('hidden', !(available && promote));
        if (data && data.prefill_email) {
            var fe = document.getElementById('fallback_email');
            if (fe && !fe.value) fe.value = data.prefill_email;
        }
        if (data && data.otp_channel === 'email') {
            var daysEl = document.getElementById('otp-days-remaining');
            if (daysEl && data.expires_minutes) {
                daysEl.textContent = 'Email code expires in ' + data.expires_minutes + ' minutes.';
                daysEl.style.display = 'block';
            }
        }
    }

    function applyOtpStepData(data) {
        var otpMsg = document.getElementById('otp-step-message');
        if (otpMsg) otpMsg.textContent = data.message || 'Enter the 6-digit code sent to your phone.';
        if (data.can_resend) lastPhoneUsed = '__registered__';
        if (data.has_name && nameInput) nameInput.closest('div').style.display = 'none';
        var resendBtn = document.getElementById('btn-resend-otp');
        if (resendBtn) {
            resendBtn.disabled = data.can_resend === false;
            resendBtn.textContent = (data.can_resend === false && data.days_remaining != null)
                ? 'Resend available in ' + data.days_remaining + ' day(s)' : 'Resend code';
        }
        var daysEl = document.getElementById('otp-days-remaining');
        if (daysEl && data.otp_channel !== 'email') {
            if (data.days_remaining != null) {
                daysEl.textContent = 'Valid for ' + data.days_remaining + ' more day(s).';
                daysEl.style.display = 'block';
            } else if (data.otp_never_expires) {
                daysEl.textContent = 'This code does not expire until you receive a new one.';
                daysEl.style.display = 'block';
            }
        }
        updateEmailFallbackUi(data, false);
        updateUniversalFallbackUi(data, true);
        showStep('otp');
        initOtpBoxes();
    }

    document.getElementById('login-form').addEventListener('submit', function(e) {
        e.preventDefault();
        showError('login-error', '');
        var btn = this.querySelector('button[type="submit"]');
        var btnText = document.getElementById('btn-text');
        setLoading(btn, true);
        btnText.textContent = 'Verifying...';
        fetch('{{ route("student.verify.index") }}', {
            method: 'POST',
            credentials: 'same-origin',
            headers: jsonHeaders(csrf),
            body: JSON.stringify({ index_number: (indexInput && indexInput.value) ? indexInput.value.trim().toUpperCase() : '' })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            setLoading(btn, false);
            btnText.textContent = 'Continue';
            if (!data.success) {
                showError('login-error', data.message || 'Verification failed.', data);
                return;
            }
            if (data.redirect) {
                window.location.href = data.redirect;
                return;
            }
            currentIndexNumber = data.index_number || (indexInput && indexInput.value ? indexInput.value.trim().toUpperCase() : '');
            requirePasswordSetup = !!(data.require_password_setup && passwordLoginEnabled);
            if (setupPasswordWrap) {
                setupPasswordWrap.classList.toggle('hidden', !requirePasswordSetup);
                if (requirePasswordSetup) {
                    var sp = document.getElementById('setup_password');
                    var spc = document.getElementById('setup_password_confirmation');
                    if (sp) sp.value = '';
                    if (spc) spc.value = '';
                }
            }
            if (data.step === 'email' && stepEmail) {
                document.getElementById('email-step-message').textContent = data.message || 'Enter your email address.';
                if (emailInput) emailInput.value = data.prefill_email || '';
                showError('email-error', '');
                showStep('email');
            } else if (data.step === 'password' && passwordLoginEnabled && stepPassword) {
                var pmsg = document.getElementById('password-step-message');
                if (pmsg) pmsg.textContent = data.message || 'Enter your password.';
                showError('password-error', '');
                var lp = document.getElementById('login_password');
                if (lp) lp.value = '';
                showStep('password');
            } else if (data.step === 'phone') {
                var msgEl = document.getElementById('phone-step-message');
                if (msgEl) msgEl.textContent = data.message || 'Enter your active phone number to receive an SMS.';
                showStep('phone');
                if (phoneInput) {
                    phoneInput.value = (data.prefill_phone && requirePasswordSetup) ? data.prefill_phone : '';
                    phoneInput.readOnly = !!(data.prefill_phone && requirePasswordSetup);
                }
            } else if (data.step === 'otp') {
                applyOtpStepData(data);
            }
        })
        .catch(function() {
            setLoading(btn, false);
            btnText.textContent = 'Continue';
            showError('login-error', 'Network error. Please try again.');
        });
    });

    if (document.getElementById('btn-save-email')) {
        document.getElementById('btn-save-email').addEventListener('click', function() {
            var email = (emailInput && emailInput.value) ? emailInput.value.trim() : '';
            if (!email || email.indexOf('@') < 1) {
                showError('email-error', 'Please enter a valid email address.');
                return;
            }
            showError('email-error', '');
            setLoading(this, true);
            fetch('{{ route("student.account.save-email") }}', {
                method: 'POST',
                credentials: 'same-origin',
                headers: jsonHeaders(csrf),
                body: JSON.stringify({ index_number: currentIndexNumber, email: email })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                setLoading(document.getElementById('btn-save-email'), false);
                if (!data.success) {
                    showError('email-error', data.message || 'Could not save email.');
                    return;
                }
                requirePasswordSetup = !!(data.require_password_setup && passwordLoginEnabled);
                if (data.step === 'phone') {
                    document.getElementById('phone-step-message').textContent = data.message || '';
                    showStep('phone');
                } else if (data.step === 'password' && stepPassword) {
                    showStep('password');
                } else if (data.step === 'otp') {
                    applyOtpStepData(data);
                }
            })
            .catch(function() {
                setLoading(document.getElementById('btn-save-email'), false);
                showError('email-error', 'Network error.');
            });
        });
    }
    if (document.getElementById('btn-back-email-to-index')) {
        document.getElementById('btn-back-email-to-index').addEventListener('click', function() {
            showStep('index');
            showError('email-error', '');
        });
    }

    document.getElementById('btn-back-to-index').addEventListener('click', function() {
        showStep('index');
        showError('phone-error', '');
        requirePasswordSetup = false;
        if (setupPasswordWrap) setupPasswordWrap.classList.add('hidden');
        if (phoneInput) phoneInput.readOnly = false;
        var sendBtn = document.getElementById('btn-send-otp');
        if (sendBtn) { sendBtn.dataset.originalText = 'Send code'; sendBtn.textContent = 'Send code'; }
    });

    function showPasswordError(text) {
        var wrap = document.getElementById('password-error');
        var textEl = document.getElementById('password-error-text');
        if (!wrap || !textEl) return;
        textEl.textContent = text || '';
        wrap.classList.toggle('hidden', !text);
    }

    if (passwordLoginEnabled && document.getElementById('btn-verify-password')) {
        document.getElementById('btn-verify-password').addEventListener('click', function() {
            var pw = document.getElementById('login_password');
            var v = pw && pw.value ? pw.value : '';
            if (!v) { showPasswordError('Please enter your password.'); return; }
            showPasswordError('');
            setLoading(this, true);
            var verifyPwUrl = '{{ route("student.account.verify-password") }}';
            function doPw() {
                return fetch(verifyPwUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: jsonHeaders(csrf),
                    body: JSON.stringify({ index_number: currentIndexNumber, password: v })
                });
            }
            ensureFreshCsrf().then(function() { return doPw(); })
            .then(function(r) {
                if (r.status === 419) return ensureFreshCsrf().then(function() { return doPw(); });
                return r;
            })
            .then(function(r) { return parseJsonResponse(r); })
            .then(function(data) {
                setLoading(document.getElementById('btn-verify-password'), false);
                if (!data.success) { showPasswordError(data.message || 'Sign-in failed.'); return; }
                if (data.redirect) window.location.href = data.redirect;
            })
            .catch(function(err) {
                setLoading(document.getElementById('btn-verify-password'), false);
                showPasswordError((err && err.message) ? err.message : 'Network error. Please try again.');
            });
        });
    }
    if (passwordLoginEnabled && document.getElementById('btn-password-use-sms')) {
        document.getElementById('btn-password-use-sms').addEventListener('click', function() {
            if (!currentIndexNumber) return;
            showPasswordError('');
            setLoading(this, true);
            ensureFreshCsrf().then(function() {
                return fetch('{{ route("student.account.request-otp-login") }}', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: jsonHeaders(csrf),
                    body: JSON.stringify({ index_number: currentIndexNumber })
                });
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                setLoading(document.getElementById('btn-password-use-sms'), false);
                if (!data.success) { showPasswordError(data.message || 'Could not send SMS.'); return; }
                var otpMsg = document.getElementById('otp-step-message');
                if (otpMsg) otpMsg.textContent = data.message || 'Enter the code from your phone.';
                if (data.has_name && nameInput) nameInput.closest('div').style.display = 'none';
                showStep('otp');
                initOtpBoxes();
            })
            .catch(function() {
                setLoading(document.getElementById('btn-password-use-sms'), false);
                showPasswordError('Network error.');
            });
        });
    }
    if (passwordLoginEnabled && document.getElementById('btn-back-password-to-index')) {
        document.getElementById('btn-back-password-to-index').addEventListener('click', function() {
            showStep('index');
            showPasswordError('');
        });
    }

    document.getElementById('btn-send-otp').addEventListener('click', function() {
        var phone = (phoneInput && phoneInput.value) ? phoneInput.value.trim() : '';
        if (!phone) {
            showError('phone-error', 'Please enter your phone number.');
            return;
        }
        showError('phone-error', '');
        setLoading(this, true);
        var sendBody = { index_number: currentIndexNumber, phone: phone };
        if (requirePasswordSetup) {
            var sp = document.getElementById('setup_password');
            var spc = document.getElementById('setup_password_confirmation');
            sendBody.new_password = sp ? sp.value : '';
            sendBody.new_password_confirmation = spc ? spc.value : '';
            if (!sendBody.new_password || sendBody.new_password.length < studentPasswordMinLength) {
                showError('phone-error', 'Choose a password of at least ' + studentPasswordMinLength + ' characters.');
                setLoading(document.getElementById('btn-send-otp'), false);
                return;
            }
            if (sendBody.new_password !== sendBody.new_password_confirmation) {
                showError('phone-error', 'Password confirmation does not match.');
                setLoading(document.getElementById('btn-send-otp'), false);
                return;
            }
        }
        ensureFreshCsrf().then(function() {
            return fetch('{{ route("student.account.send-otp") }}', {
                method: 'POST',
                credentials: 'same-origin',
                headers: jsonHeaders(csrf),
                body: JSON.stringify(sendBody)
            });
        })
        .then(function(r) { return parseJsonResponse(r); })
        .then(function(data) {
            setLoading(document.getElementById('btn-send-otp'), false);
            if (!data.success) {
                showError('phone-error', data.message || 'We couldn\'t send the code. Please try again.');
                updateEmailFallbackUi(data, !!(data && data.show_email_fallback));
                updateUniversalFallbackUi(data, true);
                if (data.email_fallback_available || data.universal_fallback_available) {
                    var otpMsg = data.show_universal_fallback && data.universal_fallback_message
                        ? data.universal_fallback_message
                        : 'SMS could not be sent. Try email verification below.';
                    applyOtpStepData(Object.assign({ step: 'otp', message: otpMsg, can_resend: true }, data));
                }
                return;
            }
            lastPhoneUsed = phone;
            applyOtpStepData(data);
            showError('otp-error', '');
        })
        .catch(function(err) {
            setLoading(document.getElementById('btn-send-otp'), false);
            showError('phone-error', (err && err.message) ? err.message : 'Network error. Please try again.');
        });
    });

    document.getElementById('btn-back-to-phone').addEventListener('click', function() {
        showStep('phone');
        showError('otp-error', '');
    });

    document.getElementById('btn-resend-otp').addEventListener('click', function() {
        if (!currentIndexNumber) {
            showError('otp-error', 'Go back and enter your index number, then try again.');
            return;
        }
        var resendBtn = document.getElementById('btn-resend-otp');
        if (resendBtn.disabled) return;
        resendBtn.disabled = true;
        resendBtn.textContent = 'Sending…';
        showError('otp-error', '');
        var payload = { index_number: currentIndexNumber };
        if (lastPhoneUsed && lastPhoneUsed !== '__registered__') {
            payload.phone = lastPhoneUsed;
        }
        ensureFreshCsrf().then(function() {
            return fetch('{{ route("student.account.send-otp") }}', {
                method: 'POST',
                credentials: 'same-origin',
                headers: jsonHeaders(csrf),
                body: JSON.stringify(payload)
            });
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                smsResendCount += 1;
                document.getElementById('otp-step-message').textContent = data.message || 'A new code has been sent. Enter it above.';
                updateEmailFallbackUi(data, smsResendCount >= 1);
                updateUniversalFallbackUi(data, !!(data && data.show_universal_fallback));
                resendBtn.disabled = true;
                resendBtn.textContent = 'Wait ~1 min to resend';
                setTimeout(function() {
                    resendBtn.disabled = false;
                    resendBtn.textContent = 'Resend code';
                }, 65000);
                var daysEl = document.getElementById('otp-days-remaining');
                if (daysEl) {
                    if (data.days_remaining != null) {
                        daysEl.textContent = 'Valid for ' + data.days_remaining + ' more day(s).';
                        daysEl.style.display = 'block';
                    } else if (data.otp_never_expires) {
                        daysEl.textContent = 'This code does not expire until you receive a new one.';
                        daysEl.style.display = 'block';
                    }
                }
                initOtpBoxes();
            } else {
                resendBtn.disabled = data.can_resend === false;
                resendBtn.textContent = (data.can_resend === false && data.days_remaining != null)
                    ? 'Resend available in ' + data.days_remaining + ' day(s)' : 'Resend code';
                showError('otp-error', data.message || 'Could not resend. Please try again.');
                updateEmailFallbackUi(data, !!(data && data.show_email_fallback));
                updateUniversalFallbackUi(data, true);
            }
        })
        .catch(function() {
            resendBtn.disabled = false;
            resendBtn.textContent = 'Resend code';
            showError('otp-error', 'Network error. Please try again.');
        });
    });

    function getOtpCode() {
        var boxes = document.querySelectorAll('.otp-digit');
        var code = '';
        for (var i = 0; i < (boxes.length || 6); i++) {
            if (boxes[i]) code += (boxes[i].value || '').trim();
        }
        return code;
    }
    function setOtpHidden(val) {
        var h = document.getElementById('otp_code');
        if (h) h.value = val;
    }
    function initOtpBoxes() {
        var boxes = document.querySelectorAll('.otp-digit');
        setOtpHidden('');
        boxes.forEach(function(b) { b.value = ''; });
        if (boxes[0]) boxes[0].focus();

        function syncAndMaybeSubmit() {
            var code = getOtpCode();
            setOtpHidden(code);
            if (code.length === 6) {
                var btn = document.getElementById('btn-verify-otp');
                if (btn && !btn.disabled) btn.click();
            }
        }
        boxes.forEach(function(box, i) {
            box.onkeydown = function(e) {
                if (/^[0-9]$/.test(e.key)) {
                    e.preventDefault();
                    this.value = e.key;
                    if (boxes[i + 1]) boxes[i + 1].focus();
                    else this.blur();
                    syncAndMaybeSubmit();
                    return;
                }
                if (e.key === 'Backspace' && !this.value && boxes[i - 1]) {
                    e.preventDefault();
                    boxes[i - 1].focus();
                }
            };
            box.oninput = function() {
                var v = this.value.replace(/\D/g, '').slice(0, 1);
                this.value = v;
                if (v && boxes[i + 1]) boxes[i + 1].focus();
                syncAndMaybeSubmit();
            };
            box.onpaste = function(e) {
                e.preventDefault();
                var pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
                for (var j = 0; j < pasted.length && j < boxes.length; j++) {
                    boxes[j].value = pasted[j];
                }
                if (pasted.length > 0 && boxes[pasted.length - 1]) boxes[pasted.length - 1].focus();
                syncAndMaybeSubmit();
            };
        });
    }

    document.getElementById('btn-verify-otp').addEventListener('click', function() {
        var code = getOtpCode();
        if (!code || code.length !== 6) {
            showError('otp-error', 'Please enter the 6-digit code.');
            return;
        }
        showError('otp-error', '');
        setLoading(this, true);
        var payload = { index_number: currentIndexNumber, code: code };
        if (nameInput && nameInput.value.trim()) payload.student_name = nameInput.value.trim();
        var verifyUrl = '{{ route("student.account.verify-otp") }}';
        function doVerify() {
            return fetch(verifyUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: jsonHeaders(csrf),
                body: JSON.stringify(payload)
            });
        }
        ensureFreshCsrf().then(function() { return doVerify(); })
        .then(function(r) {
            if (r.status === 419) {
                return ensureFreshCsrf().then(function() { return doVerify(); });
            }
            return r;
        })
        .then(function(r) { return parseJsonResponse(r); })
        .then(function(data) {
            setLoading(document.getElementById('btn-verify-otp'), false);
            if (!data.success) {
                showError('otp-error', data.message || 'Invalid or expired code.');
                updateEmailFallbackUi(data, !!(data && data.show_email_fallback));
                updateUniversalFallbackUi(data, true);
                return;
            }
            handleLoginStepData(data);
        })
        .catch(function(err) {
            setLoading(document.getElementById('btn-verify-otp'), false);
            showError('otp-error', (err && err.message) ? err.message : 'Network error. Please try again.');
        });
    });

    if (document.getElementById('btn-setup-password')) {
        document.getElementById('btn-setup-password').addEventListener('click', function() {
            var password = (document.getElementById('onboard_setup_password') || {}).value || '';
            var confirmation = (document.getElementById('onboard_setup_password_confirmation') || {}).value || '';
            if (!password || password.length < studentPasswordMinLength) {
                showError('setup-password-error', 'Password must be at least ' + studentPasswordMinLength + ' characters.');
                return;
            }
            if (password !== confirmation) {
                showError('setup-password-error', 'Password confirmation does not match.');
                return;
            }
            showError('setup-password-error', '');
            setLoading(this, true);
            ensureFreshCsrf().then(function() {
                return fetch('{{ route("student.account.setup-password") }}', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: jsonHeaders(csrf),
                    body: JSON.stringify({
                        index_number: currentIndexNumber,
                        password: password,
                        password_confirmation: confirmation
                    })
                });
            })
            .then(function(r) { return parseJsonResponse(r); })
            .then(function(data) {
                setLoading(document.getElementById('btn-setup-password'), false);
                if (!data.success) {
                    showError('setup-password-error', data.message || 'Could not save password.');
                    return;
                }
                handleLoginStepData(data);
            })
            .catch(function(err) {
                setLoading(document.getElementById('btn-setup-password'), false);
                showError('setup-password-error', (err && err.message) ? err.message : 'Network error. Please try again.');
            });
        });
    }

    if (document.getElementById('btn-setup-name')) {
        document.getElementById('btn-setup-name').addEventListener('click', function() {
            var nameEl = document.getElementById('setup_student_name');
            var name = nameEl && nameEl.value ? nameEl.value.trim() : '';
            if (!name) {
                showError('setup-name-error', 'Please enter your name.');
                return;
            }
            showError('setup-name-error', '');
            setLoading(this, true);
            ensureFreshCsrf().then(function() {
                return fetch('{{ route("student.account.setup-name") }}', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: jsonHeaders(csrf),
                    body: JSON.stringify({ index_number: currentIndexNumber, student_name: name })
                });
            })
            .then(function(r) { return parseJsonResponse(r); })
            .then(function(data) {
                setLoading(document.getElementById('btn-setup-name'), false);
                if (!data.success) {
                    showError('setup-name-error', data.message || 'Could not save name.');
                    return;
                }
                handleLoginStepData(data);
            })
            .catch(function(err) {
                setLoading(document.getElementById('btn-setup-name'), false);
                showError('setup-name-error', (err && err.message) ? err.message : 'Network error. Please try again.');
            });
        });
    }

    var btnShowEmailFallback = document.getElementById('btn-show-email-fallback');
    if (btnShowEmailFallback) {
        btnShowEmailFallback.addEventListener('click', function() {
            var fields = document.getElementById('otp-email-fallback-fields');
            if (fields) fields.classList.remove('hidden');
            this.classList.add('hidden');
            var fe = document.getElementById('fallback_email');
            if (fe) fe.focus();
        });
    }
    var btnSendEmailOtp = document.getElementById('btn-send-email-otp');
    if (btnSendEmailOtp) {
        btnSendEmailOtp.addEventListener('click', function() {
            var email = (document.getElementById('fallback_email') && document.getElementById('fallback_email').value) ? document.getElementById('fallback_email').value.trim() : '';
            if (!email) {
                showError('otp-error', 'Enter your email address.');
                return;
            }
            if (!currentIndexNumber) {
                showError('otp-error', 'Go back and enter your index number first.');
                return;
            }
            showError('otp-error', '');
            setLoading(this, true);
            ensureFreshCsrf().then(function() {
                return fetch('{{ route("student.account.send-onboarding-email-otp") }}', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: jsonHeaders(csrf),
                    body: JSON.stringify({ index_number: currentIndexNumber, email: email })
                });
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                setLoading(btnSendEmailOtp, false);
                if (!data.success) {
                    showError('otp-error', data.message || 'Could not send email code.');
                    updateEmailFallbackUi(data, !!(data && data.show_email_fallback));
                    return;
                }
                applyOtpStepData(data);
            })
            .catch(function() {
                setLoading(btnSendEmailOtp, false);
                showError('otp-error', 'Network error. Please try again.');
            });
        });
    }
})();
</script>
@endpush
@endsection
