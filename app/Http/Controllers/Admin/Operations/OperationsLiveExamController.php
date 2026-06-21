<?php

namespace App\Http\Controllers\Admin\Operations;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Services\Operations\OperationsExamControlService;
use App\Services\Operations\OperationsLiveExamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OperationsLiveExamController extends Controller
{
    public function index(OperationsLiveExamService $service): View
    {
        return view('admin.operations.live-exams.index', [
            'snapshot' => $service->snapshot(),
        ]);
    }

    public function live(OperationsLiveExamService $service): JsonResponse
    {
        return response()->json($service->snapshot());
    }

    public function show(Quiz $quiz, OperationsLiveExamService $service): View
    {
        $exam = collect($service->build()['exams'] ?? [])->firstWhere('id', $quiz->id);

        return view('admin.operations.live-exams.show', [
            'quiz' => $quiz->load(['course', 'examiner', 'classGroup']),
            'exam' => $exam,
        ]);
    }

    public function end(Quiz $quiz, OperationsExamControlService $control): RedirectResponse
    {
        $control->endExam($quiz, auth()->user());

        return back()->with('success', 'Exam ended.');
    }

    public function extend(Request $request, Quiz $quiz, OperationsExamControlService $control): RedirectResponse
    {
        $request->validate(['additional_minutes' => 'required|integer|min:1|max:120']);
        $control->extendTime($quiz, (int) $request->input('additional_minutes'), auth()->user());

        return back()->with('success', 'Exam time extended.');
    }

    public function pause(Quiz $quiz, OperationsExamControlService $control): RedirectResponse
    {
        if ($quiz->is_paused) {
            $control->resumeExam($quiz, auth()->user());

            return back()->with('success', 'Exam resumed.');
        }

        $control->pauseExam($quiz, auth()->user());

        return back()->with('success', 'Exam paused.');
    }

    public function broadcast(Request $request, Quiz $quiz, OperationsExamControlService $control): RedirectResponse
    {
        $request->validate(['message' => 'required|string|max:1000']);
        $control->broadcastMessage($quiz, $request->input('message'), auth()->user());

        return back()->with('success', 'Message broadcast to students.');
    }
}
