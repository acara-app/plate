<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
        ];
    }
}
