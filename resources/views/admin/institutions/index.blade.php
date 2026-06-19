@extends('layouts.dashboard')

@section('title', 'Institutions')
@section('dashboard_heading', 'Institutions')

@section('dashboard_content')
<div class="w-full space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-2">
        <div class="flex items-center gap-2 text-sm text-gray-600">
            <a href="{{ route('dashboard') }}" class="hover:text-primary-600">Dashboard</a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-gray-900 font-medium">Institutions</span>
        </div>
        <a href="{{ route('dashboard.institutions.create') }}" class="btn btn-primary inline-flex items-center gap-1.5 text-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Add institution
        </a>
    </div>

    <p class="text-gray-600 mb-4">Manage the hierarchy: <strong>Institution</strong> → <strong>Faculty</strong> → <strong>Department</strong>. Coordinators are assigned to a faculty; examiners to a department; students inherit their department from class groups. Staff assignments are done in <a href="{{ route('dashboard.users.index') }}" class="text-primary-600 hover:underline">User management</a>.</p>

    @if(session('success'))
        <div class="rounded-lg bg-success-50 border border-success-200 text-success-800 px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg bg-danger-50 border border-danger-200 text-danger-800 px-4 py-3 text-sm">{{ session('error') }}</div>
    @endif

    <div class="card overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-sm font-semibold text-gray-900">Institution</th>
                    <th class="px-4 py-3 text-sm font-semibold text-gray-900">Region</th>
                    <th class="px-4 py-3 text-sm font-semibold text-gray-900">Faculties</th>
                    <th class="px-4 py-3 text-sm font-semibold text-gray-900">Staff</th>
                    <th class="px-4 py-3 text-sm font-semibold text-gray-900 w-24">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($institutions as $inst)
                @php
                    $deptCount = $inst->faculties->sum('departments_count');
                @endphp
                <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50/50">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            @if($inst->logo_url)
                                <img src="{{ $inst->logo_url }}" alt="" class="h-8 w-8 object-contain rounded border border-gray-200 bg-white">
                            @else
                                <span class="h-8 w-8 flex items-center justify-center rounded bg-gray-100 text-gray-500 text-xs font-medium">—</span>
                            @endif
                            <span class="font-medium text-gray-900">{{ $inst->name }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $inst->region ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                        {{ $inst->faculties_count }} {{ Str::plural('faculty', $inst->faculties_count) }}
                        @if($deptCount > 0)
                            <span class="text-gray-400">·</span> {{ $deptCount }} {{ Str::plural('department', $deptCount) }}
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $inst->users_count }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('dashboard.institutions.edit', $inst) }}" class="text-primary-600 hover:text-primary-800 text-sm font-medium">Manage</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">
                        No institutions yet.
                        <a href="{{ route('dashboard.institutions.create') }}" class="text-primary-600 hover:underline">Add the first institution</a>.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
