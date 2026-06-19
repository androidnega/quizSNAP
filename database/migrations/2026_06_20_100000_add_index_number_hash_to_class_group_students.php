<?php

use App\Models\ClassGroupStudent;
use App\Models\Student;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_group_students', function (Blueprint $table) {
            if (! Schema::hasColumn('class_group_students', 'index_number_hash')) {
                $table->string('index_number_hash', 64)->nullable()->after('index_number');
                $table->index('index_number_hash', 'class_group_students_index_hash_idx');
            }
        });

        ClassGroupStudent::query()
            ->whereNull('index_number_hash')
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                foreach ($rows as $row) {
                    if (! is_string($row->index_number) || trim($row->index_number) === '') {
                        continue;
                    }
                    DB::table('class_group_students')
                        ->where('id', $row->id)
                        ->update(['index_number_hash' => Student::hashIndexNumber($row->index_number)]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('class_group_students', function (Blueprint $table) {
            if (Schema::hasColumn('class_group_students', 'index_number_hash')) {
                $table->dropIndex('class_group_students_index_hash_idx');
                $table->dropColumn('index_number_hash');
            }
        });
    }
};
