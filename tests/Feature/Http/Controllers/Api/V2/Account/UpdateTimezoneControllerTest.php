<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V2\Account\UpdateTimezoneController;
use App\Models\User;
use Carbon\CarbonImmutable;
use Laravel\Sanctum\Sanctum;

covers(UpdateTimezoneController::class);

it('requires a bearer token', function (): void {
    $this->putJson(route('api.v2.account.timezone'), ['timezone' => 'Asia/Ulaanbaatar'])->assertUnauthorized();
});

it('saves the device timezone so logbook days follow it', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-18 17:00:00', 'UTC'));
    $user = User::factory()->create(['timezone' => null]);
    Sanctum::actingAs($user, ['chat:converse']);

    $this->putJson(route('api.v2.account.timezone'), ['timezone' => 'Asia/Ulaanbaatar'])->assertNoContent();

    expect($user->fresh()->timezone)->toBe('Asia/Ulaanbaatar');

    $this->getJson(route('api.v2.health-entries.index'))
        ->assertOk()
        ->assertJsonPath('today', '2026-10-19');
});

it('accepts every zone name a device can report, including aliases', function (string $timezone): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['chat:converse']);

    $this->putJson(route('api.v2.account.timezone'), ['timezone' => $timezone])->assertNoContent();

    expect($user->fresh()->timezone)->toBe($timezone);
})->with(['Europe/Kyiv', 'America/Nuuk', 'Asia/Yangon', 'GMT', 'Asia/Katmandu', 'Asia/Saigon', 'Atlantic/Faeroe']);

it('rejects names that are not timezones', function (mixed $timezone): void {
    Sanctum::actingAs(User::factory()->create(), ['chat:converse']);

    $this->putJson(route('api.v2.account.timezone'), ['timezone' => $timezone])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('timezone');
})->with(['made up' => ['Mars/Olympus'], 'empty' => [''], 'missing' => [null]]);
