@php
    $appName = $appName ?? \App\Models\Setting::getValue(\App\Models\Setting::KEY_APP_NAME, config('app.name', 'QuizSnap'));
    $displayName = trim($student->student_name ?? '') !== '' ? $student->student_name : $student->index_number;
@endphp
Your {{ $appName }} verification code

Hello {{ $displayName }},

Use this code to finish setting up your {{ $appName }} student account ({{ $student->index_number }}):

{{ $code }}

This code expires in {{ $expiresMinutes }} minutes. Do not share it with anyone.

If you did not request this, you can ignore this email.

---
{{ $appName }}
Automated message — please do not reply.
