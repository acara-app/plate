<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('benchmark_meals', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('tranche');
            $table->date('collected_on');
            $table->string('cuisine');
            $table->string('dish_type');
            $table->string('lighting');
            $table->string('angle');
            $table->string('truth_scope');
            $table->decimal('total_weight_g', 8, 2);
            $table->decimal('total_kcal', 8, 2)->nullable();
            $table->decimal('total_carbs_g', 8, 2)->nullable();
            $table->decimal('total_protein_g', 8, 2)->nullable();
            $table->decimal('total_fat_g', 8, 2)->nullable();
            $table->string('truth_source')->nullable();
            $table->string('truth_ref')->nullable();
            $table->string('photo_disk');
            $table->string('photo_path');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('benchmark_meal_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('benchmark_meal_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('name');
            $table->boolean('visible')->default(true);
            $table->decimal('weight_g', 8, 2);
            $table->decimal('kcal_per_100g', 8, 2);
            $table->decimal('carbs_per_100g', 8, 2);
            $table->decimal('protein_per_100g', 8, 2);
            $table->decimal('fat_per_100g', 8, 2);
            $table->string('truth_source');
            $table->string('truth_ref')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['benchmark_meal_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('benchmark_meal_items');
        Schema::dropIfExists('benchmark_meals');
    }
};
