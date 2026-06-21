@php
    $appName = $appName ?? \App\Models\Setting::getValue(\App\Models\Setting::KEY_APP_NAME, config('app.name', 'QuizSnap'));
@endphp
{{ $appName }} mail delivery test

Your SMTP settings are working. This message was sent from Admin Settings to confirm delivery.

Sent at: {{ $sentAt }}
From: {{ $fromName }} <{{ $fromAddress }}>
Host: {{ $host ?: '—' }}
Port: {{ $port ?: '—' }}
Encryption: {{ $encryption !== '' ? strtoupper($encryption) : 'None' }}

If this landed in spam, add SPF, DKIM, and DMARC records for your sending domain in your hosting DNS panel.

---
{{ $appName }}
Automated message — please do not reply.
