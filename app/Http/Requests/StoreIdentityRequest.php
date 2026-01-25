<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\AnimalProductChoice;
use App\Enums\GoalChoice;
use App\Enums\IntensityChoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreIdentityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

/**
 * @return array<string, array<int, string|\Illuminate\Validation\Rules\Enum>>
 */
public function rules(): array
{
    return [
        'goal_choice' => ['required', Rule::enum(GoalChoice::class)],
        'animal_product_choice' => ['required', Rule::enum(AnimalProductChoice::class)],
        'intensity_choice' => ['required', Rule::enum(IntensityChoice::class)],
    ];
}
}
