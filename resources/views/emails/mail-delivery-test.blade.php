@extends('emails.layout')

@section('content')
    <h1 style="margin:0 0 12px;font-size:22px;font-weight:700;color:#0f172a;line-height:1.3;">Mail delivery test</h1>
    <p style="margin:0 0 16px;font-size:15px;line-height:1.65;color:#475569;">
        Your {{ $appName }} SMTP settings are working. This message was sent from Admin Settings to confirm delivery.
    </p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 20px;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;">
        <tr>
            <td style="padding:12px 16px;background:#f8fafc;font-size:13px;color:#64748b;border-bottom:1px solid #e2e8f0;">Sent at</td>
            <td style="padding:12px 16px;font-size:13px;color:#0f172a;border-bottom:1px solid #e2e8f0;">{{ $sentAt }}</td>
        </tr>
        <tr>
            <td style="padding:12px 16px;background:#f8fafc;font-size:13px;color:#64748b;border-bottom:1px solid #e2e8f0;">From</td>
            <td style="padding:12px 16px;font-size:13px;color:#0f172a;border-bottom:1px solid #e2e8f0;">{{ $fromName }} &lt;{{ $fromAddress }}&gt;</td>
        </tr>
        <tr>
            <td style="padding:12px 16px;background:#f8fafc;font-size:13px;color:#64748b;border-bottom:1px solid #e2e8f0;">Host</td>
            <td style="padding:12px 16px;font-size:13px;color:#0f172a;border-bottom:1px solid #e2e8f0;">{{ $host ?: '—' }}</td>
        </tr>
        <tr>
            <td style="padding:12px 16px;background:#f8fafc;font-size:13px;color:#64748b;border-bottom:1px solid #e2e8f0;">Port</td>
            <td style="padding:12px 16px;font-size:13px;color:#0f172a;border-bottom:1px solid #e2e8f0;">{{ $port ?: '—' }}</td>
        </tr>
        <tr>
            <td style="padding:12px 16px;background:#f8fafc;font-size:13px;color:#64748b;">Encryption</td>
            <td style="padding:12px 16px;font-size:13px;color:#0f172a;">{{ $encryption !== '' ? strtoupper($encryption) : 'None' }}</td>
        </tr>
    </table>

    <p style="margin:0;font-size:13px;line-height:1.55;color:#64748b;">
        If this landed in spam, add SPF, DKIM, and DMARC records for your sending domain in your hosting DNS panel.
    </p>
@endsection
