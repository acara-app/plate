<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreMealPlanDurationRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'meal_plan_days' => ['required', 'integer', 'min:1', 'max:7'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'meal_plan_days.required' => 'Please select how many days of meals you want.',
            'meal_plan_days.integer' => 'Meal plan days must be a number.',
            'meal_plan_days.min' => 'You must select at least 1 day.',
            'meal_plan_days.max' => 'You can select a maximum of 7 days.',
        ];
    }
}
