<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreMedicationsRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'medications' => ['nullable', 'array'],
            'medications.*.name' => ['required_with:medications', 'string', 'max:255'],
            'medications.*.dosage' => ['nullable', 'string', 'max:100'],
            'medications.*.frequency' => ['nullable', 'string', 'max:100'],
            'medications.*.purpose' => ['nullable', 'string', 'max:255'],
            'medications.*.started_at' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'medications.array' => 'Medications must be an array.',
            'medications.*.name.required_with' => 'Medication name is required.',
            'medications.*.name.string' => 'Medication name must be a string.',
            'medications.*.name.max' => 'Medication name cannot exceed 255 characters.',
            'medications.*.dosage.max' => 'Dosage cannot exceed 100 characters.',
            'medications.*.frequency.max' => 'Frequency cannot exceed 100 characters.',
            'medications.*.purpose.max' => 'Purpose cannot exceed 255 characters.',
            'medications.*.started_at.date' => 'Start date must be a valid date.',
        ];
    }
}
