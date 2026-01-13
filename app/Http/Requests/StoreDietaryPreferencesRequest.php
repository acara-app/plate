<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\AllergySeverity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class StoreDietaryPreferencesRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'dietary_preference_ids' => ['nullable', 'array'],
            'dietary_preference_ids.*' => ['integer', 'exists:dietary_preferences,id'],
            'severities' => ['nullable', 'array'],
            'severities.*' => ['nullable', new Enum(AllergySeverity::class)],
            'notes' => ['nullable', 'array'],
            'notes.*' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'dietary_preference_ids.array' => 'Dietary preferences must be an array.',
            'dietary_preference_ids.*.integer' => 'Each dietary preference must be a valid ID.',
            'dietary_preference_ids.*.exists' => 'One or more selected dietary preferences are invalid.',
            'severities.array' => 'Severities must be an array.',
            'severities.*.Illuminate\Validation\Rules\Enum' => 'Invalid severity level selected.',
            'notes.array' => 'Notes must be an array.',
            'notes.*.max' => 'Notes cannot exceed 500 characters.',
        ];
    }
}
