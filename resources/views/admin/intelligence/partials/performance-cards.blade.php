<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
    @foreach(['average_score'=>'Avg Score','pass_rate'=>'Pass Rate %','failure_rate'=>'Failure Rate %','median_score'=>'Median Score'] as $key => $label)
        <div class="rounded-xl border bg-white p-4 shadow-sm"><p class="text-xs text-gray-500">{{ $label }}</p><p class="text-2xl font-bold">{{ $data[$key] ?? 0 }}</p></div>
    @endforeach
</div>
