<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels')->onDelete('cascade');
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->enum('role', ['hotel_admin', 'chef', 'coordinator'])->default('chef');
            $table->string('locale', 5)->default('fr');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['hotel_id', 'email']);
            $table->index(['hotel_id', 'role']);
            $table->index(['hotel_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
