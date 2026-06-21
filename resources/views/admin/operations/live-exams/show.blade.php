@extends('admin.operations.layout')
@php($pageTitle = 'Exam Details — '.$quiz->title)
@section('operations_content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
    <div class="rounded-xl border bg-white p-4 shadow-sm lg:col-span-2">
        <h2 class="text-lg font-semibold">{{ $quiz->title }}</h2>
        <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
            <div><dt class="text-gray-500">Course</dt><dd>{{ $quiz->course?->name ?? $quiz->classGroup?->name ?? '—' }}</dd></div>
            <div><dt class="text-gray-500">Examiner</dt><dd>{{ $quiz->examiner?->name ?? '—' }}</dd></div>
            <div><dt class="text-gray-500">Starts</dt><dd>{{ $quiz->starts_at ?? '—' }}</dd></div>
            <div><dt class="text-gray-500">Ends</dt><dd>{{ $quiz->ends_at ?? '—' }}</dd></div>
            <div><dt class="text-gray-500">Active students</dt><dd>{{ $exam['students_active'] ?? 0 }}</dd></div>
            <div><dt class="text-gray-500">Risk level</dt><dd class="uppercase font-semibold">{{ $exam['risk_level'] ?? 'low' }}</dd></div>
        </dl>
    </div>
    <div class="rounded-xl border bg-white p-4 shadow-sm space-y-3">
        <h3 class="text-sm font-semibold">Exam Controls</h3>
        <form method="post" action="{{ route('dashboard.operations.live-exams.pause', $quiz) }}">@csrf<button class="btn btn-secondary btn-sm w-full">{{ $quiz->is_paused ? 'Resume Exam' : 'Pause Exam' }}</button></form>
        <form method="post" action="{{ route('dashboard.operations.live-exams.extend', $quiz) }}" class="flex gap-2">@csrf<input type="number" name="additional_minutes" min="1" max="120" value="15" class="rounded border px-2 py-1 text-sm w-20"><button class="btn btn-secondary btn-sm flex-1">Extend Time</button></form>
        <form method="post" action="{{ route('dashboard.operations.live-exams.end', $quiz) }}" onsubmit="return confirm('End this exam for all students?')">@csrf<button class="btn btn-danger btn-sm w-full">End Exam</button></form>
        <form method="post" action="{{ route('dashboard.operations.live-exams.broadcast', $quiz) }}" class="space-y-2">@csrf<textarea name="message" rows="3" class="w-full rounded border px-2 py-1 text-sm" placeholder="Broadcast message to students"></textarea><button class="btn btn-primary btn-sm w-full">Broadcast Message</button></form>
    </div>
</div>
@endsection
