<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('glucose_readings', 'diabetes_logs');

        Schema::table('diabetes_logs', function (Blueprint $table): void {
            $table->renameColumn('reading_value', 'glucose_value');
            $table->renameColumn('reading_type', 'glucose_reading_type');

            $table->decimal('insulin_units', 5, 2)->nullable()->after('notes');
            $table->string('insulin_type')->nullable()->after('insulin_units');
            $table->string('medication_name')->nullable()->after('insulin_type');
            $table->string('medication_dosage')->nullable()->after('medication_name');

            $table->decimal('weight', 5, 2)->nullable()->after('medication_dosage');
            $table->unsignedSmallInteger('blood_pressure_systolic')->nullable()->after('weight');
            $table->unsignedSmallInteger('blood_pressure_diastolic')->nullable()->after('blood_pressure_systolic');

            $table->decimal('a1c_value', 3, 1)->nullable()->after('blood_pressure_diastolic');

            $table->unsignedSmallInteger('carbs_grams')->nullable()->after('a1c_value');

            $table->string('exercise_type')->nullable()->after('carbs_grams');
            $table->unsignedSmallInteger('exercise_duration_minutes')->nullable()->after('exercise_type');
        });
    }
};
