<?php

use App\Http\Controllers\Student\QuizRulesController;
use App\Http\Controllers\Student\StudentLoginController;
use App\Http\Controllers\Student\TokenValidationController;
use App\Http\Controllers\MigrateSqliteToMysqlController;
use App\Http\Controllers\RunMigrationsAutoController;
use App\Http\Controllers\RunMigrationsController;
use App\Http\Controllers\Student\LandingPageController;
use App\Models\Quiz;
use App\Services\QuizLinkService;
use App\Models\QuizSession;
use App\Http\Controllers\Student\ProctoringCaptureController;
use App\Http\Controllers\Student\StudentQuizController;
use App\Http\Controllers\Student\PostQuizCaptureController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\ClassGroupController;
use App\Http\Controllers\Admin\ExamCalendarController;
use App\Http\Controllers\Admin\QuizManagementController;
use App\Http\Controllers\Admin\SettingsController;
use Illuminate\Support\Facades\Route;

// SQLite → MySQL migration (run via URL with secret key; no auth)
Route::get('/migrate-sqlite-to-mysql', MigrateSqliteToMysqlController::class)->name('migrate.sqlite.to.mysql');
// Run normal pending Laravel migrations via URL with secret key (no data import).
// Creates/updates all tables (e.g. exam_calendar). See MIGRATION-LINK.md.
// Link: https://quizsnap.online/migration?key=YOUR_SECRET (default key: QuizSnapMigrate2026Xp9k3m7)
Route::get('/run-migrations', RunMigrationsController::class)->name('migrate.run.pending');
Route::get('/migration', RunMigrationsController::class)->name('migration');
// Short link: https://quizsnap.online/themigration?key=YOUR_SECRET
Route::get('/themigration', RunMigrationsController::class)->name('migration.short');
// Run migrations only: https://quizsnap.online/migrationcode?key=YOUR_SECRET (same key as above)
Route::get('/migrationcode', RunMigrationsController::class)->name('migrationcode');
// No .env key: create storage/app/quizsnap-allow-migration then visit once (file deleted on success)
Route::get('/run-migrations-auto', RunMigrationsAutoController::class)->name('migrate.run.auto');
Route::get('/run-migartions-auto', RunMigrationsAutoController::class)->name('migrate.run.auto.typo');
// Timeout probe for /dashboard/quizzes; use: https://quizsnap.online/check-dashboard-quizzes-timeout?key=YOUR_SECRET
Route::get('/check-dashboard-quizzes-timeout', \App\Http\Controllers\CheckDashboardQuizzesTimeoutController::class)->name('check-dashboard-quizzes-timeout');
// Clear caches via URL (fix "pushed but not showing on live") – same key as run-migrations
// Use: https://YOUR-SITE.com/clear-cache?key=QuizSnapMigrate2026Xp9k3m7 (no .php)
Route::get('/clear-cache', \App\Http\Controllers\ClearCacheController::class)->name('clear.cache');
Route::get('/clear-cache.php', \App\Http\Controllers\ClearCacheController::class);
// Maintenance: list helper URLs (no key) – use to verify routes are deployed on live
Route::get('/maintenance', [\App\Http\Controllers\FixPullController::class, 'maintenance'])->name('maintenance');
Route::get('/maintenance/logs', \App\Http\Controllers\ServerLogsController::class)->name('maintenance.logs');
// Fix git pull merge error (same key as run-migrations)
Route::get('/fix-pull', [\App\Http\Controllers\FixPullController::class, 'show'])->name('fix.pull');
Route::get('/fix-pull/run', [\App\Http\Controllers\FixPullController::class, 'run'])->name('fix.pull.run');
Route::get('/fix-pull/script', [\App\Http\Controllers\FixPullController::class, 'script'])->name('fix.pull.script');
// Short link: quizsnap.online/thekey?key=YOUR_SECRET — runs fix-pull (no SSH needed)
Route::get('/thekey', [\App\Http\Controllers\FixPullController::class, 'run'])->name('fix.pull.thekey');

Route::post('/presence/ping', [\App\Http\Controllers\SitePresenceController::class, 'ping'])
    ->middleware('throttle:120,1')
    ->name('presence.ping');

// Public landing: single Start Quiz entry; no quiz list. If direct link has token (?t= or ?token=), go straight to rules.
Route::get('/', LandingPageController::class)->name('student.landing');

Route::get('/about-system', function () {
    $studentId = session('student_id');
    $student = $studentId ? \App\Models\Student::find($studentId) : null;
    return view('student.about-system', compact('student'));
})->name('about-system');

Route::post('/student/validate-token', [TokenValidationController::class, 'validateToken'])->name('student.validate-token');
Route::post('/student/start-quiz', function (\Illuminate\Http\Request $request) {
    $request->validate(['link' => 'required|string|max:2048']);
    $quizLinks = app(QuizLinkService::class);
    $token = $quizLinks->extractToken(trim($request->input('link', '')));
    if (! $token) {
        return redirect()->route('student.link-expired');
    }

    $student = $quizLinks->resolveStudent();
    $indexNumber = $quizLinks->normalizedIndex($student);
    $destination = $quizLinks->publicLinkDestination($token, $indexNumber);
    if (! $destination) {
        return redirect()->route('student.link-expired');
    }

    return redirect()->route($destination['route'], $destination['params']);
})->name('student.start-quiz');

Route::get('/student/link-expired', fn () => view('student.link-expired'))->name('student.link-expired');

Route::get('/quiz/rules', [QuizRulesController::class, 'show'])->name('student.rules.show');
Route::get('/t/{token}', [QuizRulesController::class, 'show'])->name('student.rules.show.quiz');
Route::get('/t/{token}/wait', [QuizRulesController::class, 'quizWillStart'])->name('student.quiz-will-start');
Route::get('/take/quiz/{token}/rules', fn ($token) => redirect()->route('student.rules.show.quiz', ['token' => $token], 301))->name('student.rules.show.quiz.legacy');
Route::post('/quiz/accept-rules', [QuizRulesController::class, 'accept'])->name('student.rules.accept');

Route::get('/student/login', [StudentLoginController::class, 'showLoginForm'])->name('student.login.form')->middleware('rules.accepted');
Route::post('/student/verify-index', [StudentLoginController::class, 'verifyIndex'])->name('student.verify.index')->middleware('rules.accepted');

Route::get('/student/proctoring/capture', [ProctoringCaptureController::class, 'show'])->name('student.proctoring.capture')->middleware('rules.accepted');
Route::post('/student/proctoring/capture', [ProctoringCaptureController::class, 'store'])->name('student.proctoring.store');

// Quiz routes: generous throttle so bursty auto-save / proctor / retries do not 429 mid-quiz; client batches requests
Route::middleware(['throttle:240,1'])->group(function () {
    Route::get('/quiz/ready', [StudentQuizController::class, 'ready'])->name('student.quiz.ready')->middleware('rules.accepted');
    // GET: in-app browsers / shared links / refresh must not 405; send users back to Ready (POST still starts the session)
    Route::get('/quiz/session/start', [StudentQuizController::class, 'startSessionRedirect'])->name('student.quiz.session.start.get')->middleware('rules.accepted');
    Route::post('/quiz/session/start', [StudentQuizController::class, 'startSession'])->name('student.quiz.session.start')->middleware('rules.accepted');
    Route::get('/quiz/take', [StudentQuizController::class, 'show'])->name('student.quiz.show')->middleware('rules.accepted');
    Route::get('/quiz/time-sync', [StudentQuizController::class, 'timeSync'])->name('student.quiz.time-sync')->middleware('rules.accepted');
    Route::post('/quiz/save-answer', [StudentQuizController::class, 'saveAnswer'])->name('student.quiz.save');
    Route::post('/quiz/save-answers', [StudentQuizController::class, 'saveAnswersBatch'])->name('student.quiz.save.batch');
    Route::post('/quiz/violation', [StudentQuizController::class, 'recordViolation'])->name('student.quiz.violation');
    Route::post('/quiz/violation/capture', [StudentQuizController::class, 'captureViolation'])->name('student.quiz.violation.capture');
    Route::post('/quiz/auto-submit', [StudentQuizController::class, 'autoSubmit'])->name('student.quiz.auto-submit');
    Route::post('/quiz/heartbeat', [StudentQuizController::class, 'heartbeat'])->name('student.quiz.heartbeat');
    Route::post('/quiz/finalize', [StudentQuizController::class, 'finalize'])->name('student.quiz.finalize');
    Route::get('/quiz/complete', [StudentQuizController::class, 'quizComplete'])->name('student.quiz.complete');
    Route::get('/quiz/result', [StudentQuizController::class, 'result'])->name('student.result');
});

Route::get('/quiz/final-photo', [PostQuizCaptureController::class, 'show'])->name('student.final-photo.capture')->middleware('rules.accepted');
Route::post('/quiz/post-face', [PostQuizCaptureController::class, 'store'])->name('student.post-face.store');

// Student account login (index → phone → OTP); no quiz link required
Route::get('/student/account/csrf-token', fn () => response()->json(['token' => csrf_token()]))->name('student.account.csrf-token');
Route::get('/student/account/login', [\App\Http\Controllers\Student\StudentAccountController::class, 'showLoginForm'])->name('student.account.login.form');
Route::post('/student/account/verify-index', [\App\Http\Controllers\Student\StudentAccountController::class, 'verifyIndex'])->middleware('throttle:student-auth')->name('student.account.verify-index');
Route::post('/student/account/save-email', [\App\Http\Controllers\Student\StudentAccountController::class, 'saveEmail'])->middleware('throttle:student-auth')->name('student.account.save-email');
Route::post('/student/account/setup-password', [\App\Http\Controllers\Student\StudentAccountController::class, 'setupPassword'])->middleware('throttle:student-auth')->name('student.account.setup-password');
Route::post('/student/account/setup-name', [\App\Http\Controllers\Student\StudentAccountController::class, 'setupName'])->middleware('throttle:student-auth')->name('student.account.setup-name');
Route::post('/student/account/send-otp', [\App\Http\Controllers\Student\StudentAccountController::class, 'sendOtp'])->middleware('throttle:student-otp-send')->name('student.account.send-otp');
Route::post('/student/account/send-onboarding-email-otp', [\App\Http\Controllers\Student\StudentAccountController::class, 'sendOnboardingEmailOtp'])->middleware('throttle:student-otp-send')->name('student.account.send-onboarding-email-otp');
Route::post('/student/account/verify-otp', [\App\Http\Controllers\Student\StudentAccountController::class, 'verifyOtp'])->middleware('throttle:student-auth')->name('student.account.verify-otp');
Route::post('/student/account/verify-password', [\App\Http\Controllers\Student\StudentAccountController::class, 'verifyPassword'])->middleware('throttle:student-auth')->name('student.account.verify-password');
Route::post('/student/account/request-otp-login', [\App\Http\Controllers\Student\StudentAccountController::class, 'requestOtpLogin'])->middleware('throttle:student-otp-send')->name('student.account.request-otp-login');
Route::post('/student/account/logout', [\App\Http\Controllers\Student\StudentAccountController::class, 'logout'])->name('student.account.logout');

Route::get('/student/password/forgot', [\App\Http\Controllers\Student\StudentPasswordResetController::class, 'showForgotForm'])->name('student.password.forgot');
Route::post('/student/password/forgot', [\App\Http\Controllers\Student\StudentPasswordResetController::class, 'sendResetLink'])->middleware('throttle:student-auth')->name('student.password.forgot.send');
Route::get('/student/password/reset/{token}', [\App\Http\Controllers\Student\StudentPasswordResetController::class, 'showResetForm'])->name('student.password.reset.form');
Route::post('/student/password/reset', [\App\Http\Controllers\Student\StudentPasswordResetController::class, 'reset'])->middleware('throttle:student-auth')->name('student.password.reset');

// Student passkey (WebAuthn) — students only; not for staff/admin
Route::post('/student/account/passkey/login-options', [\App\Http\Controllers\Student\StudentWebAuthnController::class, 'loginOptions'])->name('student.passkey.login-options');
Route::post('/student/account/passkey/login', [\App\Http\Controllers\Student\StudentWebAuthnController::class, 'login'])->name('student.passkey.login');
Route::post('/student/account/passkey/register-options', [\App\Http\Controllers\Student\StudentWebAuthnController::class, 'registerOptions'])->name('student.passkey.register-options');
Route::post('/student/account/passkey/register', [\App\Http\Controllers\Student\StudentWebAuthnController::class, 'register'])->name('student.passkey.register');

// Student level selection (when no level set)
Route::get('/student/select-level', [\App\Http\Controllers\Student\StudentLevelController::class, 'show'])
    ->middleware('dashboard.auth')
    ->name('student.select-level');
Route::post('/student/select-level', [\App\Http\Controllers\Student\StudentLevelController::class, 'store'])
    ->middleware('dashboard.auth')
    ->name('student.select-level.store');

// Legacy redirects: old student dashboard URLs → unified /dashboard
Route::get('/student/dashboard', fn () => redirect()->route('dashboard', [], 301))->name('student.dashboard.index.legacy');
Route::get('/student/dashboard/quizzes', fn () => redirect()->route('dashboard.my-quizzes', [], 301));
Route::get('/student/dashboard/profile', fn () => redirect()->route('dashboard.my-profile', [], 301));

// Unified dashboard: /dashboard (student or staff); student-only routes under /dashboard
Route::get('/dashboard', [\App\Http\Controllers\DashboardGatewayController::class, '__invoke'])->middleware(['dashboard.auth', 'student.has-level'])->name('dashboard');
Route::middleware(['dashboard.auth', 'student.auth', 'student.has-level'])->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/resume-quiz/{session}', [\App\Http\Controllers\Student\StudentDashboardController::class, 'resumeQuiz'])->name('resume-quiz');
    Route::get('/my-quizzes', [\App\Http\Controllers\Student\StudentDashboardController::class, 'quizzes'])->name('my-quizzes');
    Route::get('/my-quizzes/{sessionId}', [\App\Http\Controllers\Student\StudentDashboardController::class, 'showQuiz'])->name('my-quizzes.show');
    Route::get('/my-quizzes/{sessionId}/download-pdf', [\App\Http\Controllers\Student\StudentDashboardController::class, 'downloadPdf'])->name('my-quizzes.download-pdf');
    Route::get('/my-profile', [\App\Http\Controllers\Student\StudentDashboardController::class, 'profile'])->name('my-profile');
    Route::put('/my-profile', [\App\Http\Controllers\Student\StudentDashboardController::class, 'updateProfile'])->name('my-profile.update');
    Route::get('/course-materials', [\App\Http\Controllers\Student\StudentDashboardController::class, 'courseMaterials'])->name('course-materials');
    Route::get('/calendar', [\App\Http\Controllers\Student\StudentDashboardController::class, 'calendar'])->name('calendar');
    Route::get('/notifications', [\App\Http\Controllers\Student\StudentNotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [\App\Http\Controllers\Student\StudentNotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/{notificationId}/read', [\App\Http\Controllers\Student\StudentNotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/push-subscribe', [\App\Http\Controllers\Student\PushSubscribeController::class, 'store'])->name('push-subscribe');
});

// Staff login (rate-limited to 5 attempts per minute per IP+username)
Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AdminAuthController::class, 'login'])->middleware('throttle:login')->name('login.post');
Route::get('/password/forgot', [\App\Http\Controllers\Admin\StaffPasswordResetController::class, 'showForgotForm'])->name('password.forgot');
Route::post('/password/forgot', [\App\Http\Controllers\Admin\StaffPasswordResetController::class, 'sendResetLink'])->name('password.forgot.send');
Route::get('/password/reset/{token}', [\App\Http\Controllers\Admin\StaffPasswordResetController::class, 'showResetForm'])->name('password.reset.form');
Route::post('/password/reset', [\App\Http\Controllers\Admin\StaffPasswordResetController::class, 'reset'])->name('password.reset');

// Staff dashboard and all staff pages under /dashboard (admin + examiner)
Route::middleware('admin.auth')->group(function () {
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
    Route::get('/logout', [AdminAuthController::class, 'logout'])->name('logout.get');
    Route::get('/dashboard/live-stats', [AdminDashboardController::class, 'liveStats'])->name('dashboard.live-stats');
    // GET /dashboard is handled by DashboardGatewayController (unified)

        Route::prefix('dashboard')->name('dashboard.')->middleware('block.superadmin.coordinator')->group(function () {
        // Minimal ping (same auth/session as quizzes) — if this is fast but /dashboard/quizzes times out, bottleneck is controller/view
        Route::get('/ping', fn () => response('OK', 200, ['Content-Type' => 'text/plain; charset=utf-8']))->name('ping');
        // Profile — both roles
        Route::get('/profile', [\App\Http\Controllers\Admin\StaffProfileController::class, 'show'])->name('profile.show');
        Route::put('/profile', [\App\Http\Controllers\Admin\StaffProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/avatar', [\App\Http\Controllers\Admin\StaffProfileController::class, 'updateAvatar'])->name('profile.avatar');
        Route::get('/profile/password', [\App\Http\Controllers\Admin\StaffProfileController::class, 'password'])->name('profile.password');
        Route::put('/profile/password', [\App\Http\Controllers\Admin\StaffProfileController::class, 'updatePassword'])->name('profile.password.update');

        // Study guide: super admin only — time-limited signed URL (no DB logging)
        Route::get('/study-guide/{classGroup}', [\App\Http\Controllers\Admin\StudyGuideController::class, '__invoke'])
            ->name('study-guide.show')
            ->middleware('signed');

        // Class groups — both (policy controls create/edit/delete)
        Route::get('/class-groups', [ClassGroupController::class, 'index'])->name('class-groups.index');
        Route::get('/class-groups/create', [ClassGroupController::class, 'create'])->name('class-groups.create');
        Route::post('/class-groups', [ClassGroupController::class, 'store'])->name('class-groups.store');
        Route::get('/class-groups/{classGroup}', [ClassGroupController::class, 'show'])->name('class-groups.show');
        Route::get('/class-groups/{classGroup}/edit', [ClassGroupController::class, 'edit'])->name('class-groups.edit');
        Route::put('/class-groups/{classGroup}', [ClassGroupController::class, 'update'])->name('class-groups.update');
        Route::put('/class-groups/{classGroup}/allowed-devices', [ClassGroupController::class, 'updateAllowedDevices'])->name('class-groups.allowed-devices.update');
        Route::delete('/class-groups/{classGroup}', [ClassGroupController::class, 'destroy'])->name('class-groups.destroy');
        Route::get('/class-groups/{classGroup}/students', [ClassGroupController::class, 'studentsIndex'])->name('class-groups.students.index');
        Route::get('/class-groups/{classGroup}/students/export/excel', [ClassGroupController::class, 'exportStudentsExcel'])->name('class-groups.students.export.excel');
        Route::get('/class-groups/{classGroup}/students/export/pdf', [ClassGroupController::class, 'exportStudentsPdf'])->name('class-groups.students.export.pdf');
        // Keep classGroup in URL for readability, but resolve from student to avoid stale nested URL 404s.
        Route::get('/class-groups/{classGroupId}/students/{student}', [ClassGroupController::class, 'showStudent'])->name('class-groups.students.show');
        Route::get('/class-groups/{classGroupId}/students/{student}/edit', [ClassGroupController::class, 'editStudent'])->name('class-groups.students.edit');
        Route::post('/class-groups/{classGroup}/students', [ClassGroupController::class, 'addStudent'])->name('class-groups.students.add');
        Route::post('/class-groups/{classGroup}/students/upload', [ClassGroupController::class, 'uploadStudents'])->name('class-groups.students.upload');
        Route::get('/class-groups/{classGroup}/students/upload/{uploadId}/status', [ClassGroupController::class, 'uploadStudentsStatus'])->name('class-groups.students.upload.status');
        Route::post('/class-groups/{classGroup}/students/upload/{uploadId}/duplicates', [ClassGroupController::class, 'resolveUploadDuplicates'])->name('class-groups.students.upload.duplicates');
        Route::post('/class-groups/{classGroup}/students/clear', [ClassGroupController::class, 'clearStudents'])->name('class-groups.students.clear');
        Route::delete('/class-groups/{classGroup}/students/bulk-destroy', [ClassGroupController::class, 'bulkDestroyStudents'])->name('class-groups.students.bulk-destroy');
        Route::put('/class-groups/{classGroupId}/students/{student}', [ClassGroupController::class, 'updateStudent'])->name('class-groups.students.update');
        Route::delete('/class-groups/{classGroupId}/students/{student}', [ClassGroupController::class, 'destroyStudent'])->name('class-groups.students.destroy');
        Route::delete('/class-groups/{classGroupId}/students/{student}/phone', [ClassGroupController::class, 'removeStudentPhone'])->name('class-groups.students.remove-phone');
        Route::post('/class-groups/{classGroupId}/students/{student}/fallback-code', [ClassGroupController::class, 'generateFallbackCode'])->name('class-groups.students.fallback-code');

        // Exam calendar (midsem & end-of-semester) — coordinator assigns by class group; students see on dashboard
        Route::get('/exam-calendar', [ExamCalendarController::class, 'index'])->name('exam-calendar.index');
        Route::get('/exam-calendar/create', [ExamCalendarController::class, 'create'])->name('exam-calendar.create');
        Route::post('/exam-calendar', [ExamCalendarController::class, 'store'])->name('exam-calendar.store');
        Route::get('/exam-calendar/{examCalendar}/edit', [ExamCalendarController::class, 'edit'])->name('exam-calendar.edit');
        Route::put('/exam-calendar/{examCalendar}', [ExamCalendarController::class, 'update'])->name('exam-calendar.update');
        Route::delete('/exam-calendar/{examCalendar}', [ExamCalendarController::class, 'destroy'])->name('exam-calendar.destroy');

        Route::get('/student-notifications/create', [\App\Http\Controllers\Admin\StaffStudentNotificationController::class, 'create'])->name('student-notifications.create');
        Route::post('/student-notifications', [\App\Http\Controllers\Admin\StaffStudentNotificationController::class, 'store'])->name('student-notifications.store');

        // Quiz session detail — all staff (examiners + super admins) so session/student data always shows
        // Keep quiz ID in URL for readability, but resolve by quizSession in controller
        // so migrated/stale links do not hard-404 when quiz IDs changed.
        Route::get('/quizzes/{quizId}/sessions/{quizSession}', [QuizManagementController::class, 'showSession'])->name('quizzes.sessions.show');
        Route::get('/proctoring-media/{path}', [\App\Http\Controllers\Admin\ProctoringMediaController::class, 'show'])->where('path', '.*')->name('proctoring-media');
        Route::post('/quizzes/{quizId}/sessions/{quizSession}/reset-ip', [QuizManagementController::class, 'resetSessionIp'])->name('quizzes.sessions.reset-ip');
        Route::post('/quizzes/{quizId}/sessions/{quizSession}/clear-withheld', [QuizManagementController::class, 'clearWithheldResult'])->name('quizzes.sessions.clear-withheld');
        Route::post('/quizzes/{quizId}/sessions/{quizSession}/kill', [QuizManagementController::class, 'killSession'])->name('quizzes.sessions.kill');
        // Quizzes — examiner only
        Route::middleware('examiner.only')->group(function () {
            Route::get('/quizzes-ping', fn () => response('OK', 200, ['Content-Type' => 'text/plain; charset=utf-8']))->name('quizzes.ping');
            Route::get('/students', fn () => redirect()->route('dashboard.class-groups.index', [], 301))->name('students.index');
            Route::get('/attendance', fn () => redirect()->route('dashboard.class-groups.index', [], 301))->name('attendance.index');
            Route::get('/quizzes', [QuizManagementController::class, 'index'])->name('quizzes.index');
            Route::get('/quizzes/create', [QuizManagementController::class, 'create'])->name('quizzes.create');
            Route::post('/quizzes/validate-ai-json', [QuizManagementController::class, 'validateAiJson'])->name('quizzes.validate-ai-json');
            Route::post('/quizzes/extract-topics', [QuizManagementController::class, 'extractTopics'])->name('quizzes.extract-topics');
            Route::post('/quizzes', [QuizManagementController::class, 'store'])->name('quizzes.store');
            Route::get('/quizzes/{quiz}', [QuizManagementController::class, 'show'])->name('quizzes.show');
            Route::get('/quizzes/{quiz}/ai-generation-status', [QuizManagementController::class, 'aiGenerationStatus'])->name('quizzes.ai-generation-status');
            Route::get('/quizzes/{quiz}/edit', [QuizManagementController::class, 'edit'])->name('quizzes.edit');
            Route::put('/quizzes/{quiz}', [QuizManagementController::class, 'update'])->name('quizzes.update');
            Route::post('/quizzes/{quiz}/ai-generate/batch', [QuizManagementController::class, 'generateBatch'])->name('quizzes.ai-generate.batch');
            Route::post('/quizzes/{quiz}/ai-generate', [QuizManagementController::class, 'generateBatchAi'])->name('quizzes.ai-generate');
            Route::post('/quizzes/{quiz}/ai-generate/gemini', [QuizManagementController::class, 'generateBatchGemini'])->name('quizzes.ai-generate.gemini');
            Route::get('/quizzes/{quiz}/ai-generate/batch', function (Quiz $quiz) {
                return redirect()->route('dashboard.quizzes.show', $quiz)
                    ->with('info', 'Use the "Generate questions with AI" button on this page.');
            })->name('quizzes.ai-generate.batch.get');
            Route::get('/quizzes/{quiz}/scores', [QuizManagementController::class, 'scores'])->name('quizzes.scores');
            Route::get('/quizzes/{quiz}/scores/export/pdf/preview', [QuizManagementController::class, 'exportScoresPdf'])->name('quizzes.scores.export.pdf.preview');
            Route::get('/quizzes/{quiz}/scores/export/pdf', [QuizManagementController::class, 'exportScoresPdf'])->name('quizzes.scores.export.pdf');
            Route::get('/quizzes/{quiz}/scores/export/excel', [QuizManagementController::class, 'exportScoresExcel'])->name('quizzes.scores.export.excel');
            Route::get('/quizzes/{quiz}/scores/export', [QuizManagementController::class, 'exportScores'])->name('quizzes.scores.export');
            Route::get('/quizzes/{quiz}/analytics/export/pdf/preview', [QuizManagementController::class, 'exportAnalyticsPdf'])->name('quizzes.analytics.export.pdf.preview');
            Route::get('/quizzes/{quiz}/analytics/export/pdf', [QuizManagementController::class, 'exportAnalyticsPdf'])->name('quizzes.analytics.export.pdf');
            Route::get('/quizzes/{quiz}/violations/export', [QuizManagementController::class, 'exportViolations'])->name('quizzes.violations.export');
            Route::get('/quizzes/{quiz}/questions/export/txt', [QuizManagementController::class, 'exportQuestionsTxt'])->name('quizzes.questions.export.txt');
            Route::get('/quizzes/{quiz}/questions/export/full-pool.txt', [QuizManagementController::class, 'exportFullQuestionPoolTxt'])->name('quizzes.questions.export.full-pool-txt');
            Route::post('/quizzes/{quiz}/question-pools/{pool}/approve', [QuizManagementController::class, 'approvePool'])->name('quizzes.pool.approve');
            Route::get('/quizzes/{quiz}/question-pools/{pool}/edit', [QuizManagementController::class, 'editPool'])->name('quizzes.pool.edit');
            Route::put('/quizzes/{quiz}/question-pools/{pool}', [QuizManagementController::class, 'updatePool'])->name('quizzes.pool.update');
            Route::delete('/quizzes/{quiz}/question-pools/{pool}', [QuizManagementController::class, 'rejectPool'])->name('quizzes.pool.reject');
            Route::post('/quizzes/{quiz}/approve-all-pool', [QuizManagementController::class, 'approveAllPool'])->name('quizzes.approve-all-pool');
            Route::post('/quizzes/{quiz}/publish', [QuizManagementController::class, 'publish'])->name('quizzes.publish');
            Route::post('/quizzes/{quiz}/unpublish', [QuizManagementController::class, 'unpublish'])->name('quizzes.unpublish');
            Route::post('/quizzes/{quiz}/end', [QuizManagementController::class, 'endQuiz'])->name('quizzes.end');
            Route::post('/quizzes/{quiz}/extend-time', [QuizManagementController::class, 'extendTime'])->name('quizzes.extend-time');
            Route::post('/quizzes/{quiz}/sessions/clear-range', [QuizManagementController::class, 'clearSessionsByRange'])->name('quizzes.sessions.clear-range');
            Route::get('/quizzes/{quiz}/questions/{question}/edit', [QuizManagementController::class, 'editQuestion'])->name('quizzes.questions.edit');
            Route::put('/quizzes/{quiz}/questions/{question}', [QuizManagementController::class, 'updateQuestion'])->name('quizzes.questions.update');
            Route::delete('/quizzes/{quiz}/questions/{question}', [QuizManagementController::class, 'destroyQuestion'])->name('quizzes.questions.destroy');
            Route::delete('/quizzes/{quiz}', [QuizManagementController::class, 'destroy'])->name('quizzes.destroy');
        });

        // Courses: Super Admin always, Examiner when setting allows
        Route::middleware('course.creation')->group(function () {
            Route::get('/courses', [\App\Http\Controllers\Admin\CourseController::class, 'index'])->name('courses.index');
            Route::get('/courses/create', [\App\Http\Controllers\Admin\CourseController::class, 'create'])->name('courses.create');
            Route::post('/courses', [\App\Http\Controllers\Admin\CourseController::class, 'store'])->name('courses.store');
            Route::get('/courses/{course}/edit', [\App\Http\Controllers\Admin\CourseController::class, 'edit'])->name('courses.edit');
            Route::put('/courses/{course}', [\App\Http\Controllers\Admin\CourseController::class, 'update'])->name('courses.update');
            Route::post('/courses/{course}/archive', [\App\Http\Controllers\Admin\CourseController::class, 'archive'])->name('courses.archive');
            Route::post('/courses/{course}/unarchive', [\App\Http\Controllers\Admin\CourseController::class, 'unarchive'])->name('courses.unarchive');
            Route::delete('/courses/{course}', [\App\Http\Controllers\Admin\CourseController::class, 'destroy'])->name('courses.destroy');
            Route::delete('/courses', [\App\Http\Controllers\Admin\CourseController::class, 'bulkDestroy'])->name('courses.bulk-destroy');
        });

        // Examiners can edit their own profile (faculty/department)
        Route::get('/users/{user}/edit', [\App\Http\Controllers\Admin\UserManagementController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [\App\Http\Controllers\Admin\UserManagementController::class, 'update'])->name('users.update');

        // Super Admin and Coordinator: view examiners and assign AI tokens
        Route::middleware('staff.tokens')->group(function () {
            Route::get('/users', [\App\Http\Controllers\Admin\UserManagementController::class, 'index'])->name('users.index');
            Route::post('/users/update-ai-tokens', [\App\Http\Controllers\Admin\UserManagementController::class, 'updateAiTokens'])->name('users.update-ai-tokens');
        });
        
        // QuizSnap: cascading selects for assessment creation
        Route::get('/quizsnap/courses', [\App\Http\Controllers\Admin\QuizSnapApiController::class, 'coursesByContext'])->name('quizsnap.courses');
        Route::get('/quizsnap/academic-classes', [\App\Http\Controllers\Admin\QuizSnapApiController::class, 'academicClassesByContext'])->name('quizsnap.academic-classes');

        // Faculty/Department AJAX endpoints (for examiners editing their profile)
        Route::get('/faculties/{faculty}/departments', [\App\Http\Controllers\Admin\DepartmentController::class, 'byFaculty'])->name('departments.by-faculty');
        Route::get('/institutions/{institution}/faculties', [\App\Http\Controllers\Admin\FacultyController::class, 'byInstitution'])->name('faculties.by-institution');

        // Coordinator only: academic structure for QuizSnap
        Route::middleware('coordinator.only')->prefix('coordinators')->name('coordinators.')->group(function () {
            Route::resource('academic-years', \App\Http\Controllers\Admin\AcademicYearController::class)->parameters(['academic-years' => 'academicYear']);
            Route::resource('quiz-categories', \App\Http\Controllers\Admin\QuizCategoryController::class)->parameters(['quiz-categories' => 'quizCategory']);
            Route::resource('semesters', \App\Http\Controllers\Admin\SemesterController::class);
            Route::resource('academic-classes', \App\Http\Controllers\Admin\AcademicClassController::class)->parameters(['academic-classes' => 'academicClass']);
            Route::get('/student-levels', [\App\Http\Controllers\Admin\StudentLevelController::class, 'index'])->name('student-levels.index');
            Route::post('/student-levels', [\App\Http\Controllers\Admin\StudentLevelController::class, 'store'])->name('student-levels.store');
            Route::put('/student-levels/{studentLevel}', [\App\Http\Controllers\Admin\StudentLevelController::class, 'update'])->name('student-levels.update');
            Route::delete('/student-levels/{studentLevel}', [\App\Http\Controllers\Admin\StudentLevelController::class, 'destroy'])->name('student-levels.destroy');
        });

        // Super Admin only: institutions, users, settings, system reset
        Route::middleware('admin.role')->group(function () {
            Route::get('/institutions', [\App\Http\Controllers\Admin\InstitutionController::class, 'index'])->name('institutions.index');
            Route::get('/institutions/create', [\App\Http\Controllers\Admin\InstitutionController::class, 'create'])->name('institutions.create');
            Route::post('/institutions', [\App\Http\Controllers\Admin\InstitutionController::class, 'store'])->name('institutions.store');
            Route::get('/institutions/{institution}/edit', [\App\Http\Controllers\Admin\InstitutionController::class, 'edit'])->name('institutions.edit');
            Route::put('/institutions/{institution}', [\App\Http\Controllers\Admin\InstitutionController::class, 'update'])->name('institutions.update');
            // Faculty and Department management
            Route::post('/faculties', [\App\Http\Controllers\Admin\FacultyController::class, 'store'])->name('faculties.store');
            Route::put('/faculties/{faculty}', [\App\Http\Controllers\Admin\FacultyController::class, 'update'])->name('faculties.update');
            Route::delete('/faculties/{faculty}', [\App\Http\Controllers\Admin\FacultyController::class, 'destroy'])->name('faculties.destroy');
            Route::post('/departments', [\App\Http\Controllers\Admin\DepartmentController::class, 'store'])->name('departments.store');
            Route::put('/departments/{department}', [\App\Http\Controllers\Admin\DepartmentController::class, 'update'])->name('departments.update');
            Route::delete('/departments/{department}', [\App\Http\Controllers\Admin\DepartmentController::class, 'destroy'])->name('departments.destroy');
            Route::post('/settings/update-mode', [SettingsController::class, 'toggleUpdateMode'])->name('settings.update-mode');
            Route::post('/settings/update-estimated-end', [SettingsController::class, 'setUpdateEstimatedEnd'])->name('settings.update-estimated-end');
            Route::get('/system/reset', [\App\Http\Controllers\Admin\SystemResetController::class, 'index'])->name('system.reset.index');
            Route::post('/system/reset', [\App\Http\Controllers\Admin\SystemResetController::class, 'reset'])->name('system.reset');
            Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
            Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
            Route::post('/settings/study-guide/unlock', [SettingsController::class, 'studyGuideUnlock'])->name('settings.study-guide.unlock');
            if (! app()->environment('production')) {
            }
            Route::get('/settings/supabase-test', [SettingsController::class, 'supabaseTest'])->name('settings.supabase-test');
            Route::post('/settings/otp-test', [SettingsController::class, 'otpTest'])->name('settings.otp-test');
            Route::get('/settings/otp-balance', [SettingsController::class, 'otpBalance'])->name('settings.otp-balance');
            Route::post('/settings/email-test', [SettingsController::class, 'emailTest'])->name('settings.email-test');
            Route::post('/settings/password-reset-test', [SettingsController::class, 'passwordResetTest'])->name('settings.password-reset-test');
            Route::get('/student-levels', [\App\Http\Controllers\Admin\StudentLevelController::class, 'index'])->name('student-levels.index');
            Route::post('/student-levels', [\App\Http\Controllers\Admin\StudentLevelController::class, 'store'])->name('student-levels.store');
            Route::put('/student-levels/{studentLevel}', [\App\Http\Controllers\Admin\StudentLevelController::class, 'update'])->name('student-levels.update');
            Route::delete('/student-levels/{studentLevel}', [\App\Http\Controllers\Admin\StudentLevelController::class, 'destroy'])->name('student-levels.destroy');
            Route::get('/users/create', [\App\Http\Controllers\Admin\UserManagementController::class, 'create'])->name('users.create');
            Route::post('/users', [\App\Http\Controllers\Admin\UserManagementController::class, 'store'])->name('users.store');
            Route::get('/users/{user}/view-password', [\App\Http\Controllers\Admin\UserManagementController::class, 'showPasswordForm'])->name('users.view-password-form');
            Route::post('/users/{user}/view-password', [\App\Http\Controllers\Admin\UserManagementController::class, 'viewPassword'])->name('users.view-password');
            Route::post('/users/{user}/reset-password', [\App\Http\Controllers\Admin\UserManagementController::class, 'resetPassword'])->name('users.reset-password');
            Route::post('/users/update-sms', [\App\Http\Controllers\Admin\UserManagementController::class, 'updateSms'])->name('users.update-sms');
            Route::post('/users/{user}/revoke', [\App\Http\Controllers\Admin\UserManagementController::class, 'revoke'])->name('users.revoke');
            Route::delete('/users/{user}', [\App\Http\Controllers\Admin\UserManagementController::class, 'destroy'])->name('users.destroy');
        });
    });
});

