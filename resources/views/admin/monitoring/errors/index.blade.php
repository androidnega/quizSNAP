@extends('admin.monitoring.layout')
@php($pageTitle = 'Error Logs')
@php($monitoringPage = 'errors')
@section('monitoring_content')
<div class="flex flex-wrap items-center justify-between gap-2 mb-4">
    <form method="get" class="flex flex-wrap gap-2 flex-1 min-w-0">
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Search errors..." class="rounded-lg border px-3 py-2 text-sm min-w-[12rem]">
        <select name="severity" class="rounded-lg border px-3 py-2 text-sm">
            <option value="">All severities</option>
            @foreach(['info','warning','error','critical','fatal'] as $sev)
                <option value="{{ $sev }}" @selected(request('severity') === $sev)>{{ ucfirst($sev) }}</option>
            @endforeach
        </select>
        <select name="status" class="rounded-lg border px-3 py-2 text-sm">
            <option value="">All statuses</option>
            @foreach(['open','resolved','ignored'] as $st)
                <option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst($st) }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
    </form>
    <div class="flex flex-wrap gap-2">
        <button type="button" id="copy-selected-errors" data-export-url="{{ route('dashboard.monitoring.errors.export') }}" class="btn btn-secondary btn-sm">Copy selected</button>
        <button type="button" data-copy-error="all" data-export-url="{{ $exportAllUrl ?? route('dashboard.monitoring.errors.export') }}" class="btn btn-secondary btn-sm">Copy all (filtered)</button>
        <a href="{{ $downloadJsonUrl ?? route('dashboard.monitoring.errors.download', ['format' => 'json']) }}" class="btn btn-secondary btn-sm">Download JSON</a>
        <a href="{{ $downloadTxtUrl ?? route('dashboard.monitoring.errors.download', ['format' => 'txt']) }}" class="btn btn-secondary btn-sm">Download TXT</a>
        <form method="post" action="{{ route('dashboard.monitoring.maintenance.clear-logs') }}" onsubmit="return confirm('Clear all error logs? This cannot be undone.');">
            @csrf
            <input type="hidden" name="category" value="errors">
            <input type="hidden" name="confirm" value="1">
            <button type="submit" class="btn btn-danger btn-sm">Clear all errors</button>
        </form>
    </div>
</div>
<div class="rounded-xl border overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y text-sm">
            <thead><tr>
                <th class="px-3 py-3 w-10"><input type="checkbox" id="select-all-errors" class="rounded border-gray-300" aria-label="Select all"></th>
                <th class="px-4 py-3 text-left font-medium">Severity</th>
                <th class="px-4 py-3 text-left font-medium">Message</th>
                <th class="px-4 py-3 text-left font-medium">Location</th>
                <th class="px-4 py-3 text-left font-medium">Count</th>
                <th class="px-4 py-3 text-left font-medium">Last seen</th>
                <th class="px-4 py-3 text-right font-medium">Actions</th>
            </tr></thead>
            <tbody class="divide-y">
                @forelse($errors as $error)
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-3"><input type="checkbox" class="error-log-checkbox rounded border-gray-300" value="{{ $error->id }}" aria-label="Select error"></td>
                        <td class="px-4 py-3"><span class="rounded px-2 py-0.5 text-xs font-semibold uppercase bg-red-50 text-red-700">{{ $error->severity }}</span></td>
                        <td class="px-4 py-3 max-w-md"><a href="{{ route('dashboard.monitoring.errors.show', $error) }}" class="text-primary-600 hover:underline">{{ Str::limit($error->message, 100) }}</a></td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ basename($error->file ?? '') }}:{{ $error->line }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ $error->occurrence_count }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $error->last_seen_at?->format('M j, Y H:i') }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap space-x-2">
                            <button type="button" data-copy-error="{{ $error->id }}" class="text-xs font-medium text-primary-600 hover:underline">Copy</button>
                            <a href="{{ route('dashboard.monitoring.errors.download.single', ['error' => $error, 'format' => 'json']) }}" class="text-xs font-medium text-primary-600 hover:underline">JSON</a>
                            <a href="{{ route('dashboard.monitoring.errors.download.single', ['error' => $error, 'format' => 'txt']) }}" class="text-xs font-medium text-primary-600 hover:underline">TXT</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center">No errors found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $errors->links() }}</div>
@endsection

@push('scripts')
<script src="{{ asset('js/monitoring-errors.js') }}" defer></script>
@endpush
