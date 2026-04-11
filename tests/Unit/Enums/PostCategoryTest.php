<?php

declare(strict_types=1);

use App\Enums\PostCategory;

covers(PostCategory::class);

it('returns correct labels', function (): void {
    expect(PostCategory::ProductUpdates->label())->toBe('Product Updates')
        ->and(PostCategory::NutritionTips->label())->toBe('Nutrition Tips')
        ->and(PostCategory::Recipes->label())->toBe('Recipes')
        ->and(PostCategory::Research->label())->toBe('Research')
        ->and(PostCategory::Lifestyle->label())->toBe('Lifestyle')
        ->and(PostCategory::DiabetesManagement->label())->toBe('Diabetes Management');
});

it('returns correct titles', function (): void {
    expect(PostCategory::ProductUpdates->title())->toBe('Product Updates & Announcements')
        ->and(PostCategory::NutritionTips->title())->toBe('Nutrition Tips: Eat Smarter, Live Better')
        ->and(PostCategory::Recipes->title())->toBe('Healthy Recipes: Balanced Meals & Snacks')
        ->and(PostCategory::Research->title())->toBe('Health Research & Nutrition Science')
        ->and(PostCategory::Lifestyle->title())->toBe('Lifestyle & Wellness')
        ->and(PostCategory::DiabetesManagement->title())->toBe('Diabetes Management & Blood Sugar Control');
});

it('returns correct descriptions', function (): void {
    expect(PostCategory::ProductUpdates->description())->toContain('Acara Plate')
        ->and(PostCategory::NutritionTips->description())->toContain('nutrition')
        ->and(PostCategory::Recipes->description())->toContain('recipes')
        ->and(PostCategory::Research->description())->toContain('research')
        ->and(PostCategory::Lifestyle->description())->toContain('exercising')
        ->and(PostCategory::DiabetesManagement->description())->toContain('diabetes');
});

it('returns correct order values', function (): void {
    expect(PostCategory::ProductUpdates->order())->toBe(1)
        ->and(PostCategory::NutritionTips->order())->toBe(2)
        ->and(PostCategory::Recipes->order())->toBe(3)
        ->and(PostCategory::Research->order())->toBe(4)
        ->and(PostCategory::Lifestyle->order())->toBe(5)
        ->and(PostCategory::DiabetesManagement->order())->toBe(6);
});

it('returns all options via options method', function (): void {
    $options = PostCategory::options();

    expect($options)->toHaveCount(6)
        ->and($options)->toHaveKey(PostCategory::ProductUpdates->value)
        ->and($options[PostCategory::ProductUpdates->value])->toBe('Product Updates');
});
