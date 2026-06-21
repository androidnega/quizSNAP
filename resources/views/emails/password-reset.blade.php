@extends('emails.layout')

@section('content')
    <h1 style="margin:0 0 12px;font-size:22px;font-weight:700;color:#0f172a;line-height:1.3;">Reset your password</h1>
    <p style="margin:0 0 16px;font-size:15px;line-height:1.65;color:#475569;">
        Hello {{ $recipientName }},
    </p>
    <p style="margin:0 0 16px;font-size:15px;line-height:1.65;color:#475569;">
        We received a request to reset the password for your {{ $appName }}
        @if($audience === 'student')
            student account
            @if($accountLabel)
                <strong style="color:#0f172a;">({{ $accountLabel }})</strong>
            @endif
        @else
            staff account
            @if($accountLabel)
                <strong style="color:#0f172a;">({{ $accountLabel }})</strong>
            @endif
        @endif
        . Click the button below to choose a new password.
    </p>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin:0 auto 24px;">
        <tr>
            <td style="border-radius:10px;background:#2563eb;">
                <a href="{{ $resetUrl }}" style="display:inline-block;padding:14px 28px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:10px;">
                    Reset my password
                </a>
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 20px;background:#fffbeb;border:1px solid #fde68a;border-radius:10px;">
        <tr>
            <td style="padding:14px 16px;font-size:13px;line-height:1.55;color:#92400e;">
                <strong style="color:#78350f;">This link expires in {{ $expiresMinutes }} minutes.</strong>
                After that, request a new reset link from the login page.
            </td>
        </tr>
    </table>

    <p style="margin:0 0 8px;font-size:13px;line-height:1.55;color:#64748b;">
        If the button does not work, copy and paste this link into your browser:
    </p>
    <p style="margin:0 0 20px;font-size:12px;line-height:1.55;color:#2563eb;word-break:break-all;">
        {{ $resetUrl }}
    </p>

    <p style="margin:0 0 16px;font-size:13px;line-height:1.55;color:#64748b;">
        Did not receive this email? Check your spam or junk folder. Add {{ $fromAddress ?: 'our sending address' }} to your contacts to improve delivery.
    </p>

    <p style="margin:0;font-size:13px;line-height:1.55;color:#64748b;">
        If you did not request this, you can safely ignore this email. Your password will not change.
    </p>
@endsection
