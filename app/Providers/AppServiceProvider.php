<?php

namespace App\Providers;

use App\Models\QuizSession;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->ensureSqliteDatabaseExists();

        Route::bind('quizSession', function (string $value) {
            return QuizSession::findOrFail($value);
        });

        Route::bind('academicYear', fn (string $value) => \App\Models\AcademicYear::findOrFail($value));

        View::composer('*', function ($view): void {
            if (request()->routeIs('admin.*')) {
                $view->with('staffPrefix', 'admin');
            } elseif (request()->routeIs('examiner.*')) {
                $view->with('staffPrefix', 'examiner');
            }
        });

        View::composer('layouts.app', function ($view): void {
            if (! request()->routeIs('student.quiz.show') && ! request()->routeIs('student.quiz.ready')) {
                $view->with('quizAllowsMobile', false);
                return;
            }
            if (request()->attributes->has('quizAllowsMobile')) {
                $view->with('quizAllowsMobile', (bool) request()->attributes->get('quizAllowsMobile'));
                return;
            }
            $token = session('quiz_session_token');
            if (! $token) {
                $view->with('quizAllowsMobile', false);
                return;
            }
            $session = \App\Models\QuizSession::with(['quiz.classGroup'])->where('session_token', $token)->first();
            if (! $session || ! $session->quiz) {
                $view->with('quizAllowsMobile', false);
                return;
            }
            $allowed = $session->quiz->getEffectiveAllowedDevices();
            $view->with('quizAllowsMobile', in_array($allowed, ['mobile', 'both'], true));
        });

        View::composer('layouts.student-dashboard', function ($view): void {
            $student = null;
            $greeting = 'Hello';
            if (session('student_id')) {
                $student = \App\Models\Student::find(session('student_id'));
            } elseif (auth()->user() instanceof \App\Models\Student) {
                $student = auth()->user();
            }
            if ($student) {
                $hour = (int) now()->format('G');
                if ($hour >= 5 && $hour < 12) {
                    $greeting = 'Good morning';
                } elseif ($hour >= 12 && $hour < 17) {
                    $greeting = 'Good afternoon';
                } else {
                    $greeting = 'Good evening';
                }
            }
            $view->with([
                'student' => $student,
                'greeting' => $greeting,
                'vapidPublicKey' => config('services.webpush.vapid_public'),
            ]);
        });
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('login', function ($request) {
            return Limit::perMinute(5)->by($request->ip() . ':' . $request->input('username', ''))->response(function () {
                return back()->with('error', 'Too many login attempts. Please try again in a minute.');
            });
        });

        RateLimiter::for('student-auth', function ($request) {
            $key = $request->ip() . ':' . strtolower(trim((string) $request->input('index_number', '')));

            return Limit::perMinute(30)->by($key)->response(function () {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many requests. Please wait a moment and try again.',
                ], 429);
            });
        });

        RateLimiter::for('student-otp-send', function ($request) {
            $key = $request->ip() . ':' . strtolower(trim((string) $request->input('index_number', '')));

            return Limit::perMinute(5)->by($key)->response(function () {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many SMS requests. Please wait about a minute before trying again.',
                    'can_resend' => false,
                ], 429);
            });
        });
    }

    protected function ensureSqliteDatabaseExists(): void
    {
        if (config('database.default') !== 'sqlite') {
            return;
        }

        $path = config('database.connections.sqlite.database');
        if (empty($path)) {
            return;
        }

        if (! str_starts_with($path, '/') && ! preg_match('#^[A-Za-z]:\\\\#', $path)) {
            $path = base_path($path);
            config(['database.connections.sqlite.database' => $path]);
        }

        if (! file_exists($path)) {
            $dir = dirname($path);
            if (! is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            @touch($path);
        }
    }
}
