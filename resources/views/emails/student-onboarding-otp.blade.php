@extends('emails.layout')

@section('content')
    @php
        $displayName = trim($student->student_name ?? '') !== '' ? $student->student_name : $student->index_number;
    @endphp
    <h1 style="margin:0 0 12px;font-size:22px;font-weight:700;color:#0f172a;line-height:1.3;">Your verification code</h1>
    <p style="margin:0 0 16px;font-size:15px;line-height:1.65;color:#475569;">
        Hello {{ $displayName }}, use this code to finish setting up your {{ $appName }} student account
        <strong style="color:#0f172a;">({{ $student->index_number }})</strong>.
    </p>

    <p style="margin:0 0 8px;text-align:center;font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:0.08em;">Verification code</p>
    <p style="margin:0 0 24px;text-align:center;font-size:34px;font-weight:800;letter-spacing:0.28em;color:#2563eb;">{{ $code }}</p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 20px;background:#fffbeb;border:1px solid #fde68a;border-radius:10px;">
        <tr>
            <td style="padding:14px 16px;font-size:13px;line-height:1.55;color:#92400e;">
                <strong style="color:#78350f;">Expires in {{ $expiresMinutes }} minutes.</strong>
                Do not share this code with anyone.
            </td>
        </tr>
    </table>

    <p style="margin:0;font-size:13px;line-height:1.55;color:#64748b;">
        If you did not request this, you can ignore this email.
    </p>
@endsection
