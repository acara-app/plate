<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreLifestyleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'lifestyle_id' => ['required', 'integer', 'exists:lifestyles,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lifestyle_id.required' => 'Please select your activity level.',
            'lifestyle_id.exists' => 'The selected lifestyle is invalid.',
        ];
    }
}
