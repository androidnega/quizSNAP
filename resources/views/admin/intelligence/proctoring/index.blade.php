@extends('admin.intelligence.layout')
@php($pageTitle = 'AI Proctoring Analytics')
@section('intelligence_content')
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
    <div class="rounded-xl border bg-white p-4 shadow-sm"><p class="text-xs text-gray-500">Integrity Score</p><p class="text-2xl font-bold text-emerald-600">{{ $data['integrity_score'] ?? 0 }}</p></div>
    <div class="rounded-xl border bg-white p-4 shadow-sm"><p class="text-xs text-gray-500">Risk Score</p><p class="text-2xl font-bold text-red-600">{{ $data['risk_score'] ?? 0 }}</p></div>
    <div class="rounded-xl border bg-white p-4 shadow-sm"><p class="text-xs text-gray-500">Flagged Students</p><p class="text-2xl font-bold">{{ $data['summary']['flagged_students'] ?? 0 }}</p></div>
    <div class="rounded-xl border bg-white p-4 shadow-sm"><p class="text-xs text-gray-500">Total Violations</p><p class="text-2xl font-bold">{{ $data['summary']['total_violations'] ?? 0 }}</p></div>
</div>
@include('admin.intelligence.partials.metric-grid', ['summary' => $data['summary'] ?? [], 'keys' => ['face_verification_failures'=>'Face Failures','multiple_faces'=>'Multiple Faces','phone_detected'=>'Phone','tab_switching'=>'Tab Switch','copy_paste'=>'Copy/Paste','window_blur'=>'Window Blur']])
<div class="rounded-xl border bg-white p-4 shadow-sm mt-4"><h3 class="text-sm font-semibold mb-2">Repeat Offenders</h3><ul class="text-sm space-y-1">@forelse($data['repeat_offenders'] ?? [] as $row)<li class="flex justify-between border-b py-1"><span>{{ $row['student_index'] }}</span><strong>{{ $row['violations'] }} violations</strong></li>@empty<li class="text-gray-500">None detected.</li>@endforelse</ul></div>
@endsection
