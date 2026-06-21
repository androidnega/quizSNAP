<div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm mb-4">
    <h3 class="text-sm font-semibold text-gray-900 mb-3">{{ $title ?? 'Students' }}</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="px-3 py-2 text-left font-medium text-gray-500">Student</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500">Performance</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500">Attendance</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500">Engagement</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500">Risk</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500">Trend</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($rows as $row)
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-2 font-medium text-gray-900">{{ $row['student_index'] ?? '—' }}</td>
                        <td class="px-3 py-2 tabular-nums">{{ $row['performance_score'] ?? '—' }}</td>
                        <td class="px-3 py-2 tabular-nums">{{ $row['attendance_score'] ?? '—' }}</td>
                        <td class="px-3 py-2 tabular-nums">{{ $row['engagement_score'] ?? '—' }}</td>
                        <td class="px-3 py-2 uppercase text-xs font-semibold">{{ $row['risk_level'] ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $row['improvement_trend'] ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-3 py-8 text-center text-gray-500">No students in this category.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
