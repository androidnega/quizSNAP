@props([
    'cards' => [],
    'columns' => 'grid-cols-2 md:grid-cols-3 xl:grid-cols-6',
])

@if(!empty($cards))
<div class="grid {{ $columns }} gap-3 mb-4">
    @foreach($cards as $card)
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm min-w-0">
            <div class="flex items-start gap-3">
                @if(!empty($card['icon']))
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-violet-50 text-violet-700">
                        <i class="fas {{ $card['icon'] }} text-sm"></i>
                    </span>
                @endif
                <div class="min-w-0">
                    <p class="text-xs font-medium text-gray-500 truncate">{{ $card['label'] ?? 'Metric' }}</p>
                    <p class="mt-0.5 text-xl sm:text-2xl font-bold tabular-nums text-violet-700 truncate" @if(!empty($card['stat'])) data-intelligence-stat="{{ $card['stat'] }}" @endif>{{ $card['value'] ?? '—' }}</p>
                    @if(!empty($card['hint']))
                        <p class="mt-1 text-xs text-gray-500">{{ $card['hint'] }}</p>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>
@endif
