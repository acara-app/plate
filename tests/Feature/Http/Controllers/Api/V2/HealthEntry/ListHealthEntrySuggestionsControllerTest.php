<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V2\HealthEntry\ListHealthEntrySuggestionsController;
use App\Models\HealthSyncSample;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

covers(ListHealthEntrySuggestionsController::class);

it('requires a bearer token', function (): void {
    $this->getJson(route('api.v2.health-entries.suggestions'))->assertUnauthorized();
});

it('suggests the most recent distinct medications and insulin doses', function (): void {
    $user = User::factory()->create();

    foreach ([['Metformin', '500mg', 1], ['Metformin', '500mg', 2], ['Aspirin', '81mg', 3]] as [$name, $dosage, $minutesAgo]) {
        HealthSyncSample::factory()->medication()->fromWeb()->create([
            'user_id' => $user->id,
            'metadata' => ['medication_name' => $name, 'medication_dosage' => $dosage],
            'created_at' => now()->subMinutes($minutesAgo),
        ]);
    }

    HealthSyncSample::factory()->insulin()->fromWeb()->create(['user_id' => $user->id, 'value' => 10, 'metadata' => ['insulin_type' => 'bolus']]);
    Sanctum::actingAs($user, ['chat:converse']);

    $this->getJson(route('api.v2.health-entries.suggestions'))
        ->assertOk()
        ->assertExactJson([
            'recent_medications' => [
                ['name' => 'Metformin', 'dosage' => '500mg', 'label' => 'Metformin 500mg'],
                ['name' => 'Aspirin', 'dosage' => '81mg', 'label' => 'Aspirin 81mg'],
            ],
            'recent_insulins' => [['units' => 10, 'type' => 'bolus', 'label' => '10u bolus']],
            'todays_meals' => [],
        ]);
});
