<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\DietType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreMealPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'duration_days' => ['required', 'integer', 'min:1', 'max:7'],
            'diet_type' => ['nullable', 'string', Rule::enum(DietType::class)],
            'prompt' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
