@extends('admin.monitoring.layout')
@php($pageTitle = 'Error Details')
@php($monitoringPage = 'errors')
@section('monitoring_content')
<div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm space-y-4">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <span class="rounded px-2 py-0.5 text-xs font-semibold uppercase bg-red-50 text-red-700">{{ $error->severity }}</span>
            <h2 class="mt-2 text-lg font-semibold text-gray-900 break-words">{{ $error->exception_class }}</h2>
            <p class="text-sm text-gray-600 mt-1 break-words">{{ $error->message }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" data-copy-error="{{ $error->id }}" class="btn btn-secondary btn-sm">Copy log</button>
            <form method="post" action="{{ route('dashboard.monitoring.errors.resolve', $error) }}">@csrf<button class="btn btn-secondary btn-sm">Resolve</button></form>
            <form method="post" action="{{ route('dashboard.monitoring.errors.ignore', $error) }}">@csrf<button class="btn btn-secondary btn-sm">Ignore</button></form>
        </div>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
        <div><span class="text-gray-500">Occurrences</span><p class="font-semibold">{{ $error->occurrence_count }}</p></div>
        <div><span class="text-gray-500">Affected users</span><p class="font-semibold">{{ $error->affected_users_count }}</p></div>
        <div><span class="text-gray-500">First seen</span><p class="font-semibold">{{ $error->first_seen_at?->format('M j, Y H:i:s') }}</p></div>
        <div><span class="text-gray-500">Last seen</span><p class="font-semibold">{{ $error->last_seen_at?->format('M j, Y H:i:s') }}</p></div>
    </div>
    @if($error->source_context)
        <div>
            <h3 class="text-sm font-semibold text-gray-900 mb-2">Source context</h3>
            <pre class="overflow-x-auto rounded-lg bg-gray-900 p-4 text-xs text-green-100">@foreach($error->source_context['lines'] ?? [] as $num => $line)<span class="{{ $num == ($error->source_context['line'] ?? 0) ? 'bg-red-900/50' : '' }}">{{ str_pad($num, 4, ' ', STR_PAD_LEFT) }} {{ $line }}
</span>@endforeach</pre>
        </div>
    @endif
    <div>
        <h3 class="text-sm font-semibold text-gray-900 mb-2">Recent occurrences</h3>
        <div class="space-y-2">
            @foreach($error->occurrences as $occ)
                <div class="rounded-lg border border-gray-100 px-3 py-2 text-sm">
                    <p class="font-medium text-gray-900">{{ $occ->occurred_at?->format('M j, Y H:i:s') }} — {{ $occ->user_name ?? 'Guest' }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">IP {{ $occ->ip_address ?? '—' }} · {{ $occ->browser ?? 'Unknown browser' }} · {{ $occ->device ?? 'Desktop' }}</p>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/monitoring-errors.js') }}" defer></script>
@endpush
