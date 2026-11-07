<?php

declare(strict_types=1);

use App\Http\Controllers\BroadcastChatController;
use App\Models\AiUsage;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;

covers(BroadcastChatController::class);

beforeEach(function (): void {
    enableBilling();
    Config::set('plate.ai_usage_preflight', [
        'token_budget' => ['input' => 2_000, 'output' => 1_000],
        'fallback_estimate' => 0.01,
    ]);
});

it('returns HTTP 402 with limit metadata when a Free user is out of free AI this month', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(Conversation::participantAttributes($user));

    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 0.5,
    ]);

    $countBefore = AiUsage::query()->count();

    $response = $this->actingAs($user)->postJson(route('chat.stream', $conversation->id), [
        'messages' => [
            ['role' => 'user', 'parts' => [['type' => 'text', 'text' => 'Hello, I am over cap']]],
        ],
    ]);

    $response->assertStatus(402)
        ->assertJson([
            'error' => 'usage_limit_exceeded',
            'tier' => 'free',
            'resets' => 'monthly',
            'exhausted' => true,
        ]);

    expect(AiUsage::query()->count())->toBe($countBefore);
});

it('does not block a Free user who is well under the cap', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(Conversation::participantAttributes($user));

    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 0.001,
    ]);

    $response = $this->actingAs($user)->postJson(route('chat.stream', $conversation->id), [
        'messages' => [
            ['role' => 'user', 'parts' => [['type' => 'text', 'text' => 'Hello, I am under cap']]],
        ],
    ]);

    expect($response->getStatusCode())->toBe(202);
});

it('does not enforce when billing is off, even for users far over the cap', function (): void {
    Queue::fake();

    disableBilling();

    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(Conversation::participantAttributes($user));

    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 5.0,
    ]);

    $response = $this->actingAs($user)->postJson(route('chat.stream', $conversation->id), [
        'messages' => [
            ['role' => 'user', 'parts' => [['type' => 'text', 'text' => 'Self-host should work']]],
        ],
    ]);

    expect($response->getStatusCode())->toBe(202);
});
