<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Academic years stay institution-wide (global), not per-faculty.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('academic_years') || ! Schema::hasColumn('academic_years', 'faculty_id')) {
            return;
        }

        $years = DB::table('academic_years')->orderBy('id')->get();
        $keepByYear = [];
        $remap = [];

        foreach ($years as $row) {
            $key = (string) $row->year;
            if (! isset($keepByYear[$key])) {
                $keepByYear[$key] = (int) $row->id;
                continue;
            }
            $remap[(int) $row->id] = $keepByYear[$key];
        }

        if ($remap !== [] && Schema::hasTable('academic_classes') && Schema::hasColumn('academic_classes', 'academic_year_id')) {
            foreach ($remap as $fromId => $toId) {
                DB::table('academic_classes')->where('academic_year_id', $fromId)->update(['academic_year_id' => $toId]);
            }
        }

        if ($remap !== []) {
            DB::table('academic_years')->whereIn('id', array_keys($remap))->delete();
        }

        DB::table('academic_years')->update(['faculty_id' => null]);

        Schema::table('academic_years', function (Blueprint $blueprint) {
            try {
                $blueprint->dropUnique('academic_years_faculty_year_unique');
            } catch (\Throwable) {
            }
            try {
                $blueprint->dropConstrainedForeignId('faculty_id');
            } catch (\Throwable) {
                if (Schema::hasColumn('academic_years', 'faculty_id')) {
                    $blueprint->dropColumn('faculty_id');
                }
            }
        });

        Schema::table('academic_years', function (Blueprint $blueprint) {
            try {
                $blueprint->unique('year');
            } catch (\Throwable) {
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('academic_years') || Schema::hasColumn('academic_years', 'faculty_id')) {
            return;
        }

        Schema::table('academic_years', function (Blueprint $blueprint) {
            try {
                $blueprint->dropUnique(['year']);
            } catch (\Throwable) {
            }
            $blueprint->foreignId('faculty_id')->nullable()->constrained('faculties')->nullOnDelete();
            $blueprint->unique(['faculty_id', 'year'], 'academic_years_faculty_year_unique');
        });
    }
};
