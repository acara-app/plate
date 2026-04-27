<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PlanBrewBuddyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'prompt' => ['required', 'string', 'max:2000'],
            'weight_kg' => ['nullable', 'numeric', 'min:30', 'max:200'],
            'bedtime' => ['nullable', 'date_format:H:i'],
            'sensitivity' => ['nullable', 'in:low,normal,high'],
        ];
    }
}
