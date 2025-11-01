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
        Schema::create('glucose_readings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('reading_value', 5, 1); // e.g., 125.5 mg/dL
            $table->string('reading_type'); // Fasting, PostMeal, Random, BeforeMeal
            $table->timestamp('measured_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'measured_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('glucose_readings');
    }
};
