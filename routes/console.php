<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auto-publish quizzes when their start time arrives
Schedule::command('quizzes:auto-publish')->everyMinute();

// Auto-end quizzes when Ends At is reached or when all students have participated
Schedule::command('quizzes:auto-end')->everyMinute();

// Auto-submit quiz sessions scheduled via auto_submit_after (legacy tab-switch delay)
Schedule::command('quiz-sessions:auto-submit-tab-switch')->everyMinute();

// Flush deferred quiz heartbeats from Redis → MySQL (proctor-feed / heartbeat at scale)
Schedule::command('quiz:flush-heartbeats')->everyThirtySeconds();

// Student Level Promotion: Automatically promote students every September 1st
// Creates new academic year, promotes all students to next level, resets semester to 1
Schedule::command('students:promote-levels')->yearlyOn(9, 1, '00:00');

// Exam reminder: send browser push notifications ~1 hour before scheduled exams
Schedule::command('exam:send-reminder-push')->everyTenMinutes();

// Violation images: delete server-stored images older than retention days (primary setting)
Schedule::command('violations:clean-old-images')->daily();
