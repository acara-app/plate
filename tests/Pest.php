<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->beforeEach(function (): void {
        Http::preventStrayRequests();
        Sleep::fake();

        $this->freezeTime();
    })
    ->in('Browser', 'Feature', 'Unit');

expect()->extend('toBeOne', fn () => $this->toBe(1));

function something(): void {}

/**
 * @param  array<string, mixed>|null  $body
 * @return array{'X-Sidecar-Signature': string, 'X-Sidecar-Timestamp': string}
 */
function signSidecarHeaders(?array $body = null, ?string $secret = null, ?int $timestamp = null): array
{
    $secret ??= (string) config('plate.sidecar.hmac_secret');
    $timestamp ??= now()->timestamp;
    $rawBody = $body === null ? '' : json_encode($body, JSON_THROW_ON_ERROR);
    $signature = hash_hmac('sha256', $timestamp.'.'.$rawBody, $secret);

    return [
        'X-Sidecar-Signature' => $signature,
        'X-Sidecar-Timestamp' => (string) $timestamp,
    ];
}
