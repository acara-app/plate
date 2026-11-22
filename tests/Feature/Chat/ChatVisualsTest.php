<?php

declare(strict_types=1);

use App\Actions\CreatePendingChatStreamTurn;
use App\Ai\Agents\AgentRunner;
use App\Ai\Agents\HealthSpecialist;
use App\Enums\SettingKey;
use App\Http\Middleware\HandleInertiaRequests;
use App\Jobs\ProcessChatStream;
use App\Models\Conversation;
use App\Models\HealthDailyAggregate;
use App\Models\History;
use App\Models\Setting;
use App\Models\User;
use App\Services\Ai\PlateConversationStore;
use App\Services\ReplyComposer;
use Illuminate\Broadcasting\AnonymousEvent;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Laravel\Ai\Messages\AssistantMessage;
use Laravel\Ai\Messages\MessageRole;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    fakeChatVisualsRedis();
    Queue::fake()->except(ProcessChatStream::class);
    Event::fake([AnonymousEvent::class]);
});

it('streams, replays and saves a reply with its visuals in the order Altani wrote them', function (string $scenario, string $prompt, array $outcomes, array $toolCalls = [], array $steps = [], array $specialist = []): void {
    $user = User::factory()->create(['locale' => 'en', 'timezone' => 'UTC']);
    $conversation = Conversation::factory()->forUser($user)->create();
    AgentRunner::fake([...$toolCalls, scriptedReply($scenario)])->preventStrayPrompts();
    HealthSpecialist::fake($specialist)->preventStrayPrompts();

    foreach ($steps as $day => $count) {
        HealthDailyAggregate::factory()->for($user)->stepCount()->create(['local_date' => $day, 'date' => $day, 'value_sum' => $count]);
    }

    $this->actingAs($user)->postJson(route('chat.stream', $conversation->id), [
        'messages' => [['role' => 'user', 'parts' => [['type' => 'text', 'text' => $prompt]]]],
        'catalog' => ['name' => 'acara-chat', 'version' => 1],
    ])->assertAccepted();

    Sanctum::actingAs($user, ['chat:converse']);
    $events = json_decode($this->getJson(route('api.v2.chat.stream.events', $conversation->id))->assertOk()->getContent())->events;
    $message = json_decode($this->getJson(route('api.v2.chat.show', $conversation->id))->assertOk()->getContent())->messages[1];
    $page = json_decode($this->actingAs($user)->get(route('chat.create', ['conversationId' => $conversation->id]), [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => (string) resolve(HandleInertiaRequests::class)->version(request()),
    ])->assertOk()->getContent());
    $replyTypes = ['text_delta', 'visual_patch', 'visual_end'];
    $stored = array_values(array_filter($events, fn (stdClass $event): bool => in_array($event->type, $replyTypes, true)));
    $broadcast = Event::dispatched(AnonymousEvent::class, fn (AnonymousEvent $event): bool => in_array($event->broadcastAs(), $replyTypes, true))
        ->map(fn (array $arguments): array => $arguments[0]->broadcastWith())
        ->values()
        ->all();
    $streamedOutcomes = array_values(array_map(
        fn (stdClass $event): string => $event->data->accepted ? 'accepted' : $event->data->reason,
        array_filter($stored, fn (stdClass $event): bool => $event->type === 'visual_end'),
    ));
    $assistant = assistantReply($conversation);

    expect(json_encode($broadcast))->toBe(json_encode(array_column($stored, 'data')));
    expect(array_column($broadcast, 'conversation_id'))->each->toBe($conversation->id);
    expect(array_column($broadcast, 'sequence'))->toBe(array_column($stored, 'sequence'));
    expect(array_map(fn (array $payload): int => mb_strlen((string) json_encode((string) json_encode($payload))), $broadcast))->each->toBeLessThanOrEqual(8_000);
    expect($streamedOutcomes)->toBe($outcomes);
    expect(collect($message->parts)->where('type', 'data-spec'))->toHaveCount(count(array_keys($outcomes, 'accepted', true)));
    expect(json_encode($page->props->messages[1]->parts))->toBe(json_encode($message->parts));
    expect($assistant->content)->not->toContain('```spec');

    expectChatVisualsGolden($scenario, [
        'catalog' => ['name' => 'acara-chat', 'version' => 1],
        'events' => $events,
        'message' => $message,
    ], [
        $conversation->id => 'conversation',
        $assistant->id => 'assistant-message',
        (string) $assistant->chatStreamMeta()['user_message_id'] => 'user-message',
    ]);
})->with([
    'a visual between two paragraphs' => ['prose-visual-prose', 'Give me a 20-minute beginner walking workout I can follow.', ['accepted']],
    'two visuals, one holding an empty object' => ['two-visuals', 'Will a big bowl of white rice with teriyaki chicken spike my blood sugar?', ['accepted', 'accepted']],
    'a visual Plate rejects' => ['rejected-visual', 'Is oatmeal or white rice better for my blood sugar?', ['unknown_component']],
    'a chart released in several events' => ['chart-in-several-events', 'Show me a six-month step plan.', ['accepted']],
    'a chart bound to the steps a health tool fetched' => [
        'bound-week',
        'How did my steps go this week?',
        ['accepted'],
        [new ToolCall('call_health', 'health_specialist', ['task' => 'Daily step totals for the 7 days ending 2026-11-22.'])],
        ['2026-11-16' => 6200, '2026-11-17' => 7400, '2026-11-18' => 0, '2026-11-20' => 5100, '2026-11-21' => 9800, '2026-11-22' => 4300],
        [
            new ToolCall('call_summary', 'get_health_summary', ['type' => 'steps', 'days' => 7, 'date' => '2026-11-22']),
            'Steps from 2026-11-16 to 2026-11-22 ranged from 0 on 2026-11-18 to 9,800 on 2026-11-21. There is no reading for 2026-11-19.',
        ],
    ],
    'interactive visuals and follow-up chips' => ['interactive', 'Help me build a wind-down routine for weeknights and a different one for weekends.', ['accepted', 'accepted']],
    'a visual after a tool call' => ['visual-after-tool-call', 'Suggest a breakfast that fits my goals.', ['accepted'], [new ToolCall('call_profile', 'get_user_profile', ['section' => 'all'])]],
]);

it('offers the catalog to a client that announces it while visuals are on', function (): void {
    $user = User::factory()->create(['locale' => 'en']);
    $conversation = Conversation::factory()->forUser($user)->create();
    AgentRunner::fake([walkReply()])->preventStrayPrompts();

    $this->actingAs($user)->postJson(route('chat.stream', $conversation->id), [
        'messages' => [['role' => 'user', 'parts' => [['type' => 'text', 'text' => 'When should I walk?']]]],
        'catalog' => ['name' => 'acara-chat', 'version' => 1],
    ])->assertAccepted();

    AgentRunner::assertPrompted(fn (AgentPrompt $prompt): bool => str_contains($prompt->agent->instructions(), 'VISUALS (acara-chat catalog'));
    Event::assertDispatched(AnonymousEvent::class, fn (AnonymousEvent $event): bool => $event->broadcastAs() === 'visual_end');
    expect(assistantReply($conversation))
        ->content->toBe("Here you go.\nWalk after lunch.\nEnjoy it.")
        ->catalogVersion()->toBe(1);
});

it('keeps a reply prose-only when the client gets no catalog', function (?array $catalog, string $visuals): void {
    $user = User::factory()->create(['locale' => 'en']);
    $conversation = Conversation::factory()->forUser($user)->create();
    AgentRunner::fake([walkReply()])->preventStrayPrompts();
    Setting::set(SettingKey::ChatVisuals, $visuals);

    $this->actingAs($user)->postJson(route('chat.stream', $conversation->id), array_filter([
        'messages' => [['role' => 'user', 'parts' => [['type' => 'text', 'text' => 'When should I walk?']]]],
        'catalog' => $catalog,
    ]))->assertAccepted();

    AgentRunner::assertPrompted(fn (AgentPrompt $prompt): bool => ! str_contains($prompt->agent->instructions(), 'VISUALS (acara-chat catalog'));
    Event::assertNotDispatched(AnonymousEvent::class, fn (AnonymousEvent $event): bool => $event->broadcastAs() === 'visual_end');
    expect(assistantReply($conversation))
        ->content->toBe(walkReply())
        ->catalogVersion()->toBeNull();
})->with([
    'a client that announces no catalog' => [null, 'on'],
    'a client that announces another catalog' => [['name' => 'other-chat', 'version' => 1], 'on'],
    'visuals switched off' => [['name' => 'acara-chat', 'version' => 1], 'off'],
]);

it('shows a later turn the earlier visual as its text rendering', function (): void {
    $user = User::factory()->create(['locale' => 'en']);
    $conversation = Conversation::factory()->forUser($user)->create();
    AgentRunner::fake([walkReply()])->preventStrayPrompts();

    $this->actingAs($user)->postJson(route('chat.stream', $conversation->id), [
        'messages' => [['role' => 'user', 'parts' => [['type' => 'text', 'text' => 'When should I walk?']]]],
        'catalog' => ['name' => 'acara-chat', 'version' => 1],
    ])->assertAccepted();

    $turn = collect(resolve(PlateConversationStore::class)->assistantTurn(assistantReply($conversation)))
        ->first(fn (object $message): bool => $message instanceof AssistantMessage);

    expect($turn->content)->toBe('Here you go.'."\n".ReplyComposer::contextMarker('Walk after lunch.').'Enjoy it.');
});

it('binds the data a tool fetched in the same reply but not data from an earlier reply', function (): void {
    $user = User::factory()->create(['locale' => 'en']);
    $conversation = Conversation::factory()->forUser($user)->create();
    HealthDailyAggregate::factory()->for($user)->stepCount()->create(['local_date' => '2026-11-22', 'date' => '2026-11-22', 'value_sum' => 4300]);
    AgentRunner::fake([
        new ToolCall('call_health', 'health_specialist', ['task' => 'Steps on 2026-11-22.']),
        "Here it is.\n".boundStepsChart(),
        "Here it is again.\n".boundStepsChart(),
    ])->preventStrayPrompts();
    HealthSpecialist::fake([stepsSummaryCall(), 'You walked 4,300 steps.'])->preventStrayPrompts();

    foreach (['Chart my steps.', 'Show that again.'] as $text) {
        $this->actingAs($user)->postJson(route('chat.stream', $conversation->id), [
            'messages' => [['role' => 'user', 'parts' => [['type' => 'text', 'text' => $text]]]],
            'catalog' => ['name' => 'acara-chat', 'version' => 1],
        ])->assertAccepted();
    }

    [$fetched, $repeated] = History::query()->where('conversation_id', $conversation->id)->where('role', MessageRole::Assistant->value)->oldest('id')->get()->all();

    expect($fetched->chatStreamMeta()['visuals'])->toBe([['id' => "{$fetched->id}-1", 'accepted' => true, 'reason' => null, 'bound' => 1, 'literal' => 0]]);
    expect($repeated->chatStreamMeta()['visuals'])->toBe([['id' => "{$repeated->id}-1", 'accepted' => false, 'reason' => 'unresolved_reference', 'bound' => 0, 'literal' => 0]]);
    expect(stepText($fetched))
        ->toContain("Datasets for visuals:\n- step_count: Steps, daily total in steps, 2026-11-22 to 2026-11-22, 1 day, 0 without a reading")
        ->toContain("[visual]\nSteps\n- Sun Nov 22, 2026: 4,300 steps");
});

it('lists no datasets for a client that draws no visuals', function (): void {
    $user = User::factory()->create(['locale' => 'en']);
    $conversation = Conversation::factory()->forUser($user)->create();
    HealthDailyAggregate::factory()->for($user)->stepCount()->create(['local_date' => '2026-11-22', 'date' => '2026-11-22', 'value_sum' => 4300]);
    AgentRunner::fake([
        new ToolCall('call_health', 'health_specialist', ['task' => 'Steps on 2026-11-22.']),
        'You walked 4,300 steps.',
    ])->preventStrayPrompts();
    HealthSpecialist::fake([stepsSummaryCall(), 'You walked 4,300 steps.'])->preventStrayPrompts();

    $this->actingAs($user)->postJson(route('chat.stream', $conversation->id), [
        'messages' => [['role' => 'user', 'parts' => [['type' => 'text', 'text' => 'How many steps?']]]],
    ])->assertAccepted();

    expect(stepText(assistantReply($conversation)))
        ->toContain('You walked 4,300 steps.')
        ->not->toContain('Datasets for visuals');
});

it('finishes a prose reply that was queued before chat visuals existed', function (): void {
    $user = User::factory()->create(['locale' => 'en']);
    $conversation = Conversation::factory()->forUser($user)->create();
    $turn = resolve(CreatePendingChatStreamTurn::class)->handle($conversation, $user, 'When should I walk?', [], 'web');
    AgentRunner::fake(['Walk after lunch.'])->preventStrayPrompts();
    $properties = new ProcessChatStream(
        userId: $user->id,
        conversationId: $conversation->id,
        modelName: 'gpt-test',
        channel: 'web',
        streamId: $turn->streamId,
        userMessageId: $turn->userMessageId,
        assistantMessageId: $turn->assistantMessageId,
    )->__serialize();
    unset($properties['catalogVersion']);
    $queuedByThePreviousRelease = new ReflectionClass(ProcessChatStream::class)->newInstanceWithoutConstructor();
    $queuedByThePreviousRelease->__unserialize($properties);

    dispatch_sync($queuedByThePreviousRelease);

    expect(History::query()->whereKey($turn->assistantMessageId)->sole()->content)->toBe('Walk after lunch.');
});

function scriptedReply(string $scenario): string
{
    return mb_rtrim(File::get(base_path("tests/Fixtures/chat-visual-replies/{$scenario}.md")), "\n");
}

function walkReply(): string
{
    return "Here you go.\n```spec\n{\"op\":\"add\",\"path\":\"/root\",\"value\":\"a\"}\n{\"op\":\"add\",\"path\":\"/elements/a\",\"value\":{\"type\":\"Text\",\"props\":{\"text\":\"Walk after lunch.\"},\"children\":[]}}\n```\nEnjoy it.";
}

function boundStepsChart(): string
{
    return "```spec\n{\"op\":\"add\",\"path\":\"/root\",\"value\":\"steps\"}\n{\"op\":\"add\",\"path\":\"/elements/steps\",\"value\":{\"type\":\"BarChart\",\"props\":{\"title\":\"Steps\",\"data\":{\"\$state\":\"/data/step_count\"},\"xKey\":\"day\",\"yKey\":\"value\",\"unit\":\"steps\"},\"children\":[]}}\n```\n";
}

function stepsSummaryCall(): ToolCall
{
    return new ToolCall('call_summary', 'get_health_summary', ['type' => 'steps', 'days' => 1, 'date' => '2026-11-22']);
}

function assistantReply(Conversation $conversation): History
{
    return History::query()->where('conversation_id', $conversation->id)->where('role', MessageRole::Assistant->value)->sole();
}

function stepText(History $message): string
{
    return collect(Arr::dot($message->steps))->filter(fn (mixed $value): bool => is_string($value))->implode("\n");
}

function fakeChatVisualsRedis(): void
{
    $sorted = [];
    $values = [];
    $connection = Mockery::mock(Connection::class);

    $connection->shouldReceive('zadd')->andReturnUsing(function (string $key, array $members) use (&$sorted): int {
        $sorted[$key] = [...$sorted[$key] ?? [], ...$members];

        return count($members);
    });
    $connection->shouldReceive('zrangebyscore')->andReturnUsing(function (string $key, string $min) use (&$sorted): array {
        $members = array_filter($sorted[$key] ?? [], fn (int $score): bool => $score >= (int) $min);
        asort($members);

        return array_map(strval(...), array_keys($members));
    });
    $connection->shouldReceive('exists')->andReturnUsing(function (string $key) use (&$sorted, &$values): int {
        return isset($sorted[$key]) || isset($values[$key]) ? 1 : 0;
    });
    $connection->shouldReceive('setex')->andReturnUsing(function (string $key, int $seconds, string $value) use (&$values): bool {
        $values[$key] = $value;

        return true;
    });
    $connection->shouldReceive('del')->andReturnUsing(function (string ...$keys) use (&$sorted, &$values): int {
        foreach ($keys as $key) {
            unset($sorted[$key], $values[$key]);
        }

        return count($keys);
    });
    $connection->shouldReceive('expire')->andReturnTrue();

    Redis::shouldReceive('connection')->andReturn($connection);
}

/**
 * @param  array<string, string>  $names  volatile ids and the stable names that replace them
 */
function expectChatVisualsGolden(string $scenario, mixed $contents, array $names): void
{
    $placeholders = [];
    $json = str_replace(array_keys($names), array_values($names), (string) json_encode($contents, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    $json = (string) preg_replace_callback(
        '/\b(?:[0-7][0-9A-HJKMNP-TV-Z]{25}|[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12})\b/i',
        function (array $match) use (&$placeholders): string {
            return $placeholders[$match[0]] ??= 'id-'.(count($placeholders) + 1);
        },
        $json,
    );
    $json = preg_replace(['/"timestamp": \d+/', '/"model": "[^"]*"/'], ['"timestamp": 0', '"model": "model"'], $json).PHP_EOL;

    $path = base_path("tests/Fixtures/chat-visuals/{$scenario}.json");

    if (! File::exists($path)) {
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $json);
    }

    expect($json)->toBe(File::get($path));
}
