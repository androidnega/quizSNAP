<?php

namespace App\Services\Intelligence;

use App\Models\ClassGroupStudent;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\Result;
use App\Services\Operations\OperationsAttendanceAnalyticsService;
use App\Services\Operations\OperationsExamAnalyticsService;
use Illuminate\Support\Facades\Schema;

class IntelligencePredictiveService
{
    public function __construct(protected IntelligenceRiskEngine $risk) {}

    public function snapshot(int $days = 90): array
    {
        $since = now()->subDays($days);
        $exam = app(OperationsExamAnalyticsService::class)->snapshot($days);
        $attendance = app(OperationsAttendanceAnalyticsService::class)->snapshot($days);
        $students = app(IntelligenceStudentService::class)->snapshot($days);

        $avgScore = (float) ($exam['average_score'] ?? 0);
        $passRate = (float) ($exam['pass_rate'] ?? 0);
        $attendanceRate = (float) ($attendance['attendance_rate'] ?? 0);

        $likelyPass = $this->risk->predictPassProbability($avgScore, $passRate, $attendanceRate);
        $likelyFail = $this->risk->predictFailProbability($likelyPass);

        $studentPredictions = collect($students['students'] ?? [])->take(50)->map(function ($s) {
            $passProb = $this->risk->predictPassProbability(
                (float) ($s['performance_score'] ?? 0),
                (float) ($s['engagement_score'] ?? 0),
                (float) ($s['attendance_score'] ?? 0)
            );

            return [
                'student_index' => $s['student_index'],
                'likely_pass' => $passProb,
                'likely_fail' => $this->risk->predictFailProbability($passProb),
                'attendance_risk' => max(0, 100 - ($s['attendance_score'] ?? 0)),
                'dropout_risk' => max(0, 100 - (($s['engagement_score'] ?? 0) * 0.6 + ($s['attendance_score'] ?? 0) * 0.4)),
            ];
        })->values()->all();

        return [
            'institution' => [
                'likely_pass' => $likelyPass,
                'likely_fail' => $likelyFail,
                'attendance_risk' => max(0, 100 - $attendanceRate),
                'dropout_risk' => max(0, 100 - (($passRate * 0.5) + ($attendanceRate * 0.5))),
            ],
            'course_risks' => $this->courseRisks($since),
            'department_risks' => $this->departmentRisks($since),
            'student_predictions' => $studentPredictions,
            'period_days' => $days,
        ];
    }

    protected function courseRisks($since): array
    {
        if (! Schema::hasTable('results') || ! Schema::hasTable('courses')) {
            return [];
        }

        return Result::query()
            ->join('quiz_sessions', 'quiz_sessions.id', '=', 'results.quiz_session_id')
            ->join('quizzes', 'quizzes.id', '=', 'quiz_sessions.quiz_id')
            ->join('courses', 'courses.id', '=', 'quizzes.course_id')
            ->where('results.submitted_at', '>=', $since)
            ->groupBy('courses.id', 'courses.name')
            ->selectRaw('courses.name, AVG(results.score) as avg_score, COUNT(*) as total')
            ->orderBy('avg_score')
            ->limit(15)
            ->get()
            ->map(fn ($r) => [
                'name' => $r->name,
                'risk_score' => max(0, (int) round(100 - (float) $r->avg_score)),
                'avg_score' => round((float) $r->avg_score, 1),
            ])
            ->all();
    }

    protected function departmentRisks($since): array
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('departments')) {
            return [];
        }

        return Result::query()
            ->join('quiz_sessions', 'quiz_sessions.id', '=', 'results.quiz_session_id')
            ->join('quizzes', 'quizzes.id', '=', 'quiz_sessions.quiz_id')
            ->join('users', 'users.id', '=', 'quizzes.examiner_id')
            ->join('departments', 'departments.id', '=', 'users.department_id')
            ->where('results.submitted_at', '>=', $since)
            ->groupBy('departments.id', 'departments.name')
            ->selectRaw('departments.name, AVG(results.score) as avg_score')
            ->orderBy('avg_score')
            ->limit(15)
            ->get()
            ->map(fn ($r) => [
                'name' => $r->name,
                'risk_score' => max(0, (int) round(100 - (float) $r->avg_score)),
            ])
            ->all();
    }
}
