<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->nullable()->constrained('hotels')->onDelete('set null');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_type')->default('user'); // user | super_admin
            $table->string('action');        // created, updated, deleted, logged_in ...
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['hotel_id', 'created_at']);
            $table->index(['user_id', 'user_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_log');
    }
};
