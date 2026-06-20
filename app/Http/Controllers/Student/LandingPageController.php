<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\PageCacheService;
use App\Services\QuizLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    public function __invoke(Request $request, PageCacheService $pageCache, QuizLinkService $quizLinks): View|RedirectResponse
    {
        $token = $request->query('t') ?? $request->query('token');
        if (is_string($token) && trim($token) !== '') {
            $token = $quizLinks->extractToken(trim($token)) ?? trim($token);
            $student = $quizLinks->resolveStudent();
            $indexNumber = $quizLinks->normalizedIndex($student);
            $destination = $quizLinks->publicLinkDestination($token, $indexNumber);

            if ($destination) {
                return redirect()->route($destination['route'], $destination['params']);
            }

            if ($quizLinks->findByToken($token)) {
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
