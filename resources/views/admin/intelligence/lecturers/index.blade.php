@extends('admin.intelligence.layout')
@php($pageTitle = 'Lecturer Intelligence')
@section('intelligence_content')
<div class="rounded-xl border bg-white shadow-sm overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-gray-50"><tr>
            <th class="px-3 py-2 text-left">Lecturer</th><th class="px-3 py-2 text-left">Exams</th><th class="px-3 py-2 text-left">Attendance</th><th class="px-3 py-2 text-left">Engagement</th><th class="px-3 py-2 text-left">Avg Performance</th><th class="px-3 py-2 text-left">Effectiveness</th>
        </tr></thead>
        <tbody>
            @forelse($data['lecturers'] ?? [] as $row)
                <tr class="border-t"><td class="px-3 py-2">{{ $row['name'] }}</td><td class="px-3 py-2">{{ $row['exams_created'] }}</td><td class="px-3 py-2">{{ $row['attendance_activity'] }}</td><td class="px-3 py-2">{{ $row['student_engagement'] }}</td><td class="px-3 py-2">{{ $row['average_student_performance'] }}</td><td class="px-3 py-2 font-semibold">{{ $row['effectiveness_score'] }}</td></tr>
            @empty
                <tr><td colspan="6" class="px-3 py-8 text-center text-gray-500">No lecturer data.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
