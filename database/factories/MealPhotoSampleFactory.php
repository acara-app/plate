<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Ai\Classifiers\MealPhotoClassifier;
use App\Models\MealPhotoClassification;
use App\Models\MealPhotoSample;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @extends Factory<MealPhotoSample>
 */
final class MealPhotoSampleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'meal_photo_id' => null,
            'path' => fn (array $attributes): string => MealPhotoSample::DIRECTORY.'/'.Arr::integer($attributes, 'user_id').'/'.Str::uuid().'.jpg',
            'version' => MealPhotoClassifier::VERSION,
            'answers' => MealPhotoClassification::factory()->raw(['meal_photo_id' => null])['answers'],
        ];
    }
}
