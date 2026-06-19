<?php

namespace App\Services;

use App\Models\ClassGroup;
use App\Models\ClassGroupStudent;
use App\Models\Student;
use Illuminate\Support\Facades\Cache;

class ClassGroupStudentImportService
{
    /**
     * Remove all students from a class group (replace mode).
     */
    public function clearClassGroupStudents(ClassGroup $classGroup): int
    {
        $count = $classGroup->students()->count();
        $removedIndices = $classGroup->students()->pluck('index_number');

        foreach ($removedIndices as $removedIndex) {
            Student::deleteEverywhereByIndex($removedIndex);
            $indexUpper = strtoupper(trim($removedIndex));
            Cache::forget('student_otp:' . $removedIndex);
            Cache::forget('student_otp:' . $indexUpper);
        }

        $classGroup->students()->delete();

        return $count;
    }

    /**
     * Import one index into the class group.
     *
     * @return 'added'|'updated'
     */
    public function importRow(ClassGroup $classGroup, string $indexNumber, ?string $name, bool $overwriteExisting = false): string
    {
        $indexTrimmed = trim($indexNumber);
        $name = $name ? trim($name) : null;

        $existing = ClassGroupStudent::where('class_group_id', $classGroup->id)
            ->where('index_number', $indexTrimmed)
            ->first();

        if ($existing && ! $overwriteExisting) {
            return 'updated';
        }

        ClassGroupStudent::updateOrCreate(
            ['class_group_id' => $classGroup->id, 'index_number' => $indexTrimmed],
            ['student_name' => $name]
        );

        $hash = Student::hashIndexNumber($indexTrimmed);
        $studentAccount = Student::firstOrCreate(
            ['index_number_hash' => $hash],
            ['index_number' => $indexTrimmed, 'index_number_hash' => $hash, 'student_name' => $name]
        );
        $studentAccount->student_name = $name ?? $studentAccount->student_name;
        $this->syncStudentFromClassGroup($studentAccount, $classGroup);
        $studentAccount->save();

        return $existing ? 'updated' : 'added';
    }

    public function syncStudentFromClassGroup(Student $studentAccount, ClassGroup $classGroup): void
    {
        $classGroup->load(['level', 'academicYear', 'examiner.department']);
        $level = $classGroup->level;
        $levelValue = $level ? (int) $level->value : null;
        $studentAccount->level = $levelValue;
        $studentAccount->level_id = $classGroup->level_id;
        $studentAccount->quiz_category_id = $classGroup->quiz_category_id;
        $studentAccount->semester_id = $classGroup->semester_id;
        $studentAccount->academic_year_id = $classGroup->academic_year_id;
        $studentAccount->academic_class_id = $classGroup->academic_class_id;
        if ($classGroup->examiner?->department_id) {
            $studentAccount->department_id = $classGroup->examiner->department_id;
        }
    }
}
