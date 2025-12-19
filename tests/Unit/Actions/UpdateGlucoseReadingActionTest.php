<?php

declare(strict_types=1);

use App\Actions\UpdateGlucoseReadingAction;
use App\Models\GlucoseReading;
use App\Models\User;

it('updates a glucose reading', function (): void {
    $user = User::factory()->create();
    $reading = GlucoseReading::factory()->create([
        'user_id' => $user->id,
        'reading_value' => 100,
        'notes' => 'Original notes',
    ]);

    $action = resolve(UpdateGlucoseReadingAction::class);

    $updated = $action->handle($reading, [
        'reading_value' => 150,
        'notes' => 'Updated notes',
    ]);

    expect($updated->reading_value)->toBe(150.0)
        ->and($updated->notes)->toBe('Updated notes');
});
