<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentNotification;
use App\Services\StudentNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentNotificationController extends Controller
{
    public function index(StudentNotificationService $notifications): JsonResponse
    {
        $student = $this->student();

        return response()->json([
            'unread_count' => $notifications->unreadCount($student->index_number),
            'notifications' => $notifications->recentForStudent($student->index_number, 25)
                ->map(fn (StudentNotification $n) => $this->formatNotification($n)),
        ]);
    }

    public function markRead(Request $request, int $notificationId, StudentNotificationService $notifications): JsonResponse
    {
        $student = $this->student();
        $notifications->markRead($notificationId, $student->index_number);

        return response()->json([
            'unread_count' => $notifications->unreadCount($student->index_number),
        ]);
    }

    public function markAllRead(StudentNotificationService $notifications): JsonResponse
    {
        $student = $this->student();
        $notifications->markAllRead($student->index_number);

        return response()->json(['unread_count' => 0]);
    }

    private function student(): Student
    {
        $user = auth()->user();
        if ($user instanceof Student) {
            return $user;
        }
        $student = Student::find(session('student_id'));
        if (! $student) {
            abort(401);
        }

        return $student;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatNotification(StudentNotification $notification): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'title' => $notification->title,
            'body' => $notification->body,
            'action_url' => $notification->action_url,
            'icon' => $notification->iconClass(),
            'read' => $notification->isRead(),
            'created_at' => $notification->created_at?->diffForHumans(),
            'created_at_iso' => $notification->created_at?->toIso8601String(),
        ];
    }
}
