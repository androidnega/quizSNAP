@extends('layouts.dashboard')

@section('title', 'Student details — ' . $classGroup->display_name)
@section('dashboard_heading')
    <span class="inline-flex items-center gap-2"><i class="fas fa-user-graduate text-primary-600"></i> Student details</span>
@endsection

@section('dashboard_content')
<div class="w-full">
    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-error mb-4">{{ session('error') }}</div>
    @endif

    @if(session('temp_password'))
    <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-4">
        <p class="text-sm font-semibold text-amber-900 mb-2">Temporary password (copy now)</p>
        <div class="flex flex-wrap items-center gap-2">
            <input
                type="text"
                id="student-temp-password"
                value="{{ session('temp_password') }}"
                readonly
                class="flex-1 min-w-[12rem] font-mono text-sm bg-white border border-amber-300 rounded-lg px-3 py-2 text-gray-900"
            >
            <button
                type="button"
                onclick="(function(){var el=document.getElementById('student-temp-password');if(!el)return;navigator.clipboard.writeText(el.value).then(function(){var b=document.getElementById('copy-temp-pw-btn');if(b){b.textContent='Copied';setTimeout(function(){b.textContent='Copy';},1500);}});})()"
                id="copy-temp-pw-btn"
                class="inline-flex items-center rounded-lg bg-amber-600 px-3 py-2 text-sm font-semibold text-white hover:bg-amber-700"
            >Copy</button>
        </div>
        <p class="text-xs text-amber-800 mt-2">Share this with the student securely. Ask them to sign in with their index and this password, then change it if needed.</p>
    </div>
    @endif

    <a href="{{ route('dashboard.class-groups.students.index', $classGroup) }}" class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-primary-600 mb-6">
        <i class="fas fa-arrow-left"></i> Back to student list
    </a>

    <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between gap-3 flex-wrap">
            <h2 class="text-lg font-semibold text-gray-900">Student information</h2>
            <div class="flex gap-2 flex-wrap">
                @can('update', $classGroup)
                <a href="{{ route('dashboard.class-groups.students.edit', [$classGroup, $student]) }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-pen mr-1"></i> Edit
                </a>
                @if($phone && ($isSuperAdmin ?? false))
                <form action="{{ route('dashboard.class-groups.students.remove-phone', [$classGroup, $student]) }}" method="post" class="inline" onsubmit="return confirm('Remove phone number? Student will be asked for a new phone on next login.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm" style="background-color: #f59e0b; color: white;">
                        <i class="fas fa-phone-slash mr-1"></i> Remove phone
                    </button>
                </form>
                @endif
                @endcan
                @if(!empty($canResetStudentPassword))
                <form action="{{ route('dashboard.class-groups.students.reset-password', [$classGroup, $student]) }}" method="post" class="inline" onsubmit="return confirm('Reset this student\'s password? A temporary password will be shown once.');">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-primary" style="background-color: #2563eb;">
                        <i class="fas fa-key mr-1"></i> Reset password
                    </button>
                </form>
                @endif
            </div>
        </div>
        
        <div class="px-6 py-5 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Index number</label>
                    <p class="text-base font-mono font-semibold text-gray-900">{{ $student->index_number }}</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Class group</label>
                    <p class="text-base text-gray-900">{{ $classGroup->display_name }}</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Name</label>
                    <p class="text-base text-gray-900">{{ $displayName }}</p>
                    @if($student->student_name && $studentAccount && $studentAccount->student_name && $student->student_name !== $studentAccount->student_name)
                        <p class="text-xs text-gray-500 mt-1">Group record: {{ $student->student_name }}</p>
                    @endif
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Phone number</label>
                    <p class="text-base text-gray-900">{{ $phone ?: '—' }}</p>
                    @if(!$phone)
                        <p class="text-xs text-gray-500 mt-1">Student has not added a phone number yet</p>
                    @endif
                </div>
            </div>

            @if($studentAccount)
            <div class="pt-4 border-t border-gray-200">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Account activity</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">First name</label>
                        <p class="text-sm text-gray-900">{{ $studentAccount->first_name ?: '—' }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Last name</label>
                        <p class="text-sm text-gray-900">{{ $studentAccount->last_name ?: '—' }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Email</label>
                        <p class="text-sm text-gray-900">{{ $studentAccount->email ?: '—' }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Account created</label>
                        <p class="text-sm text-gray-900">{{ $studentAccount->created_at ? $studentAccount->created_at->format('M j, Y') : '—' }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Password set</label>
                        <p class="text-sm text-gray-900">{{ !empty($hasStudentPassword) ? 'Yes' : 'No' }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Passwords changed</label>
                        <p class="text-2xl font-bold text-gray-900 tabular-nums">{{ $passwordChangeCount ?? 0 }}</p>
                        @if(!empty($passwordChangedAt))
                            <p class="text-xs text-gray-500 mt-1">Last change {{ $passwordChangedAt->format('M j, Y g:i A') }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-gray-200">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Quiz history</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Quizzes taken</label>
                        <p class="text-2xl font-bold text-gray-900">{{ $quizzesCount }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Average score</label>
                        <p class="text-2xl font-bold text-gray-900">{{ $averageScore ? number_format($averageScore, 1) . '%' : '—' }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Last quiz</label>
                        <p class="text-sm text-gray-900">{{ $lastQuizDate ?: '—' }}</p>
                    </div>
                </div>
            </div>
            @else
            <div class="pt-4 border-t border-gray-200">
                <p class="text-sm text-gray-500 italic">This student has not logged in or taken any quizzes yet.</p>
                @if(!empty($canResetStudentPassword))
                    <p class="text-xs text-gray-500 mt-2">You can still reset/set a password — an account will be created if needed.</p>
                @endif
            </div>
            @endif
        </div>

        @can('update', $classGroup)
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex items-center justify-between">
            <form action="{{ route('dashboard.class-groups.students.destroy', [$classGroup, $student]) }}" method="post" onsubmit="return confirm('Remove this student from the class group? This will not delete their quiz history.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-sm text-danger-600 hover:text-danger-800 font-medium">
                    <i class="fas fa-trash-alt mr-1"></i> Remove from class group
                </button>
            </form>
            <a href="{{ route('dashboard.class-groups.students.edit', [$classGroup, $student]) }}" class="text-sm text-primary-600 hover:text-primary-800 font-medium">
                <i class="fas fa-pen mr-1"></i> Edit details
            </a>
        </div>
        @endcan
    </div>
</div>
@endsection
