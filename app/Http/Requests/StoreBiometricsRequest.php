<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\BloodType;
use App\Enums\Sex;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreBiometricsRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'age' => ['required', 'integer', 'min:13', 'max:120'],
            'date_of_birth' => ['nullable', 'date', 'before:today', 'after:1900-01-01'],
            'height' => ['required', 'numeric', 'min:50', 'max:300'],
            'weight' => ['required', 'numeric', 'min:20', 'max:500'],
            'sex' => ['required', Rule::enum(Sex::class)],
            'blood_type' => ['nullable', Rule::enum(BloodType::class)],
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
            'date_of_birth.before' => 'Date of birth must be in the past.',
            'date_of_birth.after' => 'Please enter a valid date of birth.',
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
