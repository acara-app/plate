<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal_plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // Enum: weekly, monthly, custom
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->integer('duration_days'); // 7 for weekly, 30 for monthly, etc.
            $table->decimal('target_daily_calories', 8, 2)->nullable();
            $table->json('macronutrient_ratios')->nullable(); // {protein: 30, carbs: 40, fat: 30}
            $table->json('metadata')->nullable(); // Additional context like BMI, BMR, TDEE at creation time
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_plans');
    }
};
