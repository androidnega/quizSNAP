<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Isolate academic catalog rows per faculty so coordinators cannot share structure.
 */
return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'semesters' => 'value',
            'quiz_categories' => 'name',
            'student_levels' => 'value',
            'academic_years' => 'year',
            'academic_classes' => null,
        ];

        foreach ($tables as $table => $uniqueCol) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'faculty_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table, $uniqueCol) {
                $blueprint->foreignId('faculty_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('faculties')
                    ->nullOnDelete();
                $blueprint->index('faculty_id');

                // Drop global uniques so each faculty can have its own Semester 1, HND, etc.
                if ($table === 'semesters') {
                    try {
                        $blueprint->dropUnique(['value']);
                    } catch (\Throwable) {
                    }
                }
                if ($table === 'quiz_categories') {
                    try {
                        $blueprint->dropUnique(['name']);
                    } catch (\Throwable) {
                    }
                }
                if ($table === 'student_levels') {
                    try {
                        $blueprint->dropUnique(['value']);
                    } catch (\Throwable) {
                    }
                }
                if ($table === 'academic_years') {
                    try {
                        $blueprint->dropUnique(['year']);
                    } catch (\Throwable) {
                    }
                }
            });

            // Re-add scoped unique indexes
            if ($uniqueCol) {
                Schema::table($table, function (Blueprint $blueprint) use ($table, $uniqueCol) {
                    $blueprint->unique(['faculty_id', $uniqueCol], $table.'_faculty_'.$uniqueCol.'_unique');
                });
            }
        }

        // Seed a copy of legacy global rows for each faculty that has a coordinator/examiner
        $facultyIds = DB::table('users')
            ->whereNotNull('faculty_id')
            ->whereIn('role', ['coordinator', 'examiner', 'super_admin'])
            ->distinct()
            ->pluck('faculty_id')
            ->filter()
            ->values()
            ->all();

        foreach ($facultyIds as $facultyId) {
            $this->cloneGlobalRows('semesters', (int) $facultyId, ['value', 'name', 'sort_order']);
            $this->cloneGlobalRows('quiz_categories', (int) $facultyId, ['name', 'sort_order']);
            $this->cloneGlobalRows('student_levels', (int) $facultyId, ['value', 'label', 'sort_order']);
            $this->cloneGlobalRows('academic_years', (int) $facultyId, ['year', 'is_active']);
        }
    }

    private function cloneGlobalRows(string $table, int $facultyId, array $columns): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'faculty_id')) {
            return;
        }

        $existing = DB::table($table)->where('faculty_id', $facultyId)->exists();
        if ($existing) {
            return;
        }

        $globals = DB::table($table)->whereNull('faculty_id')->get();
        foreach ($globals as $row) {
            $payload = ['faculty_id' => $facultyId];
            foreach ($columns as $col) {
                $payload[$col] = $row->{$col} ?? null;
            }
            if (Schema::hasColumn($table, 'created_at')) {
                $payload['created_at'] = now();
                $payload['updated_at'] = now();
            }
            try {
                DB::table($table)->insert($payload);
            } catch (\Throwable) {
                // skip duplicates
            }
        }
    }

    public function down(): void
    {
        foreach (['semesters', 'quiz_categories', 'student_levels', 'academic_years', 'academic_classes'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'faculty_id')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropConstrainedForeignId('faculty_id');
                });
            }
        }
    }
};
