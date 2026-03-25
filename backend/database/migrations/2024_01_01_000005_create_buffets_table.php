<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buffets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->date('date');
            $table->enum('service', ['breakfast', 'lunch', 'dinner', 'brunch', 'afternoon_tea'])->default('lunch');
            $table->string('theme')->nullable();
            $table->integer('pax_count')->default(0);
            $table->decimal('budget_target_per_pax', 8, 2)->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['hotel_id', 'date']);
            $table->index(['hotel_id', 'status']);
            $table->index(['hotel_id', 'service']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buffets');
    }
};
