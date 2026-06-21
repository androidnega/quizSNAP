<div class="rounded-xl border bg-white p-4 shadow-sm mb-4">
    <h3 class="text-sm font-semibold mb-3">{{ $title ?? 'Students' }}</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="px-3 py-2 text-left">Student</th><th class="px-3 py-2 text-left">Performance</th><th class="px-3 py-2 text-left">Attendance</th><th class="px-3 py-2 text-left">Engagement</th><th class="px-3 py-2 text-left">Risk</th><th class="px-3 py-2 text-left">Trend</th>
            </tr></thead>
            <tbody>
                @forelse($rows as $row)
                    <tr class="border-t"><td class="px-3 py-2">{{ $row['student_index'] ?? '—' }}</td><td class="px-3 py-2">{{ $row['performance_score'] ?? '—' }}</td><td class="px-3 py-2">{{ $row['attendance_score'] ?? '—' }}</td><td class="px-3 py-2">{{ $row['engagement_score'] ?? '—' }}</td><td class="px-3 py-2 uppercase">{{ $row['risk_level'] ?? '—' }}</td><td class="px-3 py-2">{{ $row['improvement_trend'] ?? '—' }}</td></tr>
                @empty
                    <tr><td colspan="6" class="px-3 py-6 text-center text-gray-500">No students in this category.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
