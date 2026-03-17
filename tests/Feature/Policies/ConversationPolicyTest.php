<?php

declare(strict_types=1);

use App\Models\Conversation;
use App\Models\User;
use App\Policies\ConversationPolicy;

covers(ConversationPolicy::class);

it('allows owner to view their conversation', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);

    $policy = new ConversationPolicy;

    expect($policy->view($user, $conversation))->toBeTrue();
});

it('denies other users from viewing conversation', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $owner->id]);

    $policy = new ConversationPolicy;

    expect($policy->view($other, $conversation))->toBeFalse();
});

it('denies viewAny, update, delete, restore, and forceDelete', function (): void {
    $policy = new ConversationPolicy;

    expect($policy->viewAny())->toBeFalse()
        ->and($policy->update())->toBeFalse()
        ->and($policy->delete())->toBeFalse()
        ->and($policy->restore())->toBeFalse()
        ->and($policy->forceDelete())->toBeFalse();
});

it('allows create', function (): void {
    $policy = new ConversationPolicy;

    expect($policy->create())->toBeTrue();
});
