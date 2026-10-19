<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V2\HealthEntry\ShowMealPhotoController;
use App\Models\MealPhoto;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

covers(ShowMealPhotoController::class);

beforeEach(function (): void {
    Storage::fake(MealPhoto::DISK);
    $this->user = User::factory()->create();
    $this->groupId = (string) Str::uuid();
    $this->photo = MealPhoto::factory()->create(['user_id' => $this->user->id, 'health_group_id' => $this->groupId]);
    Storage::disk(MealPhoto::DISK)->put($this->photo->path, 'jpeg-bytes');
});

it('requires a bearer token', function (): void {
    $this->get(route('api.v2.health-entries.photo', ['group' => $this->groupId]), ['Accept' => 'application/json'])->assertUnauthorized();
});

it('streams the meal photo to its owner', function (): void {
    Sanctum::actingAs($this->user, ['chat:converse']);

    $response = $this->get(route('api.v2.health-entries.photo', ['group' => $this->groupId]))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/jpeg');

    expect($response->headers->get('Cache-Control'))->toContain('private')->toContain('max-age=3600')
        ->and($response->streamedContent())->toBe('jpeg-bytes');
});

it('hides the photo from other people', function (): void {
    Sanctum::actingAs(User::factory()->create(), ['chat:converse']);

    $this->getJson(route('api.v2.health-entries.photo', ['group' => $this->groupId]))->assertNotFound();
});

it('returns not found when the photo file is gone', function (): void {
    Storage::disk(MealPhoto::DISK)->delete($this->photo->path);
    Sanctum::actingAs($this->user, ['chat:converse']);

    $this->getJson(route('api.v2.health-entries.photo', ['group' => $this->groupId]))->assertNotFound();
});
