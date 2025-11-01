<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ReadingType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreGlucoseReadingRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'reading_value' => ['required', 'numeric', 'min:20', 'max:600'],
            'reading_type' => ['required', Rule::enum(ReadingType::class)],
            'measured_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reading_value.required' => 'Please provide your glucose reading value.',
            'reading_value.numeric' => 'The glucose reading must be a number.',
            'reading_value.min' => 'Please enter a valid glucose reading (minimum 20 mg/dL).',
            'reading_value.max' => 'Please enter a valid glucose reading (maximum 600 mg/dL).',
            'reading_type.required' => 'Please select the type of glucose reading.',
            'measured_at.required' => 'Please provide the date and time of the measurement.',
            'measured_at.date' => 'Please provide a valid date and time.',
            'notes.max' => 'Notes cannot exceed 500 characters.',
        ];
    }
}
