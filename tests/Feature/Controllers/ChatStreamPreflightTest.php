<?php

declare(strict_types=1);

use App\Enums\AgentMode;
use App\Http\Controllers\ChatController;
use App\Models\AiUsage;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Config;

covers(ChatController::class);

beforeEach(function (): void {
    Config::set('plate.enable_premium_upgrades', true);
    Config::set('plate.ai_usage_preflight', [
        'token_budget' => ['input' => 2_000, 'output' => 1_000],
        'fallback_estimate' => 0.01,
    ]);
});

it('returns HTTP 402 with limit metadata when a Free user is over the rolling cap', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);

    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 0.099,
    ]);

    $countBefore = AiUsage::query()->count();

    $response = $this->actingAs($user)->postJson(route('chat.stream', $conversation->id), [
        'messages' => [
            ['role' => 'user', 'parts' => [['type' => 'text', 'text' => 'Hello, I am over cap']]],
        ],
        'mode' => AgentMode::Ask->value,
    ]);

    $response->assertStatus(402)
        ->assertJson([
            'error' => 'usage_limit_exceeded',
            'limit_type' => 'rolling',
            'tier' => 'free',
        ])
        ->assertJsonStructure([
            'error',
            'limit_type',
            'tier',
            'tier_label',
            'current_credits',
            'limit_credits',
            'resets_at',
        ]);

    expect(AiUsage::query()->count())->toBe($countBefore);
});

it('does not block a Free user who is well under the cap', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);

    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 0.001,
    ]);

    $response = $this->actingAs($user)->postJson(route('chat.stream', $conversation->id), [
        'messages' => [
            ['role' => 'user', 'parts' => [['type' => 'text', 'text' => 'Hello, I am under cap']]],
        ],
        'mode' => AgentMode::Ask->value,
    ]);

    expect($response->getStatusCode())->not->toBe(402);
});

it('does not enforce when premium upgrades are disabled, even for users far over the cap', function (): void {
    Config::set('plate.enable_premium_upgrades', false);

    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);

    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 5.0,
    ]);

    $response = $this->actingAs($user)->postJson(route('chat.stream', $conversation->id), [
        'messages' => [
            ['role' => 'user', 'parts' => [['type' => 'text', 'text' => 'Self-host should work']]],
        ],
        'mode' => AgentMode::Ask->value,
    ]);

    expect($response->getStatusCode())->not->toBe(402);
});
