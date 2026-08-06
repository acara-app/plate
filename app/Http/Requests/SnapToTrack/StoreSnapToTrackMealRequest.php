<?php

declare(strict_types=1);

namespace App\Http\Requests\SnapToTrack;

use App\Http\Requests\Concerns\ValidatesReviewedMeal;
use Illuminate\Foundation\Http\FormRequest;

final class StoreSnapToTrackMealRequest extends FormRequest
{
    use ValidatesReviewedMeal;

    public function authorize(): bool
    {
        return true;
    }
}
