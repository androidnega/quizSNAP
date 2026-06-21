<div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-4">
    @foreach($keys as $key => $label)
        <div class="rounded-xl border bg-white p-3 shadow-sm"><p class="text-xs text-gray-500">{{ $label }}</p><p class="text-xl font-bold">{{ number_format($summary[$key] ?? 0) }}</p></div>
    @endforeach
</div>
