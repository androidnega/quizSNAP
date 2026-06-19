<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Legacy placeholder migration.
 * The class_group_reps table was removed from the codebase; this no-op exists
 * only so historical migration records resolve cleanly on existing installs.
 */
return new class extends Migration
{
    public function up(): void
    {
        //
    }

    public function down(): void
    {
        //
    }
};
