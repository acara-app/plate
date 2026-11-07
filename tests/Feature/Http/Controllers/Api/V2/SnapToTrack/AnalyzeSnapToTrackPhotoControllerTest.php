<?php

declare(strict_types=1);

use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use App\Http\Controllers\Api\V2\SnapToTrack\AnalyzeSnapToTrackPhotoController;
use App\Models\AiUsage;
use App\Models\AnalysisDraft;
use App\Models\MealPhoto;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Storage;

covers(AnalyzeSnapToTrackPhotoController::class);

function apiSnapPhotoBase64(): string
{
    $file = UploadedFile::fake()->image('meal.jpg', 64, 64);

    return base64_encode((string) file_get_contents((string) $file->getRealPath()));
}

function apiSnapPhotoDataUrl(): string
{
    return 'data:image/jpeg;base64,'.apiSnapPhotoBase64();
}

function fakeApiSnapAnalysis(): void
{
    FoodPhotoAnalyzerAgent::fake([[
        'items' => [
            ['name' => 'Avocado toast', 'calories' => 320, 'protein' => 9, 'carbs' => 30, 'fat' => 19, 'portion' => '1 slice'],
        ],
        'total_calories' => 320,
        'total_protein' => 9,
        'total_carbs' => 30,
        'total_fat' => 19,
        'confidence' => 74,
    ]]);
}

beforeEach(function (): void {
    Storage::fake('s3');
    $this->user = User::factory()->create();
    $this->token = $this->user->createToken('mobile:test', ['chat:converse'])->plainTextToken;
});

it('requires authentication', function (): void {
    $this->postJson(route('api.v2.snap-to-track.analyze'), ['photo' => apiSnapPhotoDataUrl()])
        ->assertUnauthorized();
});

it('analyzes a data url photo into a claimed mobile draft', function (): void {
    fakeApiSnapAnalysis();

    $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson(route('api.v2.snap-to-track.analyze'), ['photo' => apiSnapPhotoDataUrl()]);

    $response->assertOk()
        ->assertJsonStructure(['draft_token', 'expires_at', 'analysis' => ['items', 'total_calories', 'confidence']])
        ->assertJsonPath('analysis.total_calories', 320)
        ->assertJsonPath('analysis.confidence', 74)
        ->assertJsonPath('analysis.items.0.name', 'Avocado toast');

    $draft = AnalysisDraft::query()->sole();

    expect($draft->user_id)->toBe($this->user->id)
        ->and($draft->claimed_at)->not->toBeNull()
        ->and($draft->source->value)->toBe('mobile_snap_to_track')
        ->and($draft->payload['total_calories'])->toEqual(320)
        ->and($response->json('draft_token'))->toBeString()
        ->and($draft->token_hash)->toBe(AnalysisDraft::hashToken((string) $response->json('draft_token')))
        ->and($response->json('expires_at'))->not->toBeEmpty();
});

it('accepts a bare base64 payload without a data url prefix', function (): void {
    fakeApiSnapAnalysis();

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson(route('api.v2.snap-to-track.analyze'), ['photo' => apiSnapPhotoBase64()])
        ->assertOk();

    expect(AnalysisDraft::query()->count())->toBe(1);
});

it('validates the photo payload', function (mixed $photo): void {
    $payload = $photo === null ? [] : ['photo' => $photo];

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson(route('api.v2.snap-to-track.analyze'), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['photo']);

    expect(AnalysisDraft::query()->count())->toBe(0);
})->with([
    'missing' => [null],
    'not base64' => ['@@@not-base64@@@'],
    'base64 of non-image bytes' => [fn (): string => base64_encode('this is definitely not an image')],
    'data url with no payload' => ['data:image/jpeg;base64,'],
]);

it('reports the failure and stores nothing when the analyzer throws', function (): void {
    FoodPhotoAnalyzerAgent::fake(function (): void {
        throw new Exception('AI analysis failed');
    });

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson(route('api.v2.snap-to-track.analyze'), ['photo' => apiSnapPhotoDataUrl()])
        ->assertStatus(500)
        ->assertJsonPath('error', 'analysis_failed');

    expect(AnalysisDraft::query()->count())->toBe(0);
});

it('returns 402 with the allowance and stores nothing when the AI allowance is used up', function (): void {
    enableBilling();
    AiUsage::factory()->create(['user_id' => $this->user->id, 'cost' => 0.50]);
    FoodPhotoAnalyzerAgent::fake(function (): void {
        throw new Exception('Analyzer must not run when the AI allowance is used up');
    });

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson(route('api.v2.snap-to-track.analyze'), ['photo' => apiSnapPhotoDataUrl()])
        ->assertStatus(402)
        ->assertJsonPath('error', 'usage_limit_exceeded')
        ->assertJsonPath('exhausted', true);

    expect(AnalysisDraft::query()->count())->toBe(0);
});

it('localizes the analysis using the Accept-Language header over the account locale', function (): void {
    fakeApiSnapAnalysis();

    $this->user->update(['locale' => 'en']);

    $agent = new FoodPhotoAnalyzerAgent();
    app()->instance(FoodPhotoAnalyzerAgent::class, $agent);

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->withHeader('Accept-Language', 'mn')
        ->postJson(route('api.v2.snap-to-track.analyze'), ['photo' => apiSnapPhotoDataUrl()])
        ->assertOk();

    FoodPhotoAnalyzerAgent::assertPrompted(fn ($prompt): bool => str_contains($prompt->agent->instructions(), 'Монгол'));
});

it('falls back to the account locale when the requested language is unsupported', function (): void {
    fakeApiSnapAnalysis();

    $this->user->update(['locale' => 'mn']);

    $agent = new FoodPhotoAnalyzerAgent();
    app()->instance(FoodPhotoAnalyzerAgent::class, $agent);

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->withHeader('Accept-Language', 'de-DE,de;q=0.9')
        ->postJson(route('api.v2.snap-to-track.analyze'), ['photo' => apiSnapPhotoDataUrl()])
        ->assertOk();

    FoodPhotoAnalyzerAgent::assertPrompted(fn ($prompt): bool => str_contains($prompt->agent->instructions(), 'Монгол'));
});

it('matches a regional Accept-Language tag to its base language', function (): void {
    fakeApiSnapAnalysis();

    $this->user->update(['locale' => 'en']);

    $agent = new FoodPhotoAnalyzerAgent();
    app()->instance(FoodPhotoAnalyzerAgent::class, $agent);

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->withHeader('Accept-Language', 'fr-CA,fr;q=0.9')
        ->postJson(route('api.v2.snap-to-track.analyze'), ['photo' => apiSnapPhotoDataUrl()])
        ->assertOk();

    FoodPhotoAnalyzerAgent::assertPrompted(fn ($prompt): bool => str_contains($prompt->agent->instructions(), 'Français'));
});

it('keeps the photo privately so the logged meal shows it', function (): void {
    fakeApiSnapAnalysis();
    $photo = base64_encode((string) UploadedFile::fake()->image('meal.jpg', 2000, 1500)->get());

    $token = $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson(route('api.v2.snap-to-track.analyze'), ['photo' => 'data:image/jpeg;base64,'.$photo])
        ->assertOk()
        ->json('draft_token');

    $stored = MealPhoto::query()->sole();
    $size = getimagesizefromstring((string) Storage::disk('s3')->get($stored->path));

    expect($stored->user_id)->toBe($this->user->id)
        ->and($stored->analysis_draft_id)->toBe(AnalysisDraft::query()->sole()->id)
        ->and($stored->health_group_id)->toBeNull()
        ->and([$size[0], $size[1], $size['mime']])->toBe([1280, 960, 'image/jpeg']);

    $groupId = $this->postJson(route('api.v2.snap-to-track.meal', ['draft' => $token]), [
        'items' => [['name' => 'Avocado toast', 'portion' => '1 slice', 'calories' => 320, 'protein' => 9, 'carbs' => 30, 'fat' => 19, 'provenance' => 'model']],
        'measured_at' => now()->toIso8601String(),
    ])->assertCreated()->json('group_id');

    expect($stored->refresh()->health_group_id)->toBe($groupId);

    $this->getJson(route('api.v2.health-entries.index'))
        ->assertOk()
        ->assertJsonPath('entries.0.photo_url', '/api/v2/health-entries/'.$groupId.'/photo');

    $this->get(route('api.v2.health-entries.photo', ['group' => $groupId]))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/jpeg');
});

it('still returns the analysis when the photo cannot be kept', function (): void {
    Exceptions::fake();
    fakeApiSnapAnalysis();
    $unreadableGif = 'GIF89a'.pack('vv', 10, 10)."\0\0\0";

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson(route('api.v2.snap-to-track.analyze'), ['photo' => base64_encode($unreadableGif)])
        ->assertOk()
        ->assertJsonPath('analysis.total_calories', 320);

    expect(MealPhoto::query()->count())->toBe(0);
    Exceptions::assertReported(RuntimeException::class);
});
