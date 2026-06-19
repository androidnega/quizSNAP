@extends('layouts.dashboard')

@section('title', 'Student Levels')
@section('dashboard_heading')
<span class="inline-flex items-center gap-2"><i class="fas fa-layer-group text-primary-600"></i> Student Levels</span>
@endsection

@section('dashboard_content')
@php
    $levelsRoutePrefix = $levelsRoutePrefix ?? (session('admin_role') === 'coordinator' ? 'dashboard.coordinators.student-levels' : 'dashboard.student-levels');
    $levelsBaseUrl = rtrim(route($levelsRoutePrefix . '.index'), '/');
@endphp
<div class="w-full space-y-6">
    <div class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
        <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        @if(session('admin_role') === 'coordinator')
            <span class="text-gray-900 font-medium">Student Levels</span>
        @else
            <a href="{{ route('dashboard.settings.index') }}" class="hover:text-gray-700">Settings</a>
            <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-gray-900 font-medium">Student Levels</span>
        @endif
    </div>

    <p class="text-sm text-gray-500">Define levels (e.g. 100, 200, 300) used when creating class groups and when students sign in. Students pick their level on first login.</p>

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-2.5 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <div class="rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50/80">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Levels</p>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead>
                <tr>
                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Value</th>
                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Label</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse($levels as $level)
                <tr>
                    <td class="px-4 py-2.5 text-sm font-mono text-gray-900">{{ $level->value }}</td>
                    <td class="px-4 py-2.5 text-sm text-gray-900">{{ $level->label }}</td>
                    <td class="px-4 py-2.5 text-right">
                        <form action="{{ route($levelsRoutePrefix . '.destroy', $level) }}" method="post" class="inline" onsubmit="return confirm('Remove this level? Students with this level will need to reselect.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm text-red-600 hover:text-red-800 focus:outline-none">Remove</button>
                        </form>
                        <span class="text-gray-300 mx-1">·</span>
                        <button type="button" onclick="editLevel({{ $level->id }}, {{ $level->value }}, '{{ addslashes($level->label) }}')" class="text-sm text-gray-600 hover:text-gray-900 focus:outline-none">Edit</button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="px-4 py-8 text-center text-sm text-gray-500">No levels defined. Add one below.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-4 sm:p-5 shadow-sm">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-4" id="form-title">Add level</p>
        <form action="{{ route($levelsRoutePrefix . '.store') }}" method="post" id="level-form">
            @csrf
            <input type="hidden" name="_method" id="form-method" value="POST">
            <input type="hidden" name="level_id" id="level-id" value="">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="value" class="block text-xs font-medium text-gray-500 mb-0.5">Value</label>
                    <input type="number" name="value" id="value" min="1" max="999" required placeholder="e.g. 100" class="qs-control @error('value') qs-control--error @enderror" value="{{ old('value') }}">
                    @error('value')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="label" class="block text-xs font-medium text-gray-500 mb-0.5">Label</label>
                    <input type="text" name="label" id="label" maxlength="100" required placeholder="e.g. Level 100" class="qs-control @error('label') qs-control--error @enderror" value="{{ old('label') }}">
                    @error('label')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary" id="submit-btn">Add level</button>
                <button type="button" id="cancel-edit-btn" class="hidden btn btn-secondary" onclick="resetForm()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
const levelsBaseUrl = @json($levelsBaseUrl);
const levelsStoreUrl = @json(route($levelsRoutePrefix . '.store'));

function editLevel(id, value, label) {
    document.getElementById('form-title').textContent = 'Edit level';
    document.getElementById('level-form').action = levelsBaseUrl + '/' + id;
    document.getElementById('form-method').value = 'PUT';
    document.getElementById('level-id').value = id;
    document.getElementById('value').value = value;
    document.getElementById('label').value = label;
    document.getElementById('submit-btn').textContent = 'Update';
    document.getElementById('cancel-edit-btn').classList.remove('hidden');
}
function resetForm() {
    document.getElementById('form-title').textContent = 'Add level';
    document.getElementById('level-form').action = levelsStoreUrl;
    document.getElementById('form-method').value = 'POST';
    document.getElementById('level-id').value = '';
    document.getElementById('value').value = '';
    document.getElementById('label').value = '';
    document.getElementById('submit-btn').textContent = 'Add level';
    document.getElementById('cancel-edit-btn').classList.add('hidden');
}
document.getElementById('level-form').addEventListener('submit', function() {
    var id = document.getElementById('level-id').value;
    if (id) {
        this.action = levelsBaseUrl + '/' + id;
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = '_method';
        input.value = 'PUT';
        this.appendChild(input);
    }
});
</script>
@endsection
