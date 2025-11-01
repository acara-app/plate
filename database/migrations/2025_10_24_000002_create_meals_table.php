<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('meal_plan_id')->constrained()->cascadeOnDelete();
            $table->integer('day_number'); // 1-7 for weekly, 1-30 for monthly
            $table->string('type'); // Enum: breakfast, lunch, dinner, snack
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('preparation_instructions')->nullable();
            $table->text('ingredients')->nullable(); // Could be JSON or text
            $table->string('portion_size')->nullable();
            $table->decimal('calories', 8, 2);
            $table->decimal('protein_grams', 8, 2)->nullable();
            $table->decimal('carbs_grams', 8, 2)->nullable();
            $table->decimal('fat_grams', 8, 2)->nullable();
            $table->integer('preparation_time_minutes')->nullable();
            $table->json('metadata')->nullable(); // Additional nutritional info
            $table->integer('sort_order')->default(0); // For ordering meals within a day
            $table->timestamps();

            $table->index(['meal_plan_id', 'day_number']);
            $table->index(['meal_plan_id', 'day_number', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meals');
    }
};
