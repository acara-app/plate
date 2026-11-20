<?php

declare(strict_types=1);

use App\Ai\Agents\AgentRunner;
use App\Jobs\ProcessChatStream;
use App\Models\Conversation;
use App\Models\HealthDailyAggregate;
use App\Models\User;
use App\Services\StreamEventStore;
use Illuminate\Broadcasting\AnonymousEvent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Sanctum\Sanctum;

it('reopens a chart reply and a subsequent prose-only reply on mobile', function (): void {
    $example = File::json(base_path('tests/Fixtures/chat-weekly-steps.json'));
    $saved = $example['savedMessage'];
    $user = User::factory()->create(['locale' => 'en']);
    $conversation = Conversation::factory()->forUser($user)->create();
    $readings = [
        '2026-11-09' => 4200,
        '2026-11-10' => 0,
        '2026-11-12' => 7900,
        '2026-11-13' => 4500,
        '2026-11-14' => 6100,
        '2026-11-15' => 3300,
    ];

    foreach ($readings as $date => $steps) {
        HealthDailyAggregate::factory()->for($user)->stepCount()->create([
            'date' => $date,
            'local_date' => $date,
            'value_sum' => $steps,
            'value_sum_canonical' => $steps,
        ]);
    }

    AgentRunner::fake([
        new ToolCall('call_weekly_steps', 'show_weekly_steps', ['date' => '2026-11-15']),
        $saved['parts'][0]['text'],
        'Hello! How can I help?',
    ])->preventStrayPrompts();
    Queue::fake()->except(ProcessChatStream::class);
    Event::fake([AnonymousEvent::class]);
    $events = $this->spy(StreamEventStore::class);

    $this->actingAs($user)->postJson(route('chat.stream', $conversation), [
        'messages' => [['role' => 'user', 'parts' => [['type' => 'text', 'text' => 'How did my steps vary the week ending November 15?']]]],
    ])->assertAccepted();

    $expected = $example['liveEvent'];
    Event::assertDispatched(AnonymousEvent::class, fn (AnonymousEvent $event): bool => $event->broadcastAs() === $expected['type']
        && $event->broadcastWith()['conversationId'] === $conversation->id
        && $event->broadcastWith()['part'] === $expected['part']);
    $events->shouldHaveReceived('append')->withArgs(fn (string $id, array $event): bool => $id === $conversation->id
        && $event['type'] === $expected['type'] && $event['part'] === $expected['part'])->once();

    $this->postJson(route('chat.stream', $conversation), [
        'messages' => [['role' => 'user', 'parts' => [['type' => 'text', 'text' => 'Hello!']]]],
    ])->assertAccepted();

    Sanctum::actingAs($user, ['chat:converse']);
    $this->getJson(route('api.v2.chat.show', $conversation))
        ->assertOk()
        ->assertJsonPath('messages.1.role', $saved['role'])
        ->assertJsonPath('messages.1.parts', $saved['parts'])
        ->assertJsonPath('messages.3.parts', [['type' => 'text', 'text' => 'Hello! How can I help?']]);
});
