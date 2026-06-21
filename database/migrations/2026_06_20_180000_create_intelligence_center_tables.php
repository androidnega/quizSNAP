<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('intelligence_recommendations')) {
            Schema::create('intelligence_recommendations', function (Blueprint $table) {
                $table->id();
                $table->string('category', 64)->index();
                $table->string('severity', 16)->index();
                $table->string('title');
                $table->text('message');
                $table->string('subject_type', 64)->nullable();
                $table->string('subject_key', 128)->nullable()->index();
                $table->json('meta')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['category', 'created_at']);
            });
        }

        if (! Schema::hasTable('intelligence_warnings')) {
            Schema::create('intelligence_warnings', function (Blueprint $table) {
                $table->id();
                $table->string('warning_type', 64)->index();
                $table->string('severity', 16)->index();
                $table->string('title');
                $table->text('message');
                $table->string('subject_type', 64)->nullable();
                $table->string('subject_key', 128)->nullable()->index();
                $table->string('status', 32)->default('open')->index();
                $table->json('meta')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('intelligence_anomalies')) {
            Schema::create('intelligence_anomalies', function (Blueprint $table) {
                $table->id();
                $table->string('anomaly_type', 64)->index();
                $table->string('severity', 16)->index();
                $table->string('title');
                $table->text('description')->nullable();
                $table->json('metrics')->nullable();
                $table->string('status', 32)->default('open')->index();
                $table->timestamp('detected_at')->useCurrent();
                $table->timestamp('resolved_at')->nullable();
            });
        }

        if (! Schema::hasTable('intelligence_snapshots')) {
            Schema::create('intelligence_snapshots', function (Blueprint $table) {
                $table->id();
                $table->string('snapshot_type', 64)->index();
                $table->json('payload');
                $table->timestamp('recorded_at')->useCurrent()->index();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('intelligence_snapshots');
        Schema::dropIfExists('intelligence_anomalies');
        Schema::dropIfExists('intelligence_warnings');
        Schema::dropIfExists('intelligence_recommendations');
    }
};
