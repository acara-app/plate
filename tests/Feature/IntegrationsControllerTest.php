<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\UserTelegramChat;

it('renders integrations page', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('integrations.edit'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('integrations/edit'));
});

it('shows connected state when telegram is linked', function (): void {
    $user = User::factory()->create();
    UserTelegramChat::factory()->for($user)->create([
        'is_active' => true,
        'linked_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('integrations.edit'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('telegram')
            ->where('telegram.is_connected', true)
        );
});

it('shows disconnected state when no telegram link exists', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('integrations.edit'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('telegram')
            ->where('telegram.is_connected', false)
        );
});

it('generates telegram token', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('integrations.telegram.token'))
        ->assertRedirect(route('integrations.edit'))
        ->assertSessionHas('telegram_token');

    expect($user->fresh()->telegramChat)
        ->not->toBeNull()
        ->is_active->toBeTrue();
});

it('deactivates existing links when generating new token', function (): void {
    $user = User::factory()->create();
    $oldLink = UserTelegramChat::factory()->for($user)->create([
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->post(route('integrations.telegram.token'))
        ->assertRedirect();

    expect($oldLink->fresh()->is_active)->toBeFalse();
    expect($user->fresh()->telegramChat)->not->toBeNull();
});

it('disconnects telegram integration', function (): void {
    $user = User::factory()->create();
    UserTelegramChat::factory()->for($user)->create([
        'is_active' => true,
        'linked_at' => now(),
    ]);

    $this->actingAs($user)
        ->delete(route('integrations.telegram.destroy'))
        ->assertRedirect(route('integrations.edit'));

    expect($user->fresh()->telegramChat->is_active)->toBeFalse();
});

it('requires authentication to view integrations', function (): void {
    $this->get(route('integrations.edit'))
        ->assertRedirect(route('login'));
});

it('requires authentication to generate token', function (): void {
    $this->post(route('integrations.telegram.token'))
        ->assertRedirect(route('login'));
});

it('requires authentication to disconnect', function (): void {
    $this->delete(route('integrations.telegram.destroy'))
        ->assertRedirect(route('login'));
});
