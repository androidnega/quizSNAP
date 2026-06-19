<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Docu Mentor academic years (reused later by QuizSnap academic structure).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academic_years')) {
            return;
        }

        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('year', 9)->unique();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_years');
    }
};
