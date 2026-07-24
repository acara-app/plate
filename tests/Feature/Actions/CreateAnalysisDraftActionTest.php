<?php

declare(strict_types=1);

use App\Actions\CreateAnalysisDraftAction;
use App\Data\FoodAnalysisData;
use App\Enums\AnalysisDraftSource;
use App\Enums\FoodValueProvenance;
use App\Models\AnalysisDraft;
use App\Models\User;

function analysisForDraft(): FoodAnalysisData
{
    return FoodAnalysisData::from([
        'items' => [
            ['name' => 'Hummus', 'calories' => 166.0, 'protein' => 7.9, 'carbs' => 14.3, 'fat' => 9.6, 'portion' => '100g', 'grams' => 100.0, 'match_name' => 'hummus commercial', 'provenance' => 'reference'],
            ['name' => 'Pita', 'calories' => 250.0, 'protein' => 8.0, 'carbs' => 50.0, 'fat' => 2.0, 'portion' => '80g'],
        ],
        'total_calories' => 416.0,
        'total_protein' => 15.9,
        'total_carbs' => 64.3,
        'total_fat' => 11.6,
        'confidence' => 82,
        'analyzer_version' => 'gemini-3.5-flash/p3',
    ]);
}

beforeEach(function (): void {
    $this->action = resolve(CreateAnalysisDraftAction::class);
});

it('stores a draft under the hashed token and returns the raw token', function (): void {
    $token = $this->action->handle(analysisForDraft(), AnalysisDraftSource::PublicSnapToTrack);

    expect(mb_strlen($token))->toBe(64);

    $draft = AnalysisDraft::query()->sole();

    expect($draft->token_hash)->toBe(hash('sha256', $token))
        ->and($draft->token_hash)->not->toBe($token)
        ->and($draft->schema_version)->toBe(AnalysisDraft::SCHEMA_VERSION)
        ->and($draft->source)->toBe(AnalysisDraftSource::PublicSnapToTrack)
        ->and($draft->user_id)->toBeNull()
        ->and($draft->claimed_at)->toBeNull()
        ->and($draft->consumed_at)->toBeNull()
        ->and($draft->expires_at->getTimestamp())->toBe(now()->addMinutes(AnalysisDraft::TTL_MINUTES)->getTimestamp());
});

it('claims the draft at creation for an authenticated user', function (): void {
    $user = User::factory()->create();

    $this->action->handle(analysisForDraft(), AnalysisDraftSource::AuthenticatedSnapToTrack, $user->id);

    $draft = AnalysisDraft::query()->sole();

    expect($draft->user_id)->toBe($user->id)
        ->and($draft->claimed_at)->not->toBeNull()
        ->and($draft->source)->toBe(AnalysisDraftSource::AuthenticatedSnapToTrack);
});

it('rehydrates the stored payload into the identical analysis result', function (): void {
    $analysis = analysisForDraft();

    $this->action->handle($analysis, AnalysisDraftSource::PublicSnapToTrack);

    $draft = AnalysisDraft::query()->sole();
    $restored = FoodAnalysisData::from($draft->payload);

    expect($restored->toArray())->toBe($analysis->toArray())
        ->and($restored->items->first()->provenance)->toBe(FoodValueProvenance::Reference)
        ->and($restored->analyzerVersion)->toBe('gemini-3.5-flash/p3')
        ->and($restored->totalCalories)->toBe(416.0);
});
