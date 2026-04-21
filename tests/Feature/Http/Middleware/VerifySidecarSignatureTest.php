<?php

declare(strict_types=1);

use App\Http\Middleware\VerifySidecarSignature;

covers(VerifySidecarSignature::class);

const CHAT_TURNS_URL = '/api/v2/messaging/platforms/mock/users/alice/chat-turns';

it('rejects requests missing signature headers', function (): void {
    $this->postJson(CHAT_TURNS_URL, ['message' => 'hi'])
        ->assertUnauthorized()
        ->assertJson(['error' => 'missing_signature']);
});

it('rejects requests with a non-numeric timestamp', function (): void {
    $this->withHeaders([
        'X-Sidecar-Timestamp' => 'not-a-number',
        'X-Sidecar-Signature' => 'abc',
    ])->postJson(CHAT_TURNS_URL, ['message' => 'hi'])
        ->assertUnauthorized()
        ->assertJson(['error' => 'missing_signature']);
});

it('rejects requests with a stale timestamp', function (): void {
    $body = ['message' => 'hi'];
    $staleTimestamp = now()->subMinutes(5)->timestamp;

    $this->withHeaders(signSidecarHeaders($body, timestamp: $staleTimestamp))
        ->postJson(CHAT_TURNS_URL, $body)
        ->assertUnauthorized()
        ->assertJson(['error' => 'stale_timestamp']);
});

it('rejects requests signed with the wrong secret', function (): void {
    $body = ['message' => 'hi'];

    $this->withHeaders(signSidecarHeaders($body, secret: 'the-wrong-secret-1234567890'))
        ->postJson(CHAT_TURNS_URL, $body)
        ->assertUnauthorized()
        ->assertJson(['error' => 'invalid_signature']);
});

it('accepts a correctly signed POST', function (): void {
    $body = ['message' => 'hi'];

    $this->withHeaders(signSidecarHeaders($body))
        ->postJson(CHAT_TURNS_URL, $body)
        ->assertStatus(409)
        ->assertJsonStructure(['linking_code', 'expires_at']);
});
