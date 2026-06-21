@php
    $appName = $appName ?? \App\Models\Setting::getValue(\App\Models\Setting::KEY_APP_NAME, config('app.name', 'QuizSnap'));
    $quiz = $session->quiz;
    $result = $session->result;
@endphp
{{ $appName }} quiz result ready

A student has submitted a quiz.

Quiz: {{ $quiz?->title ?? '—' }}
Student index: {{ $session->student_index ?? '—' }}
Score: {{ $result ? round($result->score, 1) . '%' : '—' }}{{ $result ? ' ('.$result->correct_count.'/'.$result->total_questions.' correct)' : '' }}
Submitted at: {{ $result?->submitted_at?->format('Y-m-d H:i T') ?? '—' }}

View full results in your {{ $appName }} dashboard.

---
{{ $appName }}
Automated message — please do not reply.
