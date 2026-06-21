@extends('admin.intelligence.layout')
@php($pageTitle = 'Engagement Analytics')
@section('intelligence_content')
<div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-4">
    <div class="rounded-xl border bg-white p-4 shadow-sm"><p class="text-xs text-gray-500">Exam Participation</p><p class="text-2xl font-bold">{{ number_format($data['exam_participation'] ?? 0) }}</p></div>
    <div class="rounded-xl border bg-white p-4 shadow-sm"><p class="text-xs text-gray-500">Attendance Participation</p><p class="text-2xl font-bold">{{ number_format($data['attendance_participation'] ?? 0) }}</p></div>
</div>
<div class="rounded-xl border bg-white p-4 shadow-sm"><h3 class="text-sm font-semibold mb-2">Engagement Rankings</h3><ul class="text-sm">@forelse($data['rankings'] ?? [] as $row)<li class="flex justify-between border-b py-1"><span>{{ $row['student_index'] }}</span><strong>{{ $row['score'] }}</strong></li>@empty<li class="text-gray-500">No rankings yet.</li>@endforelse</ul></div>
@endsection
