<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\MealPlan;
use Illuminate\Foundation\Http\FormRequest;

final class RegenerateMealPlanDayRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var MealPlan|null $mealPlan */
        $mealPlan = $this->route('mealPlan');

        return $mealPlan !== null && $mealPlan->user_id === $this->user()?->id;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        /** @var MealPlan $mealPlan */
        $mealPlan = $this->route('mealPlan');

        $maxDay = (int) $mealPlan->duration_days;

        return [
            'day' => [
                'required',
                'integer',
                'min:1',
                'max:'.$maxDay,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        /** @var MealPlan $mealPlan */
        $mealPlan = $this->route('mealPlan');

        return [
            'day.integer' => 'The day must be a valid number.',
            'day.min' => 'The day must be at least 1.',
            'day.max' => sprintf("The day cannot exceed the meal plan's duration of %d days.", $mealPlan->duration_days),
        ];
    }
}
