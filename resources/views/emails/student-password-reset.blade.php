@php
    $appName = \App\Models\Setting::getValue(\App\Models\Setting::KEY_APP_NAME, config('app.name', 'QuizSnap'));
    $institutionName = \App\Models\Setting::getValue(\App\Models\Setting::KEY_INSTITUTION_NAME, '');
    $logoUrl = \App\Models\Setting::getValue(\App\Models\Setting::KEY_INSTITUTION_LOGO, '');
    $expiresMinutes = $expiresMinutes ?? 60;
    $displayName = trim($student->student_name ?? '') !== '' ? $student->student_name : $student->index_number;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset your {{ $appName }} password</title>
</head>
<body style="margin: 0; padding: 24px 16px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #fafaf9; color: #1e293b;">
    <div style="max-width: 520px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 24px rgba(15, 23, 42, 0.06);">
        {{-- Brand header: primary blue + amber accent --}}
        <div style="height: 4px; background: linear-gradient(90deg, #fbbf24 0%, #f59e0b 50%, #2563eb 100%);"></div>
        <div style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); padding: 28px 24px 24px; text-align: center;">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $appName }}" style="max-height: 44px; max-width: 160px; display: inline-block;" />
            @else
                <div style="display: inline-block; background: #fbbf24; color: #1e293b; font-size: 11px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; padding: 4px 10px; border-radius: 999px; margin-bottom: 10px;">Student account</div>
                <div style="font-size: 1.5rem; font-weight: 800; color: #ffffff; letter-spacing: -0.02em;">{{ $appName }}</div>
            @endif
        </div>

        <div style="padding: 28px 24px 8px;">
            <h1 style="margin: 0 0 12px; font-size: 1.25rem; font-weight: 700; color: #0f172a; line-height: 1.3;">Reset your password</h1>
            <p style="margin: 0 0 16px; font-size: 0.9375rem; line-height: 1.6; color: #475569;">
                Hello {{ $displayName }},
            </p>
            <p style="margin: 0 0 16px; font-size: 0.9375rem; line-height: 1.6; color: #475569;">
                We received a request to reset the password for your {{ $appName }} student account
                <strong style="color: #0f172a;">({{ $student->index_number }})</strong>.
                Tap the button below to choose a new password.
            </p>

            <p style="margin: 0 0 24px; text-align: center;">
                <a href="{{ $resetUrl }}" style="display: inline-block; padding: 14px 28px; background: #2563eb; color: #ffffff !important; text-decoration: none; font-weight: 700; font-size: 0.9375rem; border-radius: 10px; box-shadow: 0 2px 8px rgba(37, 99, 235, 0.35);">Reset my password</a>
            </p>

            <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; padding: 14px 16px; margin-bottom: 20px;">
                <p style="margin: 0; font-size: 0.8125rem; line-height: 1.5; color: #92400e;">
                    <strong style="color: #78350f;">This link expires in {{ $expiresMinutes }} minutes.</strong>
                    After that, request a new reset link from the login page.
                </p>
            </div>

            <p style="margin: 0 0 8px; font-size: 0.8125rem; line-height: 1.5; color: #64748b;">
                If the button does not work, copy and paste this link into your browser:
            </p>
            <p style="margin: 0 0 20px; font-size: 0.75rem; line-height: 1.5; color: #2563eb; word-break: break-all;">
                {{ $resetUrl }}
            </p>

            <p style="margin: 0; font-size: 0.8125rem; line-height: 1.5; color: #64748b;">
                If you did not request this, you can safely ignore this email. Your password will not change.
            </p>
        </div>

        <div style="padding: 18px 24px 22px; background: #f8fafc; border-top: 1px solid #e2e8f0;">
            <p style="margin: 0; font-size: 0.75rem; line-height: 1.5; color: #64748b; text-align: center;">
                Automated message — please do not reply.
            </p>
            @if($institutionName)
                <p style="margin: 6px 0 0; font-size: 0.75rem; color: #94a3b8; text-align: center;">{{ $institutionName }}</p>
            @endif
            <p style="margin: 6px 0 0; font-size: 0.75rem; color: #94a3b8; text-align: center;">&copy; {{ date('Y') }} {{ $appName }}</p>
        </div>
    </div>
</body>
</html>
