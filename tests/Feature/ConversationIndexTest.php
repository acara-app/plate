<?php

declare(strict_types=1);

use App\Http\Controllers\ChatController;
use App\Models\Conversation;
use App\Models\User;

use function Pest\Laravel\actingAs;

covers(ChatController::class);

it('renders conversations index page', function (): void {
    $this->withoutVite();

    $user = User::factory()->create();

    actingAs($user)
        ->get(route('chat.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('chat/index'));
});

it('lists only the authenticated user conversations', function (): void {
    $this->withoutVite();

    $user = User::factory()->create();
    $other = User::factory()->create();

    Conversation::factory()->forUser($user)->count(3)->create();
    Conversation::factory()->forUser($other)->count(2)->create();

    actingAs($user)
        ->get(route('chat.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('conversations.data', 3));
});

it('orders conversations by most recent first', function (): void {
    $this->withoutVite();

    $user = User::factory()->create();

    Conversation::factory()->forUser($user)->create([
        'created_at' => now()->subDay(),
        'updated_at' => now()->subDay(),
    ]);
    $newest = Conversation::factory()->forUser($user)->create([
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    actingAs($user)
        ->get(route('chat.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('conversations.data.0.id', $newest->id)
        );
});

it('requires authentication', function (): void {
    $this->get(route('chat.index'))
        ->assertRedirect(route('login'));
});
