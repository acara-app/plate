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
        Schema::rename('glucose_readings', 'diabetes_logs');

        Schema::table('diabetes_logs', function (Blueprint $table): void {
            // Rename reading columns for clarity
            $table->renameColumn('reading_value', 'glucose_value');
            $table->renameColumn('reading_type', 'glucose_reading_type');

            // Add insulin tracking
            $table->decimal('insulin_units', 5, 2)->nullable()->after('notes');
            $table->string('insulin_type')->nullable()->after('insulin_units'); // basal, bolus, mixed

            // Add medication tracking
            $table->string('medication_name')->nullable()->after('insulin_type');
            $table->string('medication_dosage')->nullable()->after('medication_name');

            // Add vital signs
            $table->decimal('weight', 5, 2)->nullable()->after('medication_dosage'); // in lbs or kg
            $table->unsignedSmallInteger('blood_pressure_systolic')->nullable()->after('weight');
            $table->unsignedSmallInteger('blood_pressure_diastolic')->nullable()->after('blood_pressure_systolic');

            // Add A1C tracking
            $table->decimal('a1c_value', 3, 1)->nullable()->after('blood_pressure_diastolic');

            // Add carbohydrate intake
            $table->unsignedSmallInteger('carbs_grams')->nullable()->after('a1c_value');

            // Add exercise tracking
            $table->string('exercise_type')->nullable()->after('carbs_grams');
            $table->unsignedSmallInteger('exercise_duration_minutes')->nullable()->after('exercise_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('diabetes_logs', function (Blueprint $table): void {
            $table->dropColumn([
                'insulin_units',
                'insulin_type',
                'medication_name',
                'medication_dosage',
                'weight',
                'blood_pressure_systolic',
                'blood_pressure_diastolic',
                'a1c_value',
                'carbs_grams',
                'exercise_type',
                'exercise_duration_minutes',
            ]);

            $table->renameColumn('glucose_value', 'reading_value');
            $table->renameColumn('glucose_reading_type', 'reading_type');
        });

        Schema::rename('diabetes_logs', 'glucose_readings');
    }
};
