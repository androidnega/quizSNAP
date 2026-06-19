<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\Student;
use App\Services\PageCacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    public function __invoke(Request $request, PageCacheService $pageCache): View|RedirectResponse
    {
        $token = $request->query('t') ?? $request->query('token');
        if ($token && is_string($token)) {
            $token = trim($token);
            if (preg_match('#^[a-zA-Z0-9_-]{8,64}$#', $token)) {
                $quiz = Quiz::where('link_token', $token)->first();
                if ($quiz && ($quiz->is_published || $quiz->is_active) && $quiz->hasEnoughApprovedQuestions()) {
                    if ($quiz->ends_at && $quiz->ends_at->isPast()) {
                        return redirect()->route('student.link-expired');
                    }
                    if ($quiz->starts_at && $quiz->starts_at->isFuture()) {
                        return redirect()->route('student.quiz-will-start', ['token' => $token]);
                    }

                    return redirect()->route('student.rules.show.quiz', ['token' => $token]);
                }

                return redirect()->route('student.link-expired');
            }
        }

        $studentId = session('student_id');
        $student = $studentId ? Student::find($studentId) : null;

        $public = $pageCache->landingPublicData();

        return view('student.landing', [
            'student' => $student,
            'appName' => $public['appName'],
            'institutionName' => $public['institutionName'],
            'landingHeroImage' => $public['landingHeroImage'],
            'landingHeroEnabled' => $public['landingHeroEnabled'],
            'landingShowQuizToken' => $public['landingShowQuizToken'],
        ]);
    }
}
