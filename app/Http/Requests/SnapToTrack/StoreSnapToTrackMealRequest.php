<?php

declare(strict_types=1);

namespace App\Http\Requests\SnapToTrack;

use App\Http\Requests\Concerns\ValidatesReviewedMeal;
use Illuminate\Foundation\Http\FormRequest;

final class StoreSnapToTrackMealRequest extends FormRequest
{
    use ValidatesReviewedMeal {
        rules as reviewedMealRules;
    }

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
            ...$this->reviewedMealRules(),
            'photo' => ['nullable', 'image', 'max:10240'],
        ];
    }
}
