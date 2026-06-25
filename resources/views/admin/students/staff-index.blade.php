@extends('layouts.dashboard')

@section('title', 'Students')
@section('dashboard_heading')
    <span class="inline-flex items-center gap-2"><i class="fas fa-user-graduate text-primary-600"></i> Students</span>
@endsection

@section('dashboard_content')
<div class="w-full space-y-4">
    <p class="text-sm text-gray-600">
        @if($isSuperAdmin)
            Search and manage students across all institutions. Open a student to view full details, edit their index, name, or phone, or manage them within their class group.
        @else
            Search and manage students in your faculty or department. Open a student to view full details or edit their information.
        @endif
    </p>

    <form method="get" action="{{ route('dashboard.students.index') }}" class="flex flex-wrap items-end gap-3 rounded-lg border border-gray-200 bg-white p-4">
        <div class="min-w-[200px] flex-1">
            <label for="student_search" class="block text-xs font-medium text-gray-500 mb-1">Search</label>
            <input type="search" name="search" id="student_search" value="{{ $search }}" placeholder="Index, name, or phone" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none">
        </div>
        <div>
            <label for="filter_class_group" class="block text-xs font-medium text-gray-500 mb-1">Class group</label>
            <select name="class_group_id" id="filter_class_group" class="block w-full min-w-[180px] rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none">
                <option value="">All groups</option>
                @foreach($classGroups as $group)
                    <option value="{{ $group->id }}" {{ (string) request('class_group_id') === (string) $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                @endforeach
            </select>
        </div>
        @if($isSuperAdmin && $institutions->isNotEmpty())
        <div>
            <label for="filter_institution" class="block text-xs font-medium text-gray-500 mb-1">Institution</label>
            <select name="institution_id" id="filter_institution" class="block w-full min-w-[200px] rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none">
                <option value="">All institutions</option>
                @foreach($institutions as $inst)
                    <option value="{{ $inst->id }}" {{ (string) request('institution_id') === (string) $inst->id ? 'selected' : '' }}>{{ $inst->display_name }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <div class="flex items-end gap-2">
            <button type="submit" class="inline-flex items-center justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-700">Search</button>
            <a href="{{ route('dashboard.students.index') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Clear</a>
        </div>
    </form>

    <div class="rounded-lg border border-gray-200 bg-white overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Index</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Phone</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Class group</th>
                        @if($isSuperAdmin)
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Institution</th>
                        @endif
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($students as $s)
                    @php
                        $classGroup = $s->classGroup;
                        $phone = $s->studentAccount?->phone_contact;
                        $phone = $phone && trim($phone) !== '' ? trim($phone) : null;
                        $displayName = $s->studentAccount?->student_name ?? $s->student_name;
                        $displayName = $displayName && trim($displayName) !== '' ? trim($displayName) : '—';
                        $institution = $classGroup?->examiner?->institution;
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $s->index_number }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $displayName }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $phone ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            @if($classGroup)
                                <a href="{{ route('dashboard.class-groups.show', $classGroup) }}" class="text-primary-600 hover:text-primary-800">{{ $classGroup->name }}</a>
                            @else
                                —
                            @endif
                        </td>
                        @if($isSuperAdmin)
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $institution?->display_name ?? '—' }}</td>
                        @endif
                        <td class="px-4 py-3 text-right">
                            @if($classGroup)
                            <div class="inline-flex items-center justify-end gap-2 flex-wrap">
                                <a href="{{ route('dashboard.class-groups.students.show', [$classGroup, $s]) }}" class="inline-flex items-center gap-1 text-gray-600 hover:text-primary-600 text-sm"><i class="fas fa-eye"></i> View</a>
                                @can('update', $classGroup)
                                <a href="{{ route('dashboard.class-groups.students.edit', [$classGroup, $s]) }}" class="inline-flex items-center gap-1 text-primary-600 hover:text-primary-800 text-sm"><i class="fas fa-pen"></i> Edit</a>
                                @endcan
                            </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $isSuperAdmin ? 6 : 5 }}" class="px-4 py-10 text-center text-sm text-gray-500">No students found in your scope.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($students->hasPages())
        <div>{{ $students->links() }}</div>
    @endif
</div>
@endsection
