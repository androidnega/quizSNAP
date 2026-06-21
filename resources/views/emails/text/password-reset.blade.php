@php
    $appName = $appName ?? \App\Models\Setting::getValue(\App\Models\Setting::KEY_APP_NAME, config('app.name', 'QuizSnap'));
@endphp
Reset your {{ $appName }} password

Hello {{ $recipientName }},

We received a request to reset the password for your {{ $appName }} {{ $audience === 'student' ? 'student' : 'staff' }} account{{ $accountLabel ? ' ('.$accountLabel.')' : '' }}.

Reset your password using this link:
{{ $resetUrl }}

This link expires in {{ $expiresMinutes }} minutes.

If you do not see this email in your inbox, check your spam or junk folder.

If you did not request this, you can safely ignore this email. Your password will not change.

---
{{ $appName }}
Automated message — please do not reply.
