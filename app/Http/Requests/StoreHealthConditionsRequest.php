<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\GlucoseUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class StoreHealthConditionsRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'health_condition_ids' => ['required', 'array'],
            'health_condition_ids.*' => ['integer', 'exists:health_conditions,id'],
            'notes' => ['required', 'array'],
            'notes.*' => ['nullable', 'string', 'max:500'],
            'units_preference' => ['nullable', new Enum(GlucoseUnit::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'health_condition_ids.array' => 'Health conditions must be an array.',
            'health_condition_ids.*.integer' => 'Each health condition must be a valid ID.',
            'health_condition_ids.*.exists' => 'One or more selected health conditions are invalid.',
            'notes.*.max' => 'Notes cannot exceed 500 characters.',
        ];
    }
}
