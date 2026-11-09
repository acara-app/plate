<?php

declare(strict_types=1);

use App\Models\MealPhotoClassification;

covers(MealPhotoClassification::class);

it('passes only clear photos of food without a face', function (array $answers, bool $passes): void {
    $classification = MealPhotoClassification::factory()->make(['meal_photo_id' => 1]);
    $classification->answers = [...$classification->answers, ...$answers];

    expect($classification->passesScreening())->toBe($passes);
})->with([
    'a clear meal' => [[], true],
    'a face' => [['face' => ['probability' => 0.4]], false],
    'not food' => [['subject' => ['probabilities' => ['food' => 0.3]]], false],
    'an unanswered question' => [['image_quality' => []], false],
]);
