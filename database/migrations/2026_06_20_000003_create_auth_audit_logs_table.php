<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('auth_audit_logs')) {
            return;
        }

        Schema::create('auth_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('actor_type', 32)->index();
            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->string('index_number_hash', 64)->nullable()->index();
            $table->string('event', 64)->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['actor_type', 'event', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_audit_logs');
    }
};
