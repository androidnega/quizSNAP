<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('quiz_acceptance')) {
            return;
        }

        // Remove placeholder rows that never represented a real student index.
        DB::table('quiz_acceptance')
            ->whereRaw("UPPER(TRIM(index_number)) = 'PENDING'")
            ->delete();

        // Normalize stored index numbers so lookups stay consistent.
        DB::table('quiz_acceptance')
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $normalized = strtoupper(trim((string) $row->index_number));
                    if ($normalized === '' || $normalized === 'PENDING') {
                        DB::table('quiz_acceptance')->where('id', $row->id)->delete();

                        continue;
                    }
                    if ($normalized !== $row->index_number) {
                        DB::table('quiz_acceptance')
                            ->where('id', $row->id)
                            ->update(['index_number' => $normalized]);
                    }
                }
            });

        // Keep the newest row when legacy duplicates exist for the same quiz + index.
        $duplicateGroups = DB::table('quiz_acceptance')
            ->select('quiz_id', 'index_number', DB::raw('MAX(id) as keep_id'), DB::raw('COUNT(*) as total'))
            ->groupBy('quiz_id', 'index_number')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateGroups as $group) {
            DB::table('quiz_acceptance')
                ->where('quiz_id', $group->quiz_id)
                ->where('index_number', $group->index_number)
                ->where('id', '!=', $group->keep_id)
                ->delete();
        }

        $legacyIndex = 'quiz_acceptance_quiz_id_index_number_index';
        $hasLegacyIndex = collect(DB::select('SHOW INDEX FROM quiz_acceptance'))
            ->contains(fn ($row) => ($row->Key_name ?? null) === $legacyIndex);
        if ($hasLegacyIndex) {
            DB::statement("ALTER TABLE quiz_acceptance DROP INDEX {$legacyIndex}");
        }

        Schema::table('quiz_acceptance', function (Blueprint $table): void {
            $table->unique(['quiz_id', 'index_number'], 'quiz_acceptance_quiz_index_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('quiz_acceptance')) {
            return;
        }

        Schema::table('quiz_acceptance', function (Blueprint $table): void {
            $table->dropUnique('quiz_acceptance_quiz_index_unique');
        });
    }
};
