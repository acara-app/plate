<?php

declare(strict_types=1);

use App\Jobs\ProcessChatStream;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

it('sends an initial prompt automatically once when no draft option is requested', function (): void {
    Queue::fake();
    $user = User::factory()->create();
    $this->actingAs($user);
    $conversationId = (string) Str::uuid();
    $prompt = 'Create a seven-day meal plan.';

    visit(route('chat.create', [
        'conversationId' => $conversationId,
        'prompt' => $prompt,
    ], absolute: false))
        ->assertSee($prompt)
        ->assertQueryStringMissing('prompt')
        ->assertValue('textarea[aria-label="Message input"]', '')
        ->refresh()
        ->assertSee($prompt)
        ->assertValue('textarea[aria-label="Message input"]', '')
        ->assertNoJavaScriptErrors();

    Queue::assertPushed(ProcessChatStream::class, 1);
    $this->assertDatabaseHas('agent_conversation_messages', [
        'conversation_id' => $conversationId,
        'role' => 'user',
        'content' => $prompt,
    ]);
});
