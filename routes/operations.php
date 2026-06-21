<?php

use App\Http\Controllers\Admin\Operations\OperationsAnalyticsController;
use App\Http\Controllers\Admin\Operations\OperationsAttendanceController;
use App\Http\Controllers\Admin\Operations\OperationsIncidentController;
use App\Http\Controllers\Admin\Operations\OperationsLiveExamController;
use App\Http\Controllers\Admin\Operations\OperationsOverviewController;
use App\Http\Controllers\Admin\Operations\OperationsProctoringController;
use App\Http\Controllers\Admin\Operations\OperationsStudentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['admin.auth', 'operations.access'])->prefix('dashboard/operations')->name('dashboard.operations.')->group(function () {
    Route::get('/', [OperationsOverviewController::class, 'index'])->name('index');
    Route::get('/live', [OperationsOverviewController::class, 'live'])->name('live');

    Route::get('/wallboard', [OperationsOverviewController::class, 'wallboard'])->name('wallboard.index');
    Route::get('/wallboard/live', [OperationsOverviewController::class, 'wallboardLive'])->name('wallboard.live');

    Route::get('/live-exams', [OperationsLiveExamController::class, 'index'])->name('live-exams.index');
    Route::get('/live-exams/live', [OperationsLiveExamController::class, 'live'])->name('live-exams.live');
    Route::get('/live-exams/{quiz}', [OperationsLiveExamController::class, 'show'])->name('live-exams.show');
    Route::post('/live-exams/{quiz}/end', [OperationsLiveExamController::class, 'end'])->name('live-exams.end');
    Route::post('/live-exams/{quiz}/extend', [OperationsLiveExamController::class, 'extend'])->name('live-exams.extend');
    Route::post('/live-exams/{quiz}/pause', [OperationsLiveExamController::class, 'pause'])->name('live-exams.pause');
    Route::post('/live-exams/{quiz}/broadcast', [OperationsLiveExamController::class, 'broadcast'])->name('live-exams.broadcast');

    Route::get('/students', [OperationsStudentController::class, 'index'])->name('students.index');
    Route::get('/students/live', [OperationsStudentController::class, 'live'])->name('students.live');

    Route::get('/proctoring', [OperationsProctoringController::class, 'index'])->name('proctoring.index');
    Route::get('/proctoring/live', [OperationsProctoringController::class, 'live'])->name('proctoring.live');

    Route::get('/incidents', [OperationsIncidentController::class, 'index'])->name('incidents.index');
    Route::post('/incidents', [OperationsIncidentController::class, 'store'])->name('incidents.store');
    Route::post('/incidents/{incident}/assign', [OperationsIncidentController::class, 'assign'])->name('incidents.assign');
    Route::post('/incidents/{incident}/resolve', [OperationsIncidentController::class, 'resolve'])->name('incidents.resolve');

    Route::get('/attendance', [OperationsAttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/live', [OperationsAttendanceController::class, 'live'])->name('attendance.live');

    Route::get('/intelligence', [OperationsAnalyticsController::class, 'intelligence'])->name('intelligence.index');
    Route::get('/exam-analytics', [OperationsAnalyticsController::class, 'exams'])->name('analytics.exams');
    Route::get('/attendance-analytics', [OperationsAnalyticsController::class, 'attendance'])->name('analytics.attendance');
    Route::get('/faculty-analytics', [OperationsAnalyticsController::class, 'faculty'])->name('analytics.faculty');

    Route::get('/reports', [OperationsAnalyticsController::class, 'reports'])->name('reports.index');
    Route::get('/reports/export', [OperationsAnalyticsController::class, 'reportsExport'])->name('reports.export');
});

Route::redirect('/admin/operations', '/dashboard/operations');
