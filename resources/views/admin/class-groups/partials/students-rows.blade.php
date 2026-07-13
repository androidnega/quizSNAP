@php $isSuperAdmin = $isSuperAdmin ?? false; @endphp
@foreach($students as $s)
@php
    $phone = $s->studentAccount?->phone_contact ?? null;
    $phone = $phone && trim($phone) !== '' ? trim($phone) : null;
    $displayName = $s->studentAccount?->student_name ?? $s->student_name ?? null;
    $displayName = $displayName && trim($displayName) !== '' ? trim($displayName) : '—';
@endphp
<tr class="hover:bg-gray-50/80">
    @can('update', $classGroup)
    <td class="px-3 py-2">
        <input type="checkbox" name="student_ids[]" value="{{ $s->id }}" class="h-3.5 w-3.5 text-gray-900 border-gray-300 rounded student-select-checkbox" form="bulk-delete-form">
    </td>
    @endcan
    <td class="px-3 py-2 text-sm font-medium text-gray-900 tabular-nums">{{ $s->index_number }}</td>
    <td class="px-3 py-2 text-sm text-gray-600">{{ $displayName }}</td>
    <td class="px-3 py-2 text-sm text-gray-500">{{ $phone ?? '—' }}</td>
    <td class="px-3 py-2 text-right">
        <div class="inline-flex items-center justify-end gap-1.5">
            <a href="{{ route('dashboard.class-groups.students.show', [$classGroup, $s]) }}" class="text-xs font-medium text-gray-500 hover:text-gray-900">View</a>
            @can('update', $classGroup)
            <a href="{{ route('dashboard.class-groups.students.edit', [$classGroup, $s]) }}" class="text-xs font-medium text-gray-500 hover:text-gray-900">Edit</a>
            <form action="{{ route('dashboard.class-groups.students.destroy', [$classGroup, $s]) }}" method="post" class="inline" onsubmit="return confirm('Remove this index from the group?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-xs font-medium text-rose-600 hover:text-rose-800 bg-transparent border-0 p-0 cursor-pointer">Remove</button>
            </form>
            @endcan
        </div>
    </td>
</tr>
@endforeach
