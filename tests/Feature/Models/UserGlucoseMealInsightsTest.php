<?php

declare(strict_types=1);

use App\Models\User;

it('defaults the glucose meal-insight opt-in to off', function (): void {
    expect(User::factory()->create()->wantsGlucoseMealInsights())->toBeFalse();
});

it('reflects the opt-in when the user has enabled it', function (): void {
    $user = User::factory()->create(['settings' => ['glucose_meal_insights_enabled' => true]]);

    expect($user->wantsGlucoseMealInsights())->toBeTrue();
});
