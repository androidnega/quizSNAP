@php
    $appName = $appName ?? \App\Models\Setting::getValue(\App\Models\Setting::KEY_APP_NAME, config('app.name', 'QuizSnap'));
    $institutionName = $institutionName ?? \App\Models\Setting::getValue(\App\Models\Setting::KEY_INSTITUTION_NAME, '');
    $logoUrl = $logoUrl ?? \App\Models\Setting::getValue(\App\Models\Setting::KEY_INSTITUTION_LOGO, '');
    $year = $year ?? date('Y');
    $preheader = trim($preheader ?? '');
    $badge = $badge ?? null;
    $isPreview = $isPreview ?? false;
    $heading = $heading ?? ($title ?? $appName);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $heading }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;color:#0f172a;">
@if($preheader !== '')
    <div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">
        {{ $preheader }}&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;
    </div>
@endif
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f1f5f9;padding:32px 16px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:560px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e2e8f0;box-shadow:0 8px 30px rgba(15,23,42,0.06);">
                <tr>
                    <td style="height:4px;background:linear-gradient(90deg,#fbbf24 0%,#f59e0b 45%,#2563eb 100%);font-size:0;line-height:0;">&nbsp;</td>
                </tr>
                @if($isPreview)
                <tr>
                    <td style="background:#fffbeb;color:#92400e;padding:12px 20px;text-align:center;font-size:13px;font-weight:700;border-bottom:1px solid #fde68a;">
                        Preview email — the reset link below is for testing only and will not change any password.
                    </td>
                </tr>
                @endif
                <tr>
                    <td style="background:linear-gradient(135deg,#2563eb 0%,#1d4ed8 100%);padding:28px 24px 24px;text-align:center;">
                        @if($logoUrl !== '')
                            <img src="{{ $logoUrl }}" alt="{{ $appName }}" width="160" style="max-height:48px;width:auto;display:inline-block;border:0;">
                        @else
                            @if($badge)
                                <div style="display:inline-block;background:#fbbf24;color:#1e293b;font-size:11px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;padding:4px 12px;border-radius:999px;margin-bottom:10px;">{{ $badge }}</div><br>
                            @endif
                            <div style="font-size:24px;font-weight:800;color:#ffffff;letter-spacing:-0.02em;line-height:1.2;">{{ $appName }}</div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding:32px 28px 12px;">
                        @yield('content')
                    </td>
                </tr>
                <tr>
                    <td style="padding:20px 28px 28px;background:#f8fafc;border-top:1px solid #e2e8f0;">
                        <p style="margin:0;font-size:12px;line-height:1.6;color:#64748b;text-align:center;">
                            This is an automated message from {{ $appName }}. Please do not reply to this email.
                        </p>
                        @if($institutionName !== '')
                            <p style="margin:8px 0 0;font-size:12px;line-height:1.5;color:#94a3b8;text-align:center;">{{ $institutionName }}</p>
                        @endif
                        <p style="margin:8px 0 0;font-size:12px;line-height:1.5;color:#94a3b8;text-align:center;">&copy; {{ $year }} {{ $appName }}</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
