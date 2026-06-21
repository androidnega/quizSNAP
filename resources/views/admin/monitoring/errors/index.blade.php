@extends('admin.monitoring.layout')
@php($pageTitle = 'Error Logs')
@php($monitoringPage = 'errors')
@section('monitoring_content')
<div class="flex flex-wrap items-center justify-between gap-2 mb-4">
    <form method="get" class="flex flex-wrap gap-2 flex-1 min-w-0">
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Search errors..." class="rounded-lg border border-gray-300 px-3 py-2 text-sm min-w-[12rem]">
        <select name="severity" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <option value="">All severities</option>
            @foreach(['info','warning','error','critical','fatal'] as $sev)
                <option value="{{ $sev }}" @selected(request('severity') === $sev)>{{ ucfirst($sev) }}</option>
            @endforeach
        </select>
        <select name="status" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
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
        <form method="post" action="{{ route('dashboard.monitoring.maintenance.clear-logs') }}" onsubmit="return confirm('Clear all error logs? This cannot be undone.');">
            @csrf
            <input type="hidden" name="category" value="errors">
            <input type="hidden" name="confirm" value="1">
            <button type="submit" class="btn btn-danger btn-sm">Clear all errors</button>
        </form>
    </div>
</div>
<div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="px-3 py-3 w-10"><input type="checkbox" id="select-all-errors" class="rounded border-gray-300" aria-label="Select all"></th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Severity</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Message</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Location</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Count</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Last seen</th>
                <th class="px-4 py-3 text-right font-medium text-gray-500">Actions</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($errors as $error)
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-3"><input type="checkbox" class="error-log-checkbox rounded border-gray-300" value="{{ $error->id }}" aria-label="Select error"></td>
                        <td class="px-4 py-3"><span class="rounded px-2 py-0.5 text-xs font-semibold uppercase bg-red-50 text-red-700">{{ $error->severity }}</span></td>
                        <td class="px-4 py-3 max-w-md"><a href="{{ route('dashboard.monitoring.errors.show', $error) }}" class="text-primary-600 hover:underline">{{ Str::limit($error->message, 100) }}</a></td>
                        <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ basename($error->file ?? '') }}:{{ $error->line }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ $error->occurrence_count }}</td>
                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $error->last_seen_at?->format('M j, Y H:i') }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <button type="button" data-copy-error="{{ $error->id }}" class="text-xs font-medium text-primary-600 hover:underline">Copy</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">No errors found.</td></tr>
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
