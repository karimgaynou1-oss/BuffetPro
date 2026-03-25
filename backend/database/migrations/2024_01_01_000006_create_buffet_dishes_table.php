<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buffet_dishes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buffet_id')->constrained('buffets')->onDelete('cascade');
            $table->foreignId('dish_id')->constrained('dishes')->onDelete('cascade');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['buffet_id', 'dish_id']);
            $table->index('buffet_id');
            $table->index('dish_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buffet_dishes');
    }
};
