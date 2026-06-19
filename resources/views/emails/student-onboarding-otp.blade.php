@php
    $appName = \App\Models\Setting::getValue(\App\Models\Setting::KEY_APP_NAME, config('app.name', 'QuizSnap'));
    $displayName = trim($student->student_name ?? '') !== '' ? $student->student_name : $student->index_number;
    $expiresMinutes = $expiresMinutes ?? 15;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your {{ $appName }} verification code</title>
</head>
<body style="margin: 0; padding: 24px 16px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #fafaf9; color: #1e293b;">
    <div style="max-width: 520px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 24px rgba(15, 23, 42, 0.06);">
        <div style="height: 4px; background: linear-gradient(90deg, #fbbf24 0%, #f59e0b 50%, #2563eb 100%);"></div>
        <div style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); padding: 28px 24px 24px; text-align: center;">
            <div style="display: inline-block; background: #fbbf24; color: #1e293b; font-size: 11px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; padding: 4px 10px; border-radius: 999px; margin-bottom: 10px;">Account setup</div>
            <div style="font-size: 1.5rem; font-weight: 800; color: #ffffff; letter-spacing: -0.02em;">{{ $appName }}</div>
        </div>
        <div style="padding: 28px 24px 8px;">
            <h1 style="margin: 0 0 12px; font-size: 1.25rem; font-weight: 700; color: #0f172a;">Your verification code</h1>
            <p style="margin: 0 0 16px; font-size: 0.9375rem; line-height: 1.6; color: #475569;">
                Hello {{ $displayName }}, use this code to finish setting up your student account
                <strong style="color: #0f172a;">({{ $student->index_number }})</strong>.
            </p>
            <p style="margin: 0 0 8px; text-align: center; font-size: 0.8125rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.08em;">Verification code</p>
            <p style="margin: 0 0 24px; text-align: center; font-size: 2rem; font-weight: 800; letter-spacing: 0.35em; color: #2563eb;">{{ $code }}</p>
            <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; padding: 14px 16px; margin-bottom: 20px;">
                <p style="margin: 0; font-size: 0.8125rem; line-height: 1.5; color: #92400e;">
                    <strong style="color: #78350f;">Expires in {{ $expiresMinutes }} minutes.</strong>
                    Do not share this code with anyone.
                </p>
            </div>
            <p style="margin: 0; font-size: 0.8125rem; line-height: 1.5; color: #64748b;">
                If you did not request this, you can ignore this email.
            </p>
        </div>
        <div style="padding: 18px 24px 22px; background: #f8fafc; border-top: 1px solid #e2e8f0;">
            <p style="margin: 0; font-size: 0.75rem; line-height: 1.5; color: #64748b; text-align: center;">Automated message — please do not reply.</p>
            <p style="margin: 6px 0 0; font-size: 0.75rem; color: #94a3b8; text-align: center;">&copy; {{ date('Y') }} {{ $appName }}</p>
        </div>
    </div>
</body>
</html>
