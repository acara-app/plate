<?php

declare(strict_types=1);

use App\Actions\LogReviewedMealAction;
use App\Data\ReviewedMealData;
use App\Jobs\AggregateUserDayJob;
use App\Models\AnalysisDraft;
use App\Models\HealthSyncSample;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

function reviewedMealForDraft(AnalysisDraft $draft): array
{
    $meal = ReviewedMealData::fromValidated([
        'items' => [
            ['name' => 'Grilled chicken', 'portion' => '100g', 'calories' => 165, 'protein' => 31, 'carbs' => 0, 'fat' => 3.6, 'provenance' => 'model'],
            ['name' => 'Steamed rice', 'calories' => 210, 'protein' => 4, 'carbs' => 45, 'fat' => 0.5, 'provenance' => 'reference'],
        ],
        'measured_at' => now()->subHour()->toDateTimeString(),
        'notes' => 'Lunch bowl',
    ]);

    return [$meal, $meal->toHealthLogData($draft)];
}

beforeEach(function (): void {
    Queue::fake();

    $this->action = resolve(LogReviewedMealAction::class);
    $this->user = User::factory()->create();
});

it('records the reviewed meal as one grouped entry with per-item metadata and consumes the draft', function (): void {
    $draft = AnalysisDraft::factory()->claimed($this->user)->create();
    [, $data] = reviewedMealForDraft($draft);

    $groupId = $this->action->handle($draft, $data, $this->user);

    $samples = HealthSyncSample::query()->where('user_id', $this->user->id)->get();

    expect($samples)->toHaveCount(4)
        ->and($samples->pluck('group_id')->unique()->all())->toBe([$groupId])
        ->and($samples->firstWhere('type_identifier', 'carbohydrates')->value)->toBe(45.0)
        ->and($samples->firstWhere('type_identifier', 'protein')->value)->toBe(35.0)
        ->and($samples->firstWhere('type_identifier', 'totalFat')->value)->toBe(4.1)
        ->and($samples->firstWhere('type_identifier', 'dietaryEnergy')->value)->toBe(375.0)
        ->and($samples->first()->notes)->toBe('Lunch bowl')
        ->and($samples->first()->entry_source->value)->toBe('web');

    $metadata = $samples->first()->metadata['snap_to_track'];

    expect($metadata['source'])->toBe('public_snap_to_track')
        ->and($metadata['confidence'])->toBe(82)
        ->and($metadata['analyzer_version'])->toBe('gemini-3.5-flash/p3')
        ->and($metadata['draft_reference'])->toBe(mb_substr($draft->token_hash, 0, 12))
        ->and($metadata['items'])->toHaveCount(2)
        ->and($metadata['items'][0]['name'])->toBe('Grilled chicken')
        ->and($metadata['items'][1]['provenance'])->toBe('reference');

    expect($draft->refresh()->isConsumed())->toBeTrue()
        ->and($draft->health_group_id)->toBe($groupId);

    Queue::assertPushed(AggregateUserDayJob::class, 1);
});

it('replays an already consumed draft without recording a duplicate entry', function (): void {
    $draft = AnalysisDraft::factory()->claimed($this->user)->create();
    [, $data] = reviewedMealForDraft($draft);

    $firstGroupId = $this->action->handle($draft, $data, $this->user);
    $secondGroupId = $this->action->handle($draft->refresh(), $data, $this->user);

    expect($secondGroupId)->toBe($firstGroupId)
        ->and(HealthSyncSample::query()->where('user_id', $this->user->id)->count())->toBe(4);

    Queue::assertPushed(AggregateUserDayJob::class, 1);
});

it('consumes a claimed draft even after its restore window expired', function (): void {
    $draft = AnalysisDraft::factory()->claimed($this->user)->expired()->create();
    [, $data] = reviewedMealForDraft($draft);

    $groupId = $this->action->handle($draft, $data, $this->user);

    expect($groupId)->not->toBe('')
        ->and($draft->refresh()->isConsumed())->toBeTrue();
});

it('refuses to consume a draft owned by another user', function (): void {
    $other = User::factory()->create();
    $draft = AnalysisDraft::factory()->claimed($other)->create();
    [, $data] = reviewedMealForDraft($draft);

    expect(fn () => $this->action->handle($draft, $data, $this->user))
        ->toThrow(InvalidArgumentException::class);

    expect(HealthSyncSample::query()->count())->toBe(0)
        ->and($draft->refresh()->isConsumed())->toBeFalse();
});
