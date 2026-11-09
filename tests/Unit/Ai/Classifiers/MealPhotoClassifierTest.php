<?php

declare(strict_types=1);

use App\Ai\Classifiers\MealPhotoClassifier;
use App\Models\MealPhotoClassification;

covers(MealPhotoClassifier::class);

it('passes only clear photos of food without a face', function (array $answers, bool $passes): void {
    $clearMeal = MealPhotoClassification::factory()->raw(['meal_photo_id' => null])['answers'];

    expect(MealPhotoClassifier::passes([...$clearMeal, ...$answers]))->toBe($passes);
})->with([
    'a clear meal' => [[], true],
    'a face' => [['face' => ['probability' => 0.4]], false],
    'not food' => [['subject' => ['probabilities' => ['food' => 0.3]]], false],
    'an unanswered question' => [['image_quality' => []], false],
]);
