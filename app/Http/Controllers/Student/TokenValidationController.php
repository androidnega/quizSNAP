<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\QuizLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TokenValidationController extends Controller
{
    public function __construct(
        private readonly QuizLinkService $quizLinks,
    ) {}

    /**
     * Validate quiz token (AJAX). Returns valid, starts_at (if future), and message.
     * Token format: alphanumeric with optional hyphen, e.g. KTdie54-3Sx9.
     */
    public function validateToken(Request $request): JsonResponse
    {
        $request->validate(['token' => 'required|string|max:128']);
        $input = trim($request->input('token', ''));
        $token = $this->quizLinks->extractToken($input);
        if (! $token) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid token format. Use your quiz token (e.g. KTdie54-3Sx9).',
            ]);
        }

        $quiz = $this->quizLinks->findByToken($token);
        if (! $quiz) {
            return response()->json([
                'valid' => false,
                'message' => 'This token is not valid. Please check and try again.',
            ]);
        }

        $student = $this->quizLinks->resolveStudent();
        $indexNumber = $this->quizLinks->normalizedIndex($student);
        if (! $this->quizLinks->isLinkOpen($quiz, $indexNumber)) {
            return response()->json([
                'valid' => false,
                'message' => 'This quiz is not available yet.',
            ]);
        }

        $startsAt = null;
        if ($quiz->starts_at && $quiz->starts_at->isFuture()) {
            $startsAt = $quiz->starts_at->toIso8601String();
        }

        return response()->json([
            'valid' => true,
            'message' => 'Valid token, proceed.',
            'starts_at' => $startsAt,
            'token' => $token,
        ]);
    }
}
