<?php

declare(strict_types=1);

use App\Enums\ChatPlatform;
use App\Http\Controllers\IntegrationsController;
use App\Models\User;
use App\Models\UserChatPlatformLink;

covers(IntegrationsController::class);

it('renders integrations page with every platform represented', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('integrations.edit'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('integrations/edit')
            ->has('platforms', count(ChatPlatform::cases()))
            ->where('platforms.0.platform', ChatPlatform::Telegram->value)
            ->where('platforms.0.is_connected', false)
        );
});

it('shows connected state when a platform is linked', function (): void {
    $user = User::factory()->create();
    UserChatPlatformLink::factory()->linked($user)->create();

    $this->actingAs($user)
        ->get(route('integrations.edit'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('platforms.0.is_connected', true)
            ->where('platforms.0.platform', ChatPlatform::Telegram->value)
        );
});

it('generates a linking token for a platform', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('integrations.platform.token', ['platform' => ChatPlatform::Telegram->value]))
        ->assertRedirect(route('integrations.edit'))
        ->assertSessionHas('linking_token')
        ->assertSessionHas('linking_platform', ChatPlatform::Telegram->value);

    $link = $user->fresh()->chatPlatformLinks()->where('is_active', true)->first();
    expect($link)->not->toBeNull();
    expect($link->platform)->toBe(ChatPlatform::Telegram);
    expect($link->linking_token)->toMatch('/^[A-Z0-9]{8}$/');
});

it('deactivates existing links when generating new token', function (): void {
    $user = User::factory()->create();
    $old = UserChatPlatformLink::factory()->linked($user)->create();

    $this->actingAs($user)
        ->post(route('integrations.platform.token', ['platform' => ChatPlatform::Telegram->value]))
        ->assertRedirect();

    expect($old->fresh()->is_active)->toBeFalse();
    expect($user->chatPlatformLinks()->where('is_active', true)->count())->toBe(1);
});

it('disconnects a platform', function (): void {
    $user = User::factory()->create();
    UserChatPlatformLink::factory()->linked($user)->create();

    $this->actingAs($user)
        ->delete(route('integrations.platform.destroy', ['platform' => ChatPlatform::Telegram->value]))
        ->assertRedirect(route('integrations.edit'));

    expect($user->chatPlatformLinks()->where('is_active', true)->count())->toBe(0);
});

it('rejects unknown platform values with 404', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/settings/integrations/whatsapp/token')
        ->assertNotFound();
});

it('requires authentication to view integrations', function (): void {
    $this->get(route('integrations.edit'))
        ->assertRedirect(route('login'));
});

it('requires authentication to generate token', function (): void {
    $this->post(route('integrations.platform.token', ['platform' => ChatPlatform::Telegram->value]))
        ->assertRedirect(route('login'));
});

it('requires authentication to disconnect', function (): void {
    $this->delete(route('integrations.platform.destroy', ['platform' => ChatPlatform::Telegram->value]))
        ->assertRedirect(route('login'));
});
