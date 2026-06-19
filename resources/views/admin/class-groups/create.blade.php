@extends('layouts.dashboard')

@section('title', 'Create Class Group')
@section('dashboard_heading', 'Create Class Group')

@push('styles')
<style>
    .class-group-form .form-input {
        width: 100%;
        border-radius: 0.5rem;
        border: 1px solid #e5e7eb;
        background-color: #f9fafb;
        padding: 0.625rem 0.75rem;
        color: #111827;
        min-height: 44px;
        transition: border-color 0.15s, box-shadow 0.15s;
    }
    .class-group-form .form-input:focus {
        outline: none;
        border-color: #eab308;
        box-shadow: 0 0 0 2px rgba(234, 179, 8, 0.25);
    }
    .class-group-form .form-input.is-invalid,
    .class-group-form select.form-input.is-invalid {
        border-color: #ef4444;
        background-color: #fef2f2;
        box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2);
    }
    .class-group-form .course-row.is-invalid {
        border-color: #fca5a5;
        background-color: #fef2f2;
    }
    .class-group-form .form-input::placeholder { color: #9ca3af; }
    .class-group-form select.form-input { appearance: auto; }
</style>
@endpush

@section('dashboard_content')
<div class="w-full">
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <p class="text-sm text-gray-600 mb-6">Create a class group with level, semester, year, and courses. For each course, assign the lecturer who teaches it.</p>

        <form action="{{ route('dashboard.class-groups.store') }}" method="post" class="class-group-form space-y-6" id="class-group-form">
            @csrf

            @if(session('error') || $errors->any())
                <div id="server-validation-errors" class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                    @if(session('error'))
                        <p class="font-medium">{{ session('error') }}</p>
                    @endif
                    @if($errors->any())
                        <p class="font-semibold text-red-900 {{ session('error') ? 'mt-2' : '' }} mb-1">Could not save — please fix the following:</p>
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach($errors->all() as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif

            @include('admin.class-groups.partials.client-validation-alert')
            {{-- 1. Class group name first --}}
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Class Group Name *</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required maxlength="255" placeholder="e.g. BTECH Group A L100 S1" class="form-input">
            </div>

            {{-- 2. Level, Semester, Academic Year, Academic Class with "create" links --}}
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="level_id" class="block text-sm font-medium text-gray-700 mb-1.5">Level *</label>
                    <select name="level_id" id="level_id" required class="form-input @error('level_id') is-invalid @enderror">
                        <option value="">— Select —</option>
                        @foreach($levels as $l)
                            <option value="{{ $l->id }}" data-value="{{ $l->value }}" {{ old('level_id') == $l->id ? 'selected' : '' }}>{{ $l->label }}</option>
                        @endforeach
                    </select>
                    @error('level_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    @php
                        $canManageLevels = auth()->user() && in_array(session('admin_role'), ['super_admin', 'coordinator'], true);
                        $studentLevelsRoute = session('admin_role') === 'coordinator'
                            ? 'dashboard.coordinators.student-levels.index'
                            : 'dashboard.student-levels.index';
                    @endphp
                    @if($canManageLevels)
                        <a href="{{ route($studentLevelsRoute) }}" class="mt-1 block text-xs text-primary-600 hover:text-primary-800 hover:underline">Add level</a>
                    @endif
                </div>
                <div>
                    <label for="semester_id" class="block text-sm font-medium text-gray-700 mb-1.5">Semester *</label>
                    <select name="semester_id" id="semester_id" required class="form-input">
                        <option value="">— Select —</option>
                        @foreach($semesters as $s)
                            <option value="{{ $s->id }}" {{ old('semester_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                    <a href="{{ route('dashboard.coordinators.semesters.create') }}" class="mt-1 block text-xs text-primary-600 hover:text-primary-800 hover:underline">Add semester</a>
                </div>
                <div>
                    <label for="academic_year_id" class="block text-sm font-medium text-gray-700 mb-1.5">Academic Year *</label>
                    <select name="academic_year_id" id="academic_year_id" required class="form-input @error('academic_year_id') is-invalid @enderror">
                        <option value="">— Select —</option>
                        @foreach($academicYears as $y)
                            <option value="{{ $y->id }}" {{ old('academic_year_id') == $y->id ? 'selected' : '' }}>{{ $y->year }}</option>
                        @endforeach
                    </select>
                    @error('academic_year_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    <a href="{{ route('dashboard.coordinators.academic-years.create') }}" class="mt-1 block text-xs text-primary-600 hover:text-primary-800 hover:underline">Add academic year</a>
                </div>
                <div>
                    <label for="academic_class_id" class="block text-sm font-medium text-gray-700 mb-1.5">Academic Class (optional)</label>
                    <select name="academic_class_id" id="academic_class_id" class="form-input">
                        <option value="">— None —</option>
                        @foreach($academicClasses as $ac)
                            <option value="{{ $ac->id }}" {{ old('academic_class_id') == $ac->id ? 'selected' : '' }}>{{ $ac->name }}</option>
                        @endforeach
                    </select>
                    <a href="{{ route('dashboard.coordinators.academic-classes.create') }}" class="mt-1 block text-xs text-primary-600 hover:text-primary-800 hover:underline">Add academic class</a>
                </div>
            </div>

            @if(isset($accentColors) && count($accentColors) > 0)
            <div class="max-w-xs">
                <label for="accent_color" class="block text-sm font-medium text-gray-700 mb-1.5">Group color (optional)</label>
                <select name="accent_color" id="accent_color" class="form-input">
                    <option value="">Auto (assign a soft color)</option>
                    @foreach($accentColors as $key => $classes)
                        <option value="{{ $key }}" {{ old('accent_color') === $key ? 'selected' : '' }}>{{ ucfirst($key) }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">Soft color for the group card. Leave Auto to rotate colors.</p>
            </div>
            @endif

            @if(!empty($allowedDevicesOptions))
            <div class="max-w-xs">
                <label for="allowed_devices" class="block text-sm font-medium text-gray-700 mb-1.5">Allowed devices</label>
                <select name="allowed_devices" id="allowed_devices" class="form-input">
                    @foreach($allowedDevicesOptions as $value => $label)
                        <option value="{{ $value }}" {{ old('allowed_devices', 'desktop') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">Quizzes in this group can be taken on desktop only, mobile only, or both.</p>
            </div>
            @endif

            <div>
                <div class="flex items-center justify-between gap-4 mb-2">
                    <label class="block text-sm font-medium text-gray-700">Courses & Lecturers *</label>
                    <button type="button" id="add-course-row" class="shrink-0 inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 hover:border-gray-400">+ Add course</button>
                </div>
                <p class="text-sm text-gray-500 mb-3">For each course, select which lecturer teaches it in this class.</p>
                <div id="course-rows" class="space-y-3">
                    @php
                        $oldAssignments = old('course_assignments', [['course_id' => '', 'examiner_id' => '']]);
                        if (empty(array_filter($oldAssignments, fn($a) => !empty($a['course_id'])))) {
                            $oldAssignments = [['course_id' => '', 'examiner_id' => '']];
                        }
                    @endphp
                    @foreach($oldAssignments as $idx => $a)
                    <div class="course-row flex flex-wrap gap-3 items-end rounded-lg border border-gray-200 p-4 bg-gray-50/80">
                        <div class="flex-1 min-w-[180px]">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Course</label>
                            <select name="course_assignments[{{ $idx }}][course_id]" class="course-select form-input text-sm" required>
                                <option value="">— Select course —</option>
                                @foreach($courses as $c)
                                    <option value="{{ $c->id }}" data-examiners="{{ $c->examiners->toJson() }}" {{ ($a['course_id'] ?? '') == $c->id ? 'selected' : '' }}>{{ $c->name }} ({{ $c->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex-1 min-w-[180px]">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Lecturer</label>
                            <select name="course_assignments[{{ $idx }}][examiner_id]" class="examiner-select form-input text-sm" required>
                                <option value="">— Select lecturer —</option>
                                @foreach($courses as $c)
                                    @if(($a['course_id'] ?? '') == $c->id)
                                        @foreach($c->examiners as $ex)
                                            <option value="{{ $ex->id }}" {{ ($a['examiner_id'] ?? '') == $ex->id ? 'selected' : '' }}>{{ $ex->name ?: $ex->username }}</option>
                                        @endforeach
                                        @break
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <button type="button" class="remove-row shrink-0 inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 hover:border-gray-400">Remove</button>
                    </div>
                    @endforeach
                </div>
                @if($courses->isEmpty())
                <p class="mt-3 text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg p-3">No courses available. Create courses and assign lecturers in <a href="{{ route('dashboard.courses.index') }}" class="font-medium underline">Courses</a> first.</p>
                @endif
            </div>

            <div class="flex gap-3">
                @if($courses->isEmpty())
                    <button type="button" disabled class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium text-gray-900 bg-yellow-400 border border-yellow-600/30 shadow-sm opacity-60 cursor-not-allowed">Create Class Group</button>
                @else
                    <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium text-gray-900 bg-yellow-400 hover:bg-yellow-500 border border-yellow-600/30 shadow-sm">Create Class Group</button>
                @endif
                <a href="{{ route('dashboard.class-groups.index') }}" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium text-white bg-red-600 hover:bg-red-700 border border-red-700/30 shadow-sm">Cancel</a>
            </div>
        </form>
    </div>
</div>

@php
    $courseOptionsForJs = $courses->map(function ($c) {
        return [
            'id' => $c->id,
            'name' => $c->name,
            'code' => $c->code ?? '',
            'examiners' => $c->examiners->map(function ($e) {
                return ['id' => $e->id, 'name' => $e->name ?: $e->username];
            })->values()->all(),
        ];
    })->values()->all();
    $fallbackExaminersForJs = ($examiners ?? collect())->map(function ($e) {
        return ['id' => $e->id, 'name' => $e->name ?: $e->username];
    })->values()->all();
@endphp
@push('scripts')
<script>
(function() {
    var container = document.getElementById('course-rows');
    var addBtn = document.getElementById('add-course-row');
    var courseOptions = @json($courseOptionsForJs);
    var fallbackExaminers = @json($fallbackExaminersForJs);

    function examinersForCourse(courseId) {
        var course = courseOptions.find(function(c) { return String(c.id) === String(courseId); });
        if (course && course.examiners && course.examiners.length) {
            return course.examiners;
        }
        return fallbackExaminers;
    }

    function populateExaminerSelect(courseSelect, examinerSelect, selectedExaminerId) {
        var list = courseSelect.value ? examinersForCourse(courseSelect.value) : [];
        examinerSelect.innerHTML = '<option value="">— Select lecturer —</option>';
        list.forEach(function(ex) {
            var opt = document.createElement('option');
            opt.value = ex.id;
            opt.textContent = ex.name || '';
            if (selectedExaminerId && String(ex.id) === String(selectedExaminerId)) {
                opt.selected = true;
            }
            examinerSelect.appendChild(opt);
        });
    }

    function addRow() {
        var idx = container.querySelectorAll('.course-row').length;
        var courseOpts = courseOptions.map(function(c) {
            return '<option value="' + c.id + '">' + c.name + ' (' + c.code + ')</option>';
        }).join('');
        var html = '<div class="course-row flex flex-wrap gap-3 items-end rounded-lg border border-gray-200 p-4 bg-gray-50/80">' +
            '<div class="flex-1 min-w-[180px]"><label class="block text-sm font-medium text-gray-700 mb-1.5">Course</label>' +
            '<select name="course_assignments[' + idx + '][course_id]" class="course-select form-input text-sm" required>' +
            '<option value="">— Select course —</option>' + courseOpts + '</select></div>' +
            '<div class="flex-1 min-w-[180px]"><label class="block text-sm font-medium text-gray-700 mb-1.5">Lecturer</label>' +
            '<select name="course_assignments[' + idx + '][examiner_id]" class="examiner-select form-input text-sm" required>' +
            '<option value="">— Select lecturer —</option></select></div>' +
            '<button type="button" class="remove-row shrink-0 inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 hover:border-gray-400">Remove</button></div>';
        container.insertAdjacentHTML('beforeend', html);
        reindexRows();
        var newRow = container.querySelector('.course-row:last-child');
        if (newRow) {
            newRow.querySelector('.course-select').addEventListener('change', onCourseChange);
        }
    }

    function onCourseChange(e) {
        var row = e.target.closest('.course-row');
        populateExaminerSelect(e.target, row.querySelector('.examiner-select'), '');
    }

    function reindexRows() {
        container.querySelectorAll('.course-row').forEach(function(row, i) {
            row.querySelectorAll('[name^="course_assignments"]').forEach(function(inp) {
                inp.name = inp.name.replace(/course_assignments\[\d+\]/, 'course_assignments[' + i + ']');
            });
        });
    }

    if (addBtn) addBtn.addEventListener('click', addRow);

    container.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-row')) {
            var row = e.target.closest('.course-row');
            if (container.querySelectorAll('.course-row').length > 1) row.remove();
            reindexRows();
        }
    });

    container.querySelectorAll('.course-row').forEach(function(row) {
        var courseSelect = row.querySelector('.course-select');
        var examinerSelect = row.querySelector('.examiner-select');
        if (!courseSelect || !examinerSelect) return;
        courseSelect.addEventListener('change', onCourseChange);
        if (courseSelect.value) {
            populateExaminerSelect(courseSelect, examinerSelect, examinerSelect.value);
        }
    });
})();
</script>
@include('admin.class-groups.partials.client-validation-script')
<script>(function() {
    function bootClassGroupForm() {
        var form = document.getElementById('class-group-form');
        if (form && typeof window.initClassGroupFormValidation === 'function') {
            window.initClassGroupFormValidation(form);
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootClassGroupForm);
    } else {
        bootClassGroupForm();
    }
})();</script>
@endpush
@endsection
