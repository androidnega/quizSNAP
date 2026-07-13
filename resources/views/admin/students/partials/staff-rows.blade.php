@php $search = $search ?? ''; $isSuperAdmin = $isSuperAdmin ?? false; @endphp
@forelse($students as $s)
@php
    $classGroup = $s->classGroup;
    $phone = $s->studentAccount?->phone_contact;
    $phone = $phone && trim($phone) !== '' ? trim($phone) : null;
    $displayName = $s->studentAccount?->student_name ?? $s->student_name;
    $displayName = $displayName && trim($displayName) !== '' ? trim($displayName) : '—';
    $institution = $classGroup?->examiner?->institution;
    $passwordChanges = (int) ($s->studentAccount?->password_change_count ?? 0);
@endphp
<tr class="hover:bg-gray-50">
    <td class="px-4 py-3 text-sm font-medium text-gray-900">{!! \App\Support\SearchHighlight::mark($s->index_number, $search) !!}</td>
    <td class="px-4 py-3 text-sm text-gray-600">{!! \App\Support\SearchHighlight::mark($displayName, $search) !!}</td>
    <td class="px-4 py-3 text-sm text-gray-600">{!! \App\Support\SearchHighlight::mark($phone ?? '—', $search) !!}</td>
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
    <td class="px-4 py-3 text-sm text-right tabular-nums text-gray-700">{{ $passwordChanges }}</td>
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
    <td colspan="{{ $isSuperAdmin ? 7 : 6 }}" class="px-4 py-10 text-center text-sm text-gray-500">
        {{ trim((string) $search) !== '' ? 'No students match your search.' : 'No students found in your scope.' }}
    </td>
</tr>
@endforelse
