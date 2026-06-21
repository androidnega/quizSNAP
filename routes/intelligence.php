<?php

use App\Http\Controllers\Admin\Intelligence\IntelligenceAnalyticsController;
use App\Http\Controllers\Admin\Intelligence\IntelligenceOverviewController;
use App\Http\Controllers\Admin\Intelligence\IntelligenceReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['admin.auth', 'intelligence.access'])->prefix('dashboard/intelligence')->name('dashboard.intelligence.')->group(function () {
    Route::get('/', [IntelligenceOverviewController::class, 'index'])->name('index');
    Route::get('/live', [IntelligenceOverviewController::class, 'live'])->name('live');

    Route::get('/academic', [IntelligenceAnalyticsController::class, 'academic'])->name('academic.index');
    Route::get('/students', [IntelligenceAnalyticsController::class, 'students'])->name('students.index');
    Route::get('/lecturers', [IntelligenceAnalyticsController::class, 'lecturers'])->name('lecturers.index');
    Route::get('/risk', [IntelligenceAnalyticsController::class, 'risk'])->name('risk.index');
    Route::get('/proctoring', [IntelligenceAnalyticsController::class, 'proctoring'])->name('proctoring.index');
    Route::get('/predictive', [IntelligenceAnalyticsController::class, 'predictive'])->name('predictive.index');
    Route::get('/engagement', [IntelligenceAnalyticsController::class, 'engagement'])->name('engagement.index');
    Route::get('/integrity', [IntelligenceAnalyticsController::class, 'integrity'])->name('integrity.index');
    Route::get('/recommendations', [IntelligenceAnalyticsController::class, 'recommendations'])->name('recommendations.index');
    Route::get('/warnings', [IntelligenceAnalyticsController::class, 'warnings'])->name('warnings.index');

    Route::get('/reports', [IntelligenceReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export/json', [IntelligenceReportController::class, 'exportJson'])->name('reports.export.json');
    Route::get('/reports/export/csv', [IntelligenceReportController::class, 'exportCsv'])->name('reports.export.csv');
    Route::get('/reports/export/excel', [IntelligenceReportController::class, 'exportExcel'])->name('reports.export.excel');
    Route::get('/reports/export/pdf', [IntelligenceReportController::class, 'exportPdf'])->name('reports.export.pdf');
});

Route::redirect('/admin/intelligence', '/dashboard/intelligence');
Route::redirect('/dashboard/operations/intelligence', '/dashboard/intelligence/academic');
