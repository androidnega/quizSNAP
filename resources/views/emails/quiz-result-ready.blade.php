@extends('emails.layout')

@section('content')
    @php
        $quiz = $session->quiz;
        $result = $session->result;
    @endphp
    <h1 style="margin:0 0 12px;font-size:22px;font-weight:700;color:#0f172a;line-height:1.3;">Quiz result ready</h1>
    <p style="margin:0 0 16px;font-size:15px;line-height:1.65;color:#475569;">
        A student has submitted a quiz. Here is a quick summary:
    </p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 20px;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;">
        <tr>
            <td style="padding:12px 16px;background:#f8fafc;font-size:13px;color:#64748b;border-bottom:1px solid #e2e8f0;">Quiz</td>
            <td style="padding:12px 16px;font-size:13px;color:#0f172a;border-bottom:1px solid #e2e8f0;">{{ $quiz?->title ?? '—' }}</td>
        </tr>
        <tr>
            <td style="padding:12px 16px;background:#f8fafc;font-size:13px;color:#64748b;border-bottom:1px solid #e2e8f0;">Student index</td>
            <td style="padding:12px 16px;font-size:13px;color:#0f172a;border-bottom:1px solid #e2e8f0;">{{ $session->student_index ?? '—' }}</td>
        </tr>
        <tr>
            <td style="padding:12px 16px;background:#f8fafc;font-size:13px;color:#64748b;border-bottom:1px solid #e2e8f0;">Score</td>
            <td style="padding:12px 16px;font-size:13px;color:#0f172a;border-bottom:1px solid #e2e8f0;">
                {{ $result ? round($result->score, 1) . '%' : '—' }}
                @if($result)
                    ({{ $result->correct_count }}/{{ $result->total_questions }} correct)
                @endif
            </td>
        </tr>
        <tr>
            <td style="padding:12px 16px;background:#f8fafc;font-size:13px;color:#64748b;">Submitted at</td>
            <td style="padding:12px 16px;font-size:13px;color:#0f172a;">{{ $result?->submitted_at?->format('Y-m-d H:i T') ?? '—' }}</td>
        </tr>
    </table>

    <p style="margin:0;font-size:13px;line-height:1.55;color:#64748b;">
        View full results in your {{ $appName }} dashboard.
    </p>
@endsection
