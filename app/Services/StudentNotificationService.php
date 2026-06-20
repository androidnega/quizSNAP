<?php

namespace App\Services;

use App\Models\ClassGroupStudent;
use App\Models\ExamCalendar;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\Student;
use App\Models\StudentNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class StudentNotificationService
{
    private ?bool $tableReady = null;

    private function isTableReady(): bool
    {
        if ($this->tableReady === null) {
            try {
                $this->tableReady = Schema::hasTable('student_notifications');
            } catch (\Throwable) {
                $this->tableReady = false;
            }
        }

        return $this->tableReady;
    }

    /**
     * @return Collection<int, StudentNotification>
     */
    public function recentForStudent(string $indexNumber, int $limit = 20): Collection
    {
        if (! $this->isTableReady()) {
            return collect();
        }

        $index = $this->normalizeIndex($indexNumber);
        if ($index === '') {
            return collect();
        }

        return StudentNotification::query()
            ->where('student_index', $index)
            ->orderByDesc('created_at')
            ->limit(max(1, min($limit, 50)))
            ->get();
    }

    public function unreadCount(string $indexNumber): int
    {
        if (! $this->isTableReady()) {
            return 0;
        }

        $index = $this->normalizeIndex($indexNumber);
        if ($index === '') {
            return 0;
        }

        return StudentNotification::query()
            ->where('student_index', $index)
            ->whereNull('read_at')
            ->count();
    }

    public function markRead(int $notificationId, string $indexNumber): bool
    {
        if (! $this->isTableReady()) {
            return false;
        }

        $index = $this->normalizeIndex($indexNumber);
        if ($index === '') {
            return false;
        }

        $updated = StudentNotification::query()
            ->where('id', $notificationId)
            ->where('student_index', $index)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $updated > 0;
    }

    public function markAllRead(string $indexNumber): int
    {
        if (! $this->isTableReady()) {
            return 0;
        }

        $index = $this->normalizeIndex($indexNumber);
        if ($index === '') {
            return 0;
        }

        return StudentNotification::query()
            ->where('student_index', $index)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function notifyClassGroup(
        int $classGroupId,
        string $type,
        string $title,
        ?string $body = null,
        ?string $actionUrl = null,
        ?int $sourceId = null,
        ?string $sourceType = null,
        ?array $meta = null,
        bool $dedupe = true,
    ): int {
        $indices = ClassGroupStudent::query()
            ->where('class_group_id', $classGroupId)
            ->pluck('index_number')
            ->filter(fn ($v) => is_string($v) && trim($v) !== '')
            ->unique()
            ->values();

        $count = 0;
        foreach ($indices as $indexNumber) {
            if ($this->notifyStudent($indexNumber, $type, $title, $body, $actionUrl, $sourceId, $sourceType, $meta, $dedupe)) {
                $count++;
            }
        }

        return $count;
    }

    public function notifyStudent(
        string $indexNumber,
        string $type,
        string $title,
        ?string $body = null,
        ?string $actionUrl = null,
        ?int $sourceId = null,
        ?string $sourceType = null,
        ?array $meta = null,
        bool $dedupe = true,
    ): bool {
        if (! $this->isTableReady()) {
            return false;
        }

        $index = $this->normalizeIndex($indexNumber);
        if ($index === '' || trim($title) === '') {
            return false;
        }

        if ($dedupe && $sourceId !== null && $sourceType !== null) {
            $exists = StudentNotification::query()
                ->where('student_index', $index)
                ->where('type', $type)
                ->where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->exists();
            if ($exists) {
                return false;
            }
        }

        StudentNotification::create([
            'student_index' => $index,
            'student_index_hash' => Student::hashIndexNumber($index),
            'type' => $type,
            'title' => trim($title),
            'body' => $body !== null && trim($body) !== '' ? trim($body) : null,
            'action_url' => $actionUrl,
            'source_id' => $sourceId,
            'source_type' => $sourceType,
            'meta' => $meta,
        ]);

        return true;
    }

    public function notifyQuizPublished(Quiz $quiz): int
    {
        if (! $quiz->class_group_id) {
            return 0;
        }

        $course = $quiz->course?->name;
        $label = $quiz->getExamTypeLabel();
        $title = 'New '.$label.': '.$quiz->title;
        $body = $course
            ? $course.' — a new assessment is available.'
            : 'A new assessment is available for your class.';

        return $this->notifyClassGroup(
            (int) $quiz->class_group_id,
            StudentNotification::TYPE_NEW_QUIZ,
            $title,
            $body,
            route('dashboard.my-quizzes'),
            $quiz->id,
            'quiz',
            ['quiz_id' => $quiz->id],
        );
    }

    public function notifyTimetableEntry(ExamCalendar $entry, bool $isUpdate = false): int
    {
        $course = $entry->course?->name ?? $entry->course_name ?? 'Exam';
        $typeLabel = $entry->exam_type === ExamCalendar::EXAM_TYPE_END_OF_SEMESTER
            ? 'End of semester exam'
            : 'Midsem exam';
        $when = $entry->scheduled_at?->format('D, M j · g:i A') ?? '';
        $title = ($isUpdate ? 'Timetable updated: ' : 'New timetable: ').$course;
        $body = trim($typeLabel.($when !== '' ? ' · '.$when : ''));

        return $this->notifyClassGroup(
            (int) $entry->class_group_id,
            StudentNotification::TYPE_TIMETABLE,
            $title,
            $body,
            route('dashboard.calendar'),
            $entry->id,
            $isUpdate ? 'exam_calendar_update' : 'exam_calendar',
            ['exam_calendar_id' => $entry->id],
            ! $isUpdate,
        );
    }

    public function notifyResultHeld(QuizSession $session): bool
    {
        $quiz = $session->quiz;
        if (! $quiz) {
            return false;
        }

        return $this->notifyStudent(
            (string) $session->student_index,
            StudentNotification::TYPE_RESULT_HELD,
            'Result held: '.$quiz->title,
            'Your result is under review. You will be notified when it is released.',
            route('dashboard.my-quizzes'),
            $session->id,
            'quiz_session_held',
            ['session_id' => $session->id, 'quiz_id' => $quiz->id],
        );
    }

    public function notifyResultReleased(QuizSession $session): bool
    {
        $quiz = $session->quiz;
        if (! $quiz) {
            return false;
        }

        return $this->notifyStudent(
            (string) $session->student_index,
            StudentNotification::TYPE_RESULT_RELEASED,
            'Result released: '.$quiz->title,
            'Your result is now available to view.',
            route('dashboard.my-quizzes.show', ['sessionId' => $session->id]),
            $session->id,
            'quiz_session_released',
            ['session_id' => $session->id, 'quiz_id' => $quiz->id],
        );
    }

    public function sendStaffMessage(int $classGroupId, string $title, string $body, ?int $senderUserId = null): int
    {
        return $this->notifyClassGroup(
            $classGroupId,
            StudentNotification::TYPE_STAFF_MESSAGE,
            $title,
            $body,
            route('dashboard'),
            null,
            null,
            array_filter(['sender_user_id' => $senderUserId]),
            false,
        );
    }

    private function normalizeIndex(string $indexNumber): string
    {
        return strtoupper(trim($indexNumber));
    }
}
