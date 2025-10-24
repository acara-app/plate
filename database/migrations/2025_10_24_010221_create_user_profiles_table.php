<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Step 1: Biometrics
            $table->integer('age')->nullable();
            $table->decimal('height', 5, 2)->nullable()->comment('Height in cm');
            $table->decimal('weight', 5, 2)->nullable()->comment('Weight in kg');
            $table->string('sex')->nullable();

            // Step 2: Goals (using existing goals table as reference)
            $table->foreignId('goal_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('target_weight', 5, 2)->nullable()->comment('Target weight in kg');
            $table->text('additional_goals')->nullable();

            // Step 3: Lifestyle
            $table->foreignId('lifestyle_id')->nullable()->constrained()->nullOnDelete();

            // Completion tracking
            $table->boolean('onboarding_completed')->default(false);
            $table->timestamp('onboarding_completed_at')->nullable();

            $table->timestamps();
        });

        // Pivot table for dietary preferences (many-to-many)
        Schema::create('user_profile_dietary_preference', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dietary_preference_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_profile_id', 'dietary_preference_id'], 'profile_dietary_unique');
        });

        // Pivot table for health conditions (many-to-many)
        Schema::create('user_profile_health_condition', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('health_condition_id')->constrained()->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_profile_id', 'health_condition_id'], 'profile_health_unique');
        });
    }
};
