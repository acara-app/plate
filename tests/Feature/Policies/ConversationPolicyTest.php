<?php

declare(strict_types=1);

use App\Models\Conversation;
use App\Models\User;
use App\Policies\ConversationPolicy;

covers(ConversationPolicy::class);

it('allows owner to view their conversation', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(Conversation::participantAttributes($user));

    $policy = new ConversationPolicy;

    expect($policy->view($user, $conversation))->toBeTrue();
});

it('denies other users from viewing conversation', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $conversation = Conversation::factory()->create(Conversation::participantAttributes($owner));

    $policy = new ConversationPolicy;

    expect($policy->view($other, $conversation))->toBeFalse();
});

it('denies viewAny, update, restore, and forceDelete', function (): void {
    $policy = new ConversationPolicy;

    expect($policy->viewAny())->toBeFalse()
        ->and($policy->update())->toBeFalse()
        ->and($policy->restore())->toBeFalse()
        ->and($policy->forceDelete())->toBeFalse();
});

it('allows owner to delete their conversation and denies others', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $conversation = Conversation::factory()->create(Conversation::participantAttributes($owner));

    $policy = new ConversationPolicy;

    expect($policy->delete($owner, $conversation))->toBeTrue()
        ->and($policy->delete($other, $conversation))->toBeFalse();
});

it('allows create', function (): void {
    $policy = new ConversationPolicy;

    expect($policy->create())->toBeTrue();
});
