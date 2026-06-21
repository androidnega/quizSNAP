@props([
    'title' => 'Details',
    'items' => [],
    'empty' => 'No data for this period.',
    'valueKey' => null,
])

<div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm min-w-0">
    <h3 class="text-sm font-semibold text-gray-900 mb-3">{{ $title }}</h3>
    <ul class="space-y-2 text-sm max-h-72 overflow-y-auto">
        @forelse($items as $row)
            <li class="flex items-center justify-between gap-3 rounded-lg border border-gray-100 px-3 py-2">
                <span class="min-w-0 truncate text-gray-800">{{ $row['name'] ?? $row['student'] ?? $row['title'] ?? $row['action'] ?? '—' }}</span>
                @if($valueKey && isset($row[$valueKey]))
                    <strong class="shrink-0 tabular-nums text-violet-700">{{ is_numeric($row[$valueKey]) ? number_format($row[$valueKey]) : $row[$valueKey] }}</strong>
                @elseif(isset($row['count']) || isset($row['total']) || isset($row['avg_score']) || isset($row['effectiveness_score']))
                    <strong class="shrink-0 tabular-nums text-violet-700">{{ number_format($row['count'] ?? $row['total'] ?? $row['avg_score'] ?? $row['effectiveness_score'] ?? 0) }}</strong>
                @endif
            </li>
        @empty
            <li class="text-sm text-gray-500 py-4 text-center">{{ $empty }}</li>
        @endforelse
    </ul>
</div>
