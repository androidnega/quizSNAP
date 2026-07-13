@php
    $isSuperAdmin = session('admin_role') === 'super_admin';
    $isExaminer = session('admin_role') === 'examiner';
    $accent = $classGroup->accent_classes ?? ['bg' => 'bg-sky-50', 'border' => 'border-sky-200', 'text' => 'text-sky-800'];
    $courses = $visibleCourses ?? $classGroup->courses;
    $quizzesToShow = $visibleQuizzes ?? $classGroup->quizzes;
    $studentCount = $students->total();
@endphp
@extends('layouts.dashboard')

@section('title', $classGroup->display_name)
@section('dashboard_heading')
    <span class="inline-flex items-center gap-2 font-display tracking-tight">{{ $classGroup->display_name }}</span>
@endsection

@section('dashboard_content')
<div class="w-full space-y-5">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif

    {{-- Page header --}}
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <a href="{{ route('dashboard.class-groups.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-900 mb-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Class groups
            </a>
            <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-gray-900">{{ $classGroup->display_name }}</h1>
            <p class="mt-1 text-sm text-gray-500">Courses, quizzes, and student indices for this group.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if(!$isExaminer)
            <a href="{{ route('dashboard.class-groups.edit', $classGroup) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Edit</a>
            <form action="{{ route('dashboard.class-groups.destroy', $classGroup) }}" method="post" class="inline" onsubmit="return confirm('Delete class group \'{{ addslashes($classGroup->display_name) }}\'? This cannot be undone.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-medium text-rose-700 hover:bg-rose-100">Delete</button>
            </form>
            @endif
            @if($isExaminer)
                @if($studentCount > 0)
                    <a href="{{ route('dashboard.quizzes.create') }}?class_group_id={{ $classGroup->id }}" class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-primary-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2">Create quiz</a>
                @else
                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-primary-100 px-3.5 py-2 text-sm font-medium text-primary-700/70 cursor-not-allowed" title="Add at least one student first">Create quiz</span>
                @endif
            @endif
        </div>
    </div>

    @if($isExaminer && $studentCount === 0)
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <strong class="font-semibold">No students yet.</strong> Add indices on the student list before creating a quiz.
        </div>
    @endif

    @if(!$isExaminer)
        @php
            $allowedDevices = $allowedDevicesForForm ?? $classGroup->allowed_devices ?? \App\Models\ClassGroup::ALLOWED_DEVICES_DESKTOP;
            $allowedOptions = \App\Models\ClassGroup::allowedDevicesOptions();
        @endphp
        <form method="post" action="{{ route('dashboard.class-groups.allowed-devices.update', $classGroup) }}" class="flex flex-wrap items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-3">
            @csrf
            @method('PUT')
            <label for="allowed_devices" class="text-sm font-medium text-gray-700">Allowed devices</label>
            <select id="allowed_devices" name="allowed_devices" class="text-sm border-gray-200 rounded-lg px-2.5 py-1.5 focus:ring-primary-500 focus:border-primary-500 bg-gray-50">
                @foreach($allowedOptions as $value => $label)
                    <option value="{{ $value }}" {{ $allowedDevices === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="inline-flex items-center rounded-lg bg-gray-900 px-3 py-1.5 text-sm font-medium text-white hover:bg-black">Save</button>
        </form>
    @endif

    {{-- Stats row --}}
    <div class="grid grid-cols-3 gap-3">
        <div class="rounded-xl border border-gray-200 bg-white px-4 py-3">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Students</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums text-gray-900">{{ $studentCount }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white px-4 py-3">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Courses</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums text-gray-900">{{ $courses->count() }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white px-4 py-3">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Quizzes</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums text-gray-900">{{ $quizzesToShow->count() }}</p>
        </div>
    </div>

    {{-- Courses + Quizzes --}}
    <div class="grid md:grid-cols-2 gap-4">
        <section class="rounded-xl border border-gray-200 bg-white overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between gap-2">
                <h2 class="text-sm font-semibold text-gray-900">{{ $isExaminer ? 'Your courses' : 'Courses' }}</h2>
                <span class="text-xs text-gray-400 tabular-nums">{{ $courses->count() }}</span>
            </div>
            <div class="p-2">
                @if($courses->isEmpty())
                    <p class="px-2 py-6 text-sm text-gray-400 text-center">{{ $isExaminer ? 'No courses assigned to you in this group.' : 'No courses attached yet.' }}</p>
                @else
                    <ul class="divide-y divide-gray-50">
                        @foreach($courses as $c)
                            @php $lecturer = isset($c->pivot->examiner_id) && isset($examinersMap) ? ($examinersMap[$c->pivot->examiner_id] ?? null) : null; @endphp
                            <li class="flex items-center justify-between gap-3 px-2.5 py-2.5 rounded-lg hover:bg-gray-50">
                                <span class="text-sm text-gray-800 min-w-0 truncate">{{ $c->name }}</span>
                                @if($lecturer)
                                    <span class="shrink-0 text-[11px] px-2 py-0.5 rounded-md bg-gray-100 text-gray-600">{{ $lecturer->name ?: $lecturer->username }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between gap-2">
                <h2 class="text-sm font-semibold text-gray-900">Quizzes</h2>
                <span class="text-xs text-gray-400 tabular-nums">{{ $quizzesToShow->count() }}</span>
            </div>
            <div class="p-2">
                @if($quizzesToShow->isEmpty())
                    <p class="px-2 py-6 text-sm text-gray-400 text-center">No quizzes yet.</p>
                @else
                    <ul class="divide-y divide-gray-50">
                        @foreach($quizzesToShow->take(6) as $q)
                            <li>
                                <a href="{{ route('dashboard.quizzes.show', $q) }}" class="flex items-center justify-between gap-2 px-2.5 py-2.5 rounded-lg text-sm text-gray-800 hover:bg-gray-50 hover:text-primary-700">
                                    <span class="min-w-0 truncate">{{ $q->title }}</span>
                                    <svg class="w-3.5 h-3.5 text-gray-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            </li>
                        @endforeach
                        @if($quizzesToShow->count() > 6)
                            <li class="px-2.5 py-2 text-xs text-gray-400">+ {{ $quizzesToShow->count() - 6 }} more</li>
                        @endif
                    </ul>
                @endif
            </div>
        </section>
    </div>

    {{-- Students --}}
    <section class="rounded-xl border border-gray-200 bg-white overflow-hidden">
        <div class="px-4 py-4 sm:px-5 flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                <h2 class="text-sm font-semibold text-gray-900">Student indices</h2>
                <p class="mt-0.5 text-sm text-gray-500">Used for all quizzes in this group.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if($studentCount > 0)
                    <a href="{{ route('dashboard.class-groups.students.export.excel', $classGroup) }}" class="inline-flex items-center rounded-lg border border-gray-200 px-2.5 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50" download>Excel</a>
                    <a href="{{ route('dashboard.class-groups.students.export.pdf', $classGroup) }}" class="inline-flex items-center rounded-lg border border-gray-200 px-2.5 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50" download>PDF</a>
                @endif
                <a href="{{ route('dashboard.class-groups.students.index', $classGroup) }}" class="inline-flex items-center rounded-lg bg-gray-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-black">
                    Manage students
                </a>
            </div>
        </div>
        @if(!$isExaminer && $studentCount > 0)
        <div class="px-4 sm:px-5 pb-4 border-t border-gray-100 pt-3 flex flex-wrap items-center justify-between gap-2 bg-gray-50/50">
            <p class="text-xs text-gray-500">Clear all indices to re-upload a fresh list.</p>
            <form action="{{ route('dashboard.class-groups.students.clear', $classGroup) }}" method="post" class="inline" onsubmit="return confirm('Remove all {{ $studentCount }} index number(s) from this class group?');">
                @csrf
                <button type="submit" class="inline-flex items-center rounded-lg border border-rose-200 bg-white px-2.5 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-50">
                    Delete all indices
                </button>
            </form>
        </div>
        @endif
    </section>
</div>
@endsection
