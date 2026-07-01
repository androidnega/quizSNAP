<?php

namespace App\Support;

use App\Models\Student;

/** Student login session keys — always clear staff auth when establishing. */
final class StudentSession
{
    public static function establish(Student $student): void
    {
        StaffSession::clear();
        auth()->logout();
        auth()->login($student, false);
        session([
            'student_id' => $student->id,
            'student_index' => $student->index_number,
        ]);
    }

    public static function clear(): void
    {
        session()->forget(['student_id', 'student_index', 'student_login_intent']);
        if (auth()->user() instanceof Student) {
            auth()->logout();
        }
    }

    public static function isActive(): bool
    {
        return (bool) session('student_id');
    }
}
