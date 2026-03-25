<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dishes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels')->onDelete('cascade');
            $table->string('name_fr');
            $table->string('name_en');
            $table->string('name_es');
            $table->enum('category', [
                'starter', 'soup', 'salad', 'main_course', 'side_dish',
                'dessert', 'pastry', 'beverage', 'cheese', 'fruit', 'other',
            ])->default('other');
            $table->decimal('cost_per_10pax', 10, 2)->default(0);
            $table->integer('portion_grams')->default(150);
            $table->json('allergens')->nullable();   // array of strings
            $table->json('diets')->nullable();        // array: vegetarian, vegan, halal, kosher, gluten_free
            $table->text('description_fr')->nullable();
            $table->text('description_en')->nullable();
            $table->text('description_es')->nullable();
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['hotel_id', 'category']);
            $table->index(['hotel_id', 'is_active']);
            $table->index('hotel_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dishes');
    }
};
