<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Ai\Classifiers\MealPhotoClassifier;
use App\Models\MealPhoto;
use App\Models\MealPhotoClassification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MealPhotoClassification>
 */
final class MealPhotoClassificationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'meal_photo_id' => MealPhoto::factory(),
            'version' => MealPhotoClassifier::VERSION,
            'provider' => 'openai',
            'model' => 'gpt-6-luna',
            'answers' => [
                'subject' => [
                    'choice' => 'food',
                    'probabilities' => ['food' => 0.96, 'packaged_food' => 0.01, 'nutrition_label' => 0.0, 'printed_text' => 0.0, 'screen' => 0.02, 'other' => 0.01],
                    'confidence' => 0.93,
                ],
                'face' => ['probability' => 0.01],
                'personal_details' => ['probability' => 0.01],
                'image_quality' => [
                    'score' => 2.6,
                    'probabilities' => [0.01, 0.04, 0.3, 0.65],
                    'confidence' => 0.58,
                ],
                'food_type' => ['choice' => 'mixed_meal', 'probabilities' => ['mixed_meal' => 0.82, 'protein' => 0.1, 'grains' => 0.08], 'confidence' => 0.7],
                'cuisine' => ['choice' => 'chinese', 'probabilities' => ['chinese' => 0.88, 'japanese' => 0.07, 'korean' => 0.05], 'confidence' => 0.79],
                'dish_type' => ['choice' => 'whole', 'probabilities' => ['whole' => 0.9, 'mixed' => 0.1], 'confidence' => 0.8],
            ],
        ];
    }
}
