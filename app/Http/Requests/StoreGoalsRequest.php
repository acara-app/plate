<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreGoalsRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'goal_id' => ['nullable', 'integer', 'exists:goals,id'],
            'target_weight' => ['nullable', 'numeric', 'min:20', 'max:500'],
            'additional_goals' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'goal_id.required' => 'Please select your primary goal.',
            'goal_id.exists' => 'The selected goal is invalid.',
            'target_weight.numeric' => 'Target weight must be a number.',
            'target_weight.min' => 'Please enter a valid target weight.',
            'target_weight.max' => 'Please enter a valid target weight.',
            'additional_goals.max' => 'Additional goals cannot exceed 1000 characters.',
        ];
    }
}
