<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Sex;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreBiometricsRequest extends FormRequest
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
            'age' => ['required', 'integer', 'min:13', 'max:120'],
            'height' => ['required', 'numeric', 'min:50', 'max:300'],
            'weight' => ['required', 'numeric', 'min:20', 'max:500'],
            'sex' => ['required', Rule::enum(Sex::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'age.required' => 'Please provide your age.',
            'age.min' => 'You must be at least 13 years old to use this service.',
            'age.max' => 'Please enter a valid age.',
            'height.required' => 'Please provide your height.',
            'height.min' => 'Please enter a valid height in centimeters.',
            'height.max' => 'Please enter a valid height in centimeters.',
            'weight.required' => 'Please provide your current weight.',
            'weight.min' => 'Please enter a valid weight in kilograms.',
            'weight.max' => 'Please enter a valid weight in kilograms.',
            'sex.required' => 'Please select your biological sex.',
        ];
    }
}
