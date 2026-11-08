<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal_estimate_daily_stats', function (Blueprint $table): void {
            $table->id();
            $table->date('date');
            $table->string('model_version');
            $table->string('source');
            $table->unsignedInteger('meals');
            $table->unsignedInteger('corrected');
            $table->json('kcal_changes');
            $table->json('kcal_change_percents');
            $table->json('carbs_changes');
            $table->timestamps();

            $table->unique(['date', 'model_version', 'source']);
        });

        Schema::create('meal_estimate_daily_food_changes', function (Blueprint $table): void {
            $table->id();
            $table->date('date')->index();
            $table->text('ai_food')->nullable();
            $table->text('corrected_food')->nullable();
            $table->unsignedInteger('meals');
            $table->timestamps();
        });
    }
};
