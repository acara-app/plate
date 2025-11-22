<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('usda_foundation_foods', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary(); // fdcId
            $table->text('description');
            $table->string('food_category')->nullable();
            $table->date('publication_date')->nullable();
            $table->json('nutrients'); // Stores the entire foodNutrients array
            $table->timestamps();
        });

        Schema::create('usda_sr_legacy_foods', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary(); // fdcId
            $table->text('description');
            $table->string('food_category')->nullable();
            $table->date('publication_date')->nullable();
            $table->json('nutrients'); // Stores the entire foodNutrients array
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usda_foundation_foods');
        Schema::dropIfExists('usda_sr_legacy_foods');
    }
};
