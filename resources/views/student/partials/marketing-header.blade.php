@php
    $appName = $appName ?? \App\Models\Setting::getValue(\App\Models\Setting::KEY_APP_NAME, config('app.name', 'QuizSnap'));
    $showGetStarted = $showGetStarted ?? false;
    $showStudentLogin = $showStudentLogin ?? false;
@endphp
<header class="qs-header theme-header">
    <div class="qs-container qs-header-inner">
        @include('partials.brand-logo', [
            'appName' => $appName,
            'href' => route('student.landing'),
            'size' => 'md',
            'variant' => 'on-brand',
            'class' => 'qs-logo',
        ])

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
