@php
    $appName = $appName ?? \App\Models\Setting::getValue(\App\Models\Setting::KEY_APP_NAME, config('app.name', 'QuizSnap'));
    $showGetStarted = $showGetStarted ?? false;
    $showStudentLogin = $showStudentLogin ?? false;
@endphp
<header class="qs-header">
    <div class="qs-container qs-header-inner">
        <a href="{{ route('student.landing') }}" class="qs-logo" aria-label="{{ $appName }} home">
            <span class="qs-logo-mark" aria-hidden="true">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 14v7"/>
                </svg>
            </span>
            <span class="qs-logo-text">{{ $appName }}</span>
        </a>

        <div class="qs-header-right">
            @if(isset($student) && $student)
                <a href="{{ route('dashboard') }}" class="qs-btn-get-started">Dashboard</a>
            @elseif($showGetStarted)
                <button type="button" class="qs-btn-get-started" id="header-get-started-btn">Get started</button>
            @elseif($showStudentLogin)
                <a href="{{ route('student.account.login.form') }}" class="qs-btn-get-started">Student login</a>
            @endif
        </div>
    </div>
</header>
