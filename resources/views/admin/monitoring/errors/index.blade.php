@extends('admin.monitoring.layout')
@php($pageTitle = 'Error Logs')
@php($monitoringPage = 'errors')
@section('monitoring_content')
<form method="get" class="flex flex-wrap gap-2 mb-4">
    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search errors..." class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
    <select name="severity" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
        <option value="">All severities</option>
        @foreach(['info','warning','error','critical','fatal'] as $sev)
            <option value="{{ $sev }}" @selected(request('severity') === $sev)>{{ ucfirst($sev) }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
</form>
<div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50"><tr>
            <th class="px-4 py-3 text-left font-medium text-gray-500">Severity</th>
            <th class="px-4 py-3 text-left font-medium text-gray-500">Message</th>
            <th class="px-4 py-3 text-left font-medium text-gray-500">Location</th>
            <th class="px-4 py-3 text-left font-medium text-gray-500">Count</th>
            <th class="px-4 py-3 text-left font-medium text-gray-500">Last seen</th>
        </tr></thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($errors as $error)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3"><span class="rounded px-2 py-0.5 text-xs font-semibold uppercase bg-red-50 text-red-700">{{ $error->severity }}</span></td>
                    <td class="px-4 py-3"><a href="{{ route('dashboard.monitoring.errors.show', $error) }}" class="text-primary-600 hover:underline">{{ Str::limit($error->message, 80) }}</a></td>
                    <td class="px-4 py-3 text-gray-600">{{ basename($error->file ?? '') }}:{{ $error->line }}</td>
                    <td class="px-4 py-3 tabular-nums">{{ $error->occurrence_count }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $error->last_seen_at?->diffForHumans() }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No errors found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $errors->links() }}</div>
@endsection
