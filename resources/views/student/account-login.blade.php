@extends('layouts.app')

@section('title', 'Student Login')
@section('body_class', 'bg-offwhite')

@section('content')
<div class="min-h-[100dvh] min-h-screen flex items-center justify-center px-4 py-8">
    <div class="max-w-md w-full">
        <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
            <div class="mb-5 flex justify-center">
                @include('partials.brand-logo', ['size' => 'md', 'variant' => 'default'])
            </div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Student login</h1>
            <p class="text-gray-600 text-sm mb-6">@if(!empty($password_login_enabled))Enter your index number. First-time sign-in: verify your phone by SMS, then create a password, add your name and email. After that, sign in with your password only.@else Use your index number and phone to sign in. We'll send a one-time code by SMS. Keep this page open while you complete the steps.@endif</p>

            {{-- Step 1: Index number (primary flow) --}}
            <div id="step-index" class="space-y-4">
                <div>
                    <label for="index_number" class="block text-sm font-medium text-gray-700 mb-1">Index number</label>
                    <input type="text" id="index_number" name="index_number" required placeholder="e.g. BC/ITS/24/047" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-primary-500 focus:border-primary-500" style="text-transform: uppercase;" autocomplete="off">
                </div>
                <div id="index-error" class="hidden">
                    <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-800" id="index-error-text"></div>
                    <p id="index-error-support-wrap" class="hidden mt-2 text-sm text-gray-600">
                        <a id="index-error-support" href="#" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline font-medium">Get in touch</a>
                    </p>
                </div>
                <button type="button" id="btn-index" class="w-full py-2.5 px-4 text-sm font-semibold rounded-lg text-white bg-primary-600 hover:bg-primary-700 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">Continue</button>
            </div>

            {{-- Step 1b: Email (final onboarding step) --}}
            <div id="step-email" class="space-y-4 hidden">
                <p class="text-sm text-gray-600" id="email-step-message">Enter your email address for account recovery and notifications.</p>
                <div>
                    <label for="student_email" class="block text-sm font-medium text-gray-700 mb-1">Email address</label>
                    <input type="email" id="student_email" name="email" placeholder="you@example.com" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-primary-500 focus:border-primary-500" autocomplete="email">
                </div>
                <div id="email-error" class="hidden">
                    <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-800" id="email-error-text"></div>
                </div>
                <button type="button" id="btn-save-email" class="w-full py-2.5 px-4 text-sm font-semibold rounded-lg text-white bg-primary-600 hover:bg-primary-700 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">Continue</button>
                <button type="button" id="btn-back-email-to-index" class="w-full py-2 px-4 text-sm font-medium rounded-lg text-gray-700 bg-gray-200 hover:bg-gray-300">← Back</button>
            </div>

            @if(!empty($password_login_enabled))
            {{-- Password sign-in (index already verified) --}}
            <div id="step-password" class="space-y-4 hidden">
                <p class="text-sm text-gray-600" id="password-step-message">Enter your password.</p>
                <div>
                    <label for="login_password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" id="login_password" name="login_password" autocomplete="current-password" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>
                <div id="password-error" class="hidden">
                    <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-800" id="password-error-text"></div>
                </div>
                <button type="button" id="btn-verify-password" class="w-full py-2.5 px-4 text-sm font-semibold rounded-lg text-white bg-primary-600 hover:bg-primary-700 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">Sign in</button>
                @if(!empty($password_reset_enabled))
                <p class="text-center text-sm">
                    <a href="{{ route('student.password.forgot') }}" class="text-primary-600 hover:underline font-medium">Forgot password?</a>
                </p>
                @endif
                @if(!empty($otp_return_login_enabled))
                <button type="button" id="btn-password-use-sms" class="w-full py-2 px-4 text-sm font-medium rounded-lg text-primary-700 bg-primary-50 border border-primary-200 hover:bg-primary-100">Get a code by SMS instead</button>
                @endif
                <button type="button" id="btn-back-password-to-index" class="w-full py-2 px-4 text-sm font-medium rounded-lg text-gray-700 bg-gray-200 hover:bg-gray-300">← Back</button>
            </div>
            @endif

            {{-- Phone verification (SMS OTP) --}}
            <div id="step-phone" class="space-y-4 hidden">
                <p class="text-sm text-gray-600" id="phone-step-message">Enter your active phone number. We'll send a one-time SMS code.</p>
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone number</label>
                    <input type="tel" id="phone" name="phone" placeholder="233XXXXXXXXX" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-primary-500 focus:border-primary-500" autocomplete="tel">
                </div>
                <div id="phone-error" class="hidden">
                    <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-800" id="phone-error-text"></div>
                </div>
                <button type="button" id="btn-send-otp" class="w-full py-2.5 px-4 text-sm font-semibold rounded-lg text-white bg-primary-600 hover:bg-primary-700 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">Send code</button>
                <button type="button" id="btn-back-to-index" class="w-full py-2 px-4 text-sm font-medium rounded-lg text-gray-700 bg-gray-200 hover:bg-gray-300">← Back</button>
            </div>

            {{-- Onboarding: create password (after OTP) --}}
            @if(!empty($password_login_enabled))
            <div id="step-setup-password" class="space-y-4 hidden">
                <p class="text-sm text-gray-600" id="setup-password-message">Phone verified. Create a password for your account.</p>
                <div>
                    <label for="setup_password" class="block text-sm font-medium text-gray-700 mb-1">Password (min {{ \App\Models\Student::PASSWORD_MIN_LENGTH }} characters)</label>
                    <input type="password" id="setup_password" autocomplete="new-password" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>
                <div>
                    <label for="setup_password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm password</label>
                    <input type="password" id="setup_password_confirmation" autocomplete="new-password" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>
                <div id="setup-password-error" class="hidden">
                    <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-800" id="setup-password-error-text"></div>
                </div>
                <button type="button" id="btn-setup-password" class="w-full py-2.5 px-4 text-sm font-semibold rounded-lg text-white bg-primary-600 hover:bg-primary-700">Continue</button>
            </div>

            {{-- Onboarding: display name --}}
            <div id="step-setup-name" class="space-y-4 hidden">
                <p class="text-sm text-gray-600" id="setup-name-message">What name should we show on your account?</p>
                <div>
                    <label for="setup_student_name" class="block text-sm font-medium text-gray-700 mb-1">Your name</label>
                    <input type="text" id="setup_student_name" placeholder="Full name" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-primary-500 focus:border-primary-500" autocomplete="name" style="text-transform: capitalize;">
                </div>
                <div id="setup-name-error" class="hidden">
                    <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-800" id="setup-name-error-text"></div>
                </div>
                <button type="button" id="btn-setup-name" class="w-full py-2.5 px-4 text-sm font-semibold rounded-lg text-white bg-primary-600 hover:bg-primary-700">Continue</button>
            </div>
            @endif

            {{-- OTP verification --}}
            <div id="step-otp" class="space-y-4 hidden">
                <p class="text-sm text-gray-600" id="otp-step-message">Enter the 6-digit code sent to your phone.</p>
                <div id="otp-code-fields" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Code</label>
                        <div class="flex justify-center gap-2" id="otp-boxes-wrap">
                            @for($i = 0; $i < 6; $i++)
                            <input type="text" inputmode="numeric" pattern="[0-9]" maxlength="1" data-otp-index="{{ $i }}" autocomplete="one-time-code"
                                class="w-11 h-12 text-center text-xl font-semibold border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 otp-digit">
                            @endfor
                        </div>
                        <input type="hidden" id="otp_code" name="code" value="">
                    </div>
                    <div id="otp-error" class="hidden">
                        <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-800" id="otp-error-text"></div>
                    </div>
                    <button type="button" id="btn-verify-otp" class="w-full py-2.5 px-4 text-sm font-semibold rounded-lg text-white bg-primary-600 hover:bg-primary-700 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">Verify code</button>
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
            </div>
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
    function postJson(url, payload, timeoutMs) {
        var controller = new AbortController();
        var timer = setTimeout(function() { controller.abort(); }, timeoutMs || 12000);
        function doFetch(token) {
            return fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: jsonHeaders(token),
                body: JSON.stringify(payload),
                signal: controller.signal
            });
        }
        return ensureFreshCsrf()
            .then(function() { return doFetch(csrf); })
            .then(function(r) {
                if (r.status === 419) {
                    return ensureFreshCsrf().then(function(t) { return doFetch(t); });
                }
                return r;
            })
            .then(function(r) {
                clearTimeout(timer);
                return r.json().then(function(data) {
                    return { ok: r.ok, status: r.status, data: data };
                });
            })
            .catch(function(err) {
                clearTimeout(timer);
                throw err;
            });
    }
    var passwordLoginEnabled = @json(!empty($password_login_enabled));
    var onboardingEmailOtpEnabled = @json(!empty($onboarding_email_otp_enabled) && !empty($mail_configured));
    var otpChannel = 'sms';
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
    var currentIndexNumber = '';
    var lastPhoneUsed = '';

    function showStep(step) {
        stepIndex.classList.add('hidden');
        if (stepEmail) stepEmail.classList.add('hidden');
        stepPhone.classList.add('hidden');
        stepOtp.classList.add('hidden');
        if (stepPassword) stepPassword.classList.add('hidden');
        if (stepSetupPassword) stepSetupPassword.classList.add('hidden');
        if (stepSetupName) stepSetupName.classList.add('hidden');
        if (step === 'index') stepIndex.classList.remove('hidden');
        else if ((step === 'email' || step === 'setup_email') && stepEmail) stepEmail.classList.remove('hidden');
        else if (step === 'phone') stepPhone.classList.remove('hidden');
        else if (step === 'password' && stepPassword) stepPassword.classList.remove('hidden');
        else if (step === 'setup_password' && stepSetupPassword) stepSetupPassword.classList.remove('hidden');
        else if (step === 'setup_name' && stepSetupName) stepSetupName.classList.remove('hidden');
        else if (step === 'otp') {
            stepOtp.classList.remove('hidden');
            initOtpBoxes();
        }
    }

    function redirectIfReady(data) {
        if (data && data.redirect) {
            window.location.href = data.redirect;
            return true;
        }
        return false;
    }

    var whatsappNumber = '233552477942';
    function supportMessage(errorText, indexNumber) {
        var msg = 'Hi, I\'m having trouble with QuizSnap login. I got this message: ' + (errorText || '') + '.';
        if (indexNumber) msg += ' My index number: ' + indexNumber + '.';
        msg += ' Can you help?';
        return encodeURIComponent(msg);
    }
    function showError(elId, text) {
        var wrap = document.getElementById(elId);
        var textEl = document.getElementById(elId + '-text');
        if (!wrap || !textEl) return;
        textEl.textContent = text || '';
        wrap.classList.toggle('hidden', !text);
        var supportWrap = document.getElementById('index-error-support-wrap');
        var supportLink = document.getElementById('index-error-support');
        if (supportWrap && supportLink && elId === 'index-error') {
            if (text) {
                supportLink.href = 'https://wa.me/' + whatsappNumber + '?text=' + supportMessage(text, (indexInput && indexInput.value) ? indexInput.value.trim() : '');
                supportWrap.classList.remove('hidden');
            } else {
                supportWrap.classList.add('hidden');
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
        var available = !!(data && data.universal_fallback_available);
        var promote = forceShow || !!(data && data.show_universal_fallback);
        wrap.classList.toggle('hidden', !available || !promote);
        if (promote && data && data.universal_fallback_message) {
            hint.textContent = data.universal_fallback_message;
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
            otpChannel = 'email';
            var daysEl = document.getElementById('otp-days-remaining');
            if (daysEl && data.expires_minutes) {
                daysEl.textContent = 'Email code expires in ' + data.expires_minutes + ' minutes.';
                daysEl.style.display = 'block';
            }
        }
    }

    function handleLoginStepData(data, indexFallback) {
        currentIndexNumber = data.index_number || indexFallback || currentIndexNumber;
        if (redirectIfReady(data)) return;

        if ((data.step === 'email' || data.step === 'setup_email') && stepEmail) {
            document.getElementById('email-step-message').textContent = data.message || 'Enter your email address.';
            if (emailInput) emailInput.value = data.prefill_email || emailInput.value || '';
            showError('email-error', '');
            showStep('setup_email');
        } else if (data.step === 'setup_password' && stepSetupPassword) {
            document.getElementById('setup-password-message').textContent = data.message || 'Create a password for your account.';
            showError('setup-password-error', '');
            var sp = document.getElementById('setup_password');
            var spc = document.getElementById('setup_password_confirmation');
            if (sp) sp.value = '';
            if (spc) spc.value = '';
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
            document.getElementById('phone-step-message').textContent = data.message || 'Enter your active phone number. We will send a one-time SMS code.';
            showStep('phone');
            if (phoneInput) {
                phoneInput.value = data.prefill_phone || '';
                phoneInput.readOnly = false;
            }
        } else if (data.step === 'otp') {
            document.getElementById('otp-step-message').textContent = data.message || 'Enter the 6-digit code sent to your phone.';
            if (data.can_resend) lastPhoneUsed = '__registered__';
            var resendBtn = document.getElementById('btn-resend-otp');
            if (resendBtn) {
                resendBtn.disabled = data.can_resend === false;
                resendBtn.textContent = (data.can_resend === false && data.days_remaining != null)
                    ? 'Resend available in ' + data.days_remaining + ' day(s)' : 'Resend code';
            }
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
            updateEmailFallbackUi(data, false);
            updateUniversalFallbackUi(data, !!(data && data.show_universal_fallback));
            showStep('otp');
        }
    }

    document.getElementById('btn-index').addEventListener('click', function() {
        var index = (indexInput && indexInput.value) ? indexInput.value.trim().toUpperCase() : '';
        if (!index) {
            showError('index-error', 'Please enter your index number.');
            return;
        }
        showError('index-error', '');
        setLoading(this, true);
        postJson('{{ route("student.account.verify-index") }}', { index_number: index })
        .then(function(result) {
            setLoading(document.getElementById('btn-index'), false);
            var data = result.data;
            if (!data || !data.success) {
                showError('index-error', (data && data.message) || 'Verification failed. Please try again.');
                var btnIndex = document.getElementById('btn-index');
                if (btnIndex) { btnIndex.dataset.originalText = 'Try again'; btnIndex.textContent = 'Try again'; }
                return;
            }
            var btnIndex = document.getElementById('btn-index');
            if (btnIndex) btnIndex.dataset.originalText = 'Continue';
            handleLoginStepData(data, index);
        })
        .catch(function(err) {
            setLoading(document.getElementById('btn-index'), false);
            var msg = (err && err.name === 'AbortError')
                ? 'Request timed out. Please try again.'
                : 'Network error. Please try again.';
            showError('index-error', msg);
            var btnIndex = document.getElementById('btn-index');
            if (btnIndex) { btnIndex.dataset.originalText = 'Try again'; btnIndex.textContent = 'Try again'; }
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
                if (redirectIfReady(data)) return;
                handleLoginStepData(data, currentIndexNumber);
            })
            .catch(function() {
                setLoading(document.getElementById('btn-save-email'), false);
                showError('email-error', 'Network error. Please try again.');
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
        if (phoneInput) phoneInput.readOnly = false;
        var sendBtn = document.getElementById('btn-send-otp');
        if (sendBtn) { sendBtn.dataset.originalText = 'Send code'; sendBtn.textContent = 'Send code'; }
    });

    if (passwordLoginEnabled && document.getElementById('btn-verify-password')) {
        document.getElementById('btn-verify-password').addEventListener('click', function() {
            var pw = document.getElementById('login_password');
            var v = pw && pw.value ? pw.value : '';
            if (!v) {
                showError('password-error', 'Please enter your password.');
                return;
            }
            showError('password-error', '');
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
            .then(function(r) { return r.json(); })
            .then(function(data) {
                setLoading(document.getElementById('btn-verify-password'), false);
                if (!data.success) {
                    showError('password-error', data.message || 'Sign-in failed.');
                    return;
                }
                if (data.redirect) window.location.href = data.redirect;
            })
            .catch(function() {
                setLoading(document.getElementById('btn-verify-password'), false);
                showError('password-error', 'Network error. Please try again.');
            });
        });
    }
    if (passwordLoginEnabled && document.getElementById('btn-password-use-sms')) {
        document.getElementById('btn-password-use-sms').addEventListener('click', function() {
            if (!currentIndexNumber) return;
            showError('password-error', '');
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
                if (!data.success) {
                    showError('password-error', data.message || 'Could not send SMS.');
                    return;
                }
                document.getElementById('otp-step-message').textContent = data.message || 'Enter the code from your phone.';
                showStep('otp');
            })
            .catch(function() {
                setLoading(document.getElementById('btn-password-use-sms'), false);
                showError('password-error', 'Network error.');
            });
        });
    }
    if (passwordLoginEnabled && document.getElementById('btn-back-password-to-index')) {
        document.getElementById('btn-back-password-to-index').addEventListener('click', function() {
            showStep('index');
            showError('password-error', '');
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
        this.dataset.originalText = this.textContent;
        var sendBody = { index_number: currentIndexNumber, phone: phone };
        ensureFreshCsrf().then(function() {
            return fetch('{{ route("student.account.send-otp") }}', {
                method: 'POST',
                credentials: 'same-origin',
                headers: jsonHeaders(csrf),
                body: JSON.stringify(sendBody)
            });
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            setLoading(document.getElementById('btn-send-otp'), false);
            if (!data.success) {
                showError('phone-error', data.message || 'We couldn\'t send the code. Please try again.');
                updateEmailFallbackUi(data, !!(data && data.show_email_fallback));
                updateUniversalFallbackUi(data, true);
                if (data.email_fallback_available || data.universal_fallback_available) {
                    if (data.show_universal_fallback && data.universal_fallback_message) {
                        document.getElementById('otp-step-message').textContent = data.universal_fallback_message;
                    }
                    showStep('otp');
                }
                var sendBtn = document.getElementById('btn-send-otp');
                if (sendBtn) { sendBtn.dataset.originalText = 'Try again'; sendBtn.textContent = 'Try again'; }
                return;
            }
            lastPhoneUsed = phone;
            otpChannel = data.otp_channel || 'sms';
            document.getElementById('otp-step-message').textContent = data.message || 'Enter the 6-digit code sent to your number.';
            showStep('otp');
            updateEmailFallbackUi(data, false);
            updateUniversalFallbackUi(data, false);
            showError('otp-error', '');
        })
        .catch(function() {
            setLoading(document.getElementById('btn-send-otp'), false);
            showError('phone-error', 'Network error. Please try again.');
            var sendBtn = document.getElementById('btn-send-otp');
            if (sendBtn) { sendBtn.dataset.originalText = 'Try again'; sendBtn.textContent = 'Try again'; }
        });
    });

    document.getElementById('btn-back-to-phone').addEventListener('click', function() {
        showStep('phone');
        showError('otp-error', '');
        var sendBtn = document.getElementById('btn-send-otp');
        if (sendBtn) { sendBtn.dataset.originalText = 'Send code'; sendBtn.textContent = 'Send code'; }
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
                otpChannel = data.otp_channel || 'sms';
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
        this.dataset.originalText = this.textContent;
        var payload = { index_number: currentIndexNumber, code: code };
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
        .then(function(r) { return r.json(); })
        .then(function(data) {
            setLoading(document.getElementById('btn-verify-otp'), false);
            if (!data.success) {
                showError('otp-error', data.message || 'Invalid or expired code.');
                updateEmailFallbackUi(data, !!(data && data.show_email_fallback));
                updateUniversalFallbackUi(data, true);
                return;
            }
            if (redirectIfReady(data)) return;
            handleLoginStepData(data, currentIndexNumber);
        })
        .catch(function() {
            setLoading(document.getElementById('btn-verify-otp'), false);
            showError('otp-error', 'Network error. Please try again.');
        });
    });

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
                handleLoginStepData(data, currentIndexNumber);
                initOtpBoxes();
            })
            .catch(function() {
                setLoading(btnSendEmailOtp, false);
                showError('otp-error', 'Network error. Please try again.');
            });
        });
    }

    if (document.getElementById('btn-setup-password')) {
        document.getElementById('btn-setup-password').addEventListener('click', function() {
            var sp = document.getElementById('setup_password');
            var spc = document.getElementById('setup_password_confirmation');
            var password = sp ? sp.value : '';
            var confirmation = spc ? spc.value : '';
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
            .then(function(r) { return r.json(); })
            .then(function(data) {
                setLoading(document.getElementById('btn-setup-password'), false);
                if (!data.success) {
                    showError('setup-password-error', data.message || 'Could not save password.');
                    return;
                }
                if (redirectIfReady(data)) return;
                handleLoginStepData(data, currentIndexNumber);
            })
            .catch(function() {
                setLoading(document.getElementById('btn-setup-password'), false);
                showError('setup-password-error', 'Network error. Please try again.');
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
            .then(function(r) { return r.json(); })
            .then(function(data) {
                setLoading(document.getElementById('btn-setup-name'), false);
                if (!data.success) {
                    showError('setup-name-error', data.message || 'Could not save name.');
                    return;
                }
                if (redirectIfReady(data)) return;
                handleLoginStepData(data, currentIndexNumber);
            })
            .catch(function() {
                setLoading(document.getElementById('btn-setup-name'), false);
                showError('setup-name-error', 'Network error. Please try again.');
            });
        });
    }
})();
</script>
@endpush
@endsection
