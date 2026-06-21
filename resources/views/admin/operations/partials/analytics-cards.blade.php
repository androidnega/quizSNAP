@php
    $sections = $sections ?? [];
@endphp
<div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
    @foreach($sections as $key => $title)
        <div class="rounded-xl border bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold mb-2">{{ $title }}</h3>
            <ul class="space-y-1 text-sm max-h-64 overflow-y-auto">
                @forelse($data[$key] ?? [] as $row)
                    <li class="flex justify-between border-b py-1">
                        <span>{{ $row['name'] ?? ($row['student'] ?? '—') }}</span>
                        <strong>{{ number_format($row['count'] ?? $row['total'] ?? $row['attempts'] ?? $row['exams_created'] ?? $row['activity'] ?? 0) }}</strong>
                    </li>
                @empty
                    <li class="text-gray-500">No data for this period.</li>
                @endforelse
            </ul>
        </div>
    @endforeach
</div>
