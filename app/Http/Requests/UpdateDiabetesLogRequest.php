<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\GlucoseReadingType;
use App\Enums\InsulinType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateDiabetesLogRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            // Glucose tracking
            'glucose_value' => ['nullable', 'numeric', 'min:20', 'max:600'],
            'glucose_reading_type' => ['nullable', 'required_with:glucose_value', Rule::enum(GlucoseReadingType::class)],

            // Required fields
            'measured_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],

            // Insulin tracking
            'insulin_units' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'insulin_type' => ['nullable', 'required_with:insulin_units', Rule::enum(InsulinType::class)],

            // Medication tracking
            'medication_name' => ['nullable', 'string', 'max:100'],
            'medication_dosage' => ['nullable', 'string', 'max:100'],

            // Vital signs
            'weight' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'blood_pressure_systolic' => ['nullable', 'integer', 'min:60', 'max:300'],
            'blood_pressure_diastolic' => ['nullable', 'integer', 'min:30', 'max:200'],

            // A1C tracking
            'a1c_value' => ['nullable', 'numeric', 'min:3', 'max:20'],

            // Carbohydrate intake
            'carbs_grams' => ['nullable', 'integer', 'min:0', 'max:1000'],

            // Exercise tracking
            'exercise_type' => ['nullable', 'string', 'max:100'],
            'exercise_duration_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'glucose_value.numeric' => 'The glucose reading must be a number.',
            'glucose_value.min' => 'Please enter a valid glucose reading (minimum 20 mg/dL).',
            'glucose_value.max' => 'Please enter a valid glucose reading (maximum 600 mg/dL).',
            'glucose_reading_type.required_with' => 'Please select the type of glucose reading.',
            'measured_at.required' => 'Please provide the date and time of the measurement.',
            'measured_at.date' => 'Please provide a valid date and time.',
            'notes.max' => 'Notes cannot exceed 500 characters.',
            'insulin_units.numeric' => 'Insulin units must be a number.',
            'insulin_type.required_with' => 'Please select the insulin type.',
            'blood_pressure_systolic.min' => 'Systolic blood pressure seems too low.',
            'blood_pressure_systolic.max' => 'Systolic blood pressure seems too high.',
            'blood_pressure_diastolic.min' => 'Diastolic blood pressure seems too low.',
            'blood_pressure_diastolic.max' => 'Diastolic blood pressure seems too high.',
            'a1c_value.min' => 'A1C value seems too low.',
            'a1c_value.max' => 'A1C value seems too high.',
        ];
    }
}
