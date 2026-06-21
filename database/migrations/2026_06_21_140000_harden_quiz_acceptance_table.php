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

        $this->removeAnonymousPendingRows();

        if (! $this->hasIndex('quiz_acceptance', 'quiz_acceptance_quiz_id_index_number_unique')) {
            $this->ensureQuizIdIndexForForeignKey();
            $this->replaceCompositeIndexWithUnique();
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('quiz_acceptance')) {
            return;
        }

        if ($this->hasIndex('quiz_acceptance', 'quiz_acceptance_quiz_id_index_number_unique')) {
            Schema::table('quiz_acceptance', function (Blueprint $table) {
                $table->dropUnique('quiz_acceptance_quiz_id_index_number_unique');
            });
        }

        if (! $this->hasIndex('quiz_acceptance', 'quiz_acceptance_quiz_id_index_number_index')) {
            Schema::table('quiz_acceptance', function (Blueprint $table) {
                $table->index(['quiz_id', 'index_number'], 'quiz_acceptance_quiz_id_index_number_index');
            });
        }
    }

    private function removeAnonymousPendingRows(): void
    {
        DB::table('quiz_acceptance')
            ->where('index_number', 'pending')
            ->delete();
    }

    private function ensureQuizIdIndexForForeignKey(): void
    {
        if ($this->hasIndex('quiz_acceptance', 'quiz_acceptance_quiz_id_foreign')) {
            return;
        }

        if ($this->hasIndex('quiz_acceptance', 'quiz_acceptance_quiz_id_index')) {
            return;
        }

        Schema::table('quiz_acceptance', function (Blueprint $table) {
            $table->index('quiz_id', 'quiz_acceptance_quiz_id_index');
        });
    }

    private function replaceCompositeIndexWithUnique(): void
    {
        if (! $this->hasIndex('quiz_acceptance', 'quiz_acceptance_quiz_id_index_number_index')) {
            Schema::table('quiz_acceptance', function (Blueprint $table) {
                $table->unique(['quiz_id', 'index_number'], 'quiz_acceptance_quiz_id_index_number_unique');
            });

            return;
        }

        DB::statement('ALTER TABLE `quiz_acceptance` DROP INDEX `quiz_acceptance_quiz_id_index_number_index`, ADD UNIQUE `quiz_acceptance_quiz_id_index_number_unique` (`quiz_id`, `index_number`)');
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $rows = DB::select('SHOW INDEX FROM `'.$table.'` WHERE Key_name = ?', [$indexName]);

        return $rows !== [];
    }
};
