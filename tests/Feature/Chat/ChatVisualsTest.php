<?php

declare(strict_types=1);

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

it('streams, replays and saves a reply with its visuals in the order Altani wrote them', function (string $scenario, string $prompt, array $responses, array $outcomes, array $steps = [], array $specialist = []): void {
    $user = User::factory()->create(['locale' => 'en', 'timezone' => 'UTC']);
    $conversation = Conversation::factory()->forUser($user)->create();
    AgentRunner::fake($responses)->preventStrayPrompts();
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
    $ends = array_values(array_filter($stored, fn (stdClass $event): bool => $event->type === 'visual_end'));
    $assistant = History::query()->where('conversation_id', $conversation->id)->where('role', MessageRole::Assistant->value)->sole();

    expect(json_encode($broadcast))->toBe(json_encode(array_column($stored, 'data')))
        ->and(array_map(fn (array $payload): int => mb_strlen((string) json_encode((string) json_encode($payload))), $broadcast))->each->toBeLessThanOrEqual(8_000)
        ->and(array_map(fn (stdClass $end): string => $end->data->accepted ? 'accepted' : $end->data->reason, $ends))->toBe($outcomes)
        ->and(array_column(array_filter($message->parts, fn (stdClass $part): bool => $part->type === 'data-spec'), 'type'))->toHaveCount(count(array_keys($outcomes, 'accepted', true)))
        ->and(json_encode($page->props->messages[1]->parts))->toBe(json_encode($message->parts))
        ->and($assistant->content)->not->toContain('```spec');

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
    'a visual between two paragraphs' => [
        'prose-visual-prose',
        'Give me a 20-minute beginner walking workout I can follow.',
        [
            implode("\n", [
                'Try this **20-minute walk** on a flat route or treadmill:',
                '',
                'Walk easily for 4 minutes to warm up. Then alternate 1 minute of brisk walking with 1 minute at an easy pace, repeating the pair six times. Finish with 4 minutes of easy walking to cool down.',
                '',
                '```spec',
                '{"op":"add","path":"/root","value":"walk"}',
                '{"op":"add","path":"/elements/walk","value":{"type":"Steps","props":{"items":[{"title":"Warm up","detail":"Walk at an easy pace.","duration":"4 minutes"},{"title":"Alternate brisk and easy walking","detail":"Walk briskly for 1 minute, then easily for 1 minute. Repeat six times.","duration":"12 minutes"},{"title":"Cool down","detail":"Slow to an easy pace.","duration":"4 minutes"}]},"children":[]}}',
                '```',
                '',
                'During the brisk intervals, aim for a pace where you can still talk. Slow down if needed, and stop if you feel dizzy or have chest pain.',
            ]),
        ],
        ['accepted'],
    ],
    'two visuals, one holding an empty object' => [
        'two-visuals',
        'Will a big bowl of white rice with teriyaki chicken spike my blood sugar?',
        [
            implode("\n", [
                'A big bowl of white rice with teriyaki chicken is **likely to raise your blood sugar substantially**, though the size of the rise varies from person to person.',
                '',
                '```spec',
                '{"op":"add","path":"/root","value":"risk"}',
                '{"op":"add","path":"/elements/risk","value":{"type":"RiskMeter","props":{"level":"high","label":"Higher likelihood of a substantial rise","detail":"A large portion of white rice and sweet teriyaki sauce can add up to a lot of carbohydrate. Individual responses vary."},"children":[]}}',
                '```',
                '',
                'Here is a rough estimate for a large bowl:',
                '',
                '```spec',
                '{"op":"add","path":"/root","value":"bowl"}',
                '{"op":"add","path":"/elements/bowl","value":{"type":"Card","props":{"title":"Teriyaki chicken rice bowl"},"children":["macros","line","tip"]}}',
                '{"op":"add","path":"/elements/macros","value":{"type":"MacroBreakdown","props":{"calories":820,"carbs":110,"protein":42,"fat":20},"children":[]}}',
                '{"op":"add","path":"/elements/line","value":{"type":"Separator","props":{},"children":[]}}',
                '{"op":"add","path":"/elements/tip","value":{"type":"Text","props":{"text":"Half the rice saves about 40 g of carbohydrate."},"children":[]}}',
                '```',
                '',
                'To soften the rise, try a smaller rice portion and add non-starchy vegetables.',
            ]),
        ],
        ['accepted', 'accepted'],
    ],
    'a visual Plate rejects' => [
        'rejected-visual',
        'Is oatmeal or white rice better for my blood sugar?',
        [
            implode("\n", [
                'Here is a quick comparison.',
                '',
                '```spec',
                '{"op":"add","path":"/root","value":"chart"}',
                '{"op":"add","path":"/elements/chart","value":{"type":"PieChart","props":{"title":"Carbs"},"children":[]}}',
                '```',
                '',
                'Oatmeal usually raises blood sugar more gently than white rice.',
            ]),
        ],
        ['unknown_component'],
    ],
    'a chart released in several events' => [
        'chart-in-several-events',
        'Show me a six-month step plan.',
        fn (): array => [implode("\n", [
            'Here is a six-month plan that builds up slowly.',
            '',
            '```spec',
            '{"op":"add","path":"/root","value":"chart"}',
            json_encode(['op' => 'add', 'path' => '/elements/chart', 'value' => [
                'type' => 'BarChart',
                'props' => [
                    'title' => 'Daily step target',
                    'data' => array_map(fn (int $day): array => [
                        'day' => now()->setDate(2026, 1, 1)->addDays($day)->toDateString(),
                        'steps' => match ($day) {
                            3 => 0,
                            5 => null,
                            default => 4_000 + $day * 25,
                        },
                    ], range(0, 179)),
                    'xKey' => 'day',
                    'yKey' => 'steps',
                    'unit' => 'steps',
                ],
                'children' => [],
            ]]),
            '```',
            '',
            'Take the rest day whenever you need one.',
        ])],
        ['accepted'],
    ],
    'a chart bound to the steps a health tool fetched' => [
        'bound-week',
        'How did my steps go this week?',
        [
            new ToolCall('call_health', 'health_specialist', ['task' => 'Daily step totals for the 7 days ending 2026-11-22.']),
            implode("\n", [
                'Your steps swung a lot this week, from **0 on Wednesday** to **9,800 on Saturday**, and Thursday has no reading.',
                '',
                '```spec',
                '{"op":"add","path":"/root","value":"steps"}',
                '{"op":"add","path":"/elements/steps","value":{"type":"BarChart","props":{"title":"Daily steps","data":{"$state":"/data/step_count"},"xKey":"day","yKey":"value","unit":"steps","goal":7000},"children":[]}}',
                '```',
                '',
                'A short walk after dinner on the quiet days would lift your average.',
            ]),
        ],
        ['accepted'],
        ['2026-11-16' => 6200, '2026-11-17' => 7400, '2026-11-18' => 0, '2026-11-20' => 5100, '2026-11-21' => 9800, '2026-11-22' => 4300],
        [
            new ToolCall('call_summary', 'get_health_summary', ['type' => 'steps', 'days' => 7, 'date' => '2026-11-22']),
            'Steps from 2026-11-16 to 2026-11-22 ranged from 0 on 2026-11-18 to 9,800 on 2026-11-21. There is no reading for 2026-11-19.',
        ],
    ],
    'a visual after a tool call' => [
        'visual-after-tool-call',
        'Suggest a breakfast that fits my goals.',
        [
            new ToolCall('call_profile', 'get_user_profile', ['section' => 'all']),
            implode("\n", [
                'Here is a breakfast that fits your goals.',
                '',
                '```spec',
                '{"op":"add","path":"/root","value":"meal"}',
                '{"op":"add","path":"/elements/meal","value":{"type":"Card","props":{"title":"Greek yogurt bowl"},"children":["macros"]}}',
                '{"op":"add","path":"/elements/macros","value":{"type":"MacroBreakdown","props":{"calories":350,"carbs":38,"protein":28,"fat":9},"children":[]}}',
                '```',
                '',
                'Add berries if you want more fibre.',
            ]),
        ],
        ['accepted'],
    ],
]);

it('offers the catalog only to a client that announces it while visuals are on', function (?array $catalog, ?string $setting, bool $offered): void {
    $reply = "Here you go.\n```spec\n{\"op\":\"add\",\"path\":\"/root\",\"value\":\"a\"}\n{\"op\":\"add\",\"path\":\"/elements/a\",\"value\":{\"type\":\"Text\",\"props\":{\"text\":\"Walk after lunch.\"},\"children\":[]}}\n```\nEnjoy it.";
    $user = User::factory()->create(['locale' => 'en']);
    $conversation = Conversation::factory()->forUser($user)->create();
    AgentRunner::fake([$reply])->preventStrayPrompts();

    if ($setting !== null) {
        Setting::set(SettingKey::ChatVisuals, $setting);
    }

    $this->actingAs($user)->postJson(route('chat.stream', $conversation->id), array_filter([
        'messages' => [['role' => 'user', 'parts' => [['type' => 'text', 'text' => 'When should I walk?']]]],
        'catalog' => $catalog,
    ]))->assertAccepted();

    $assistant = History::query()->where('conversation_id', $conversation->id)->where('role', MessageRole::Assistant->value)->sole();

    AgentRunner::assertPrompted(fn (AgentPrompt $prompt): bool => str_contains($prompt->agent->instructions(), 'VISUALS (acara-chat catalog') === $offered);

    expect(Event::dispatched(AnonymousEvent::class, fn (AnonymousEvent $event): bool => $event->broadcastAs() === 'visual_end')->isNotEmpty())->toBe($offered)
        ->and($assistant->content)->toBe($offered ? "Here you go.\nWalk after lunch.\nEnjoy it." : $reply)
        ->and($assistant->catalogVersion())->toBe($offered ? 1 : null);
})->with([
    'announced' => [['name' => 'acara-chat', 'version' => 1], null, true],
    'not announced' => [null, null, false],
    'another catalog' => [['name' => 'other-chat', 'version' => 1], null, false],
    'announced while visuals are off' => [['name' => 'acara-chat', 'version' => 1], 'off', false],
]);

it('shows a later turn the earlier visual as its text rendering', function (): void {
    $user = User::factory()->create(['locale' => 'en']);
    $conversation = Conversation::factory()->forUser($user)->create();
    AgentRunner::fake(["Here you go.\n```spec\n{\"op\":\"add\",\"path\":\"/root\",\"value\":\"a\"}\n{\"op\":\"add\",\"path\":\"/elements/a\",\"value\":{\"type\":\"Text\",\"props\":{\"text\":\"Walk after lunch.\"},\"children\":[]}}\n```\nEnjoy it."])->preventStrayPrompts();

    $this->actingAs($user)->postJson(route('chat.stream', $conversation->id), [
        'messages' => [['role' => 'user', 'parts' => [['type' => 'text', 'text' => 'When should I walk?']]]],
        'catalog' => ['name' => 'acara-chat', 'version' => 1],
    ])->assertAccepted();

    $assistant = History::query()->where('conversation_id', $conversation->id)->where('role', MessageRole::Assistant->value)->sole();
    $turn = collect(resolve(PlateConversationStore::class)->assistantTurn($assistant))
        ->first(fn (object $message): bool => $message instanceof AssistantMessage);

    expect($turn->content)->toBe('Here you go.'."\n".ReplyComposer::contextMarker('Walk after lunch.').'Enjoy it.');
});

it('lets Altani bind only data fetched in the same reply, for a client that draws visuals', function (): void {
    $user = User::factory()->create(['locale' => 'en']);
    $conversation = Conversation::factory()->forUser($user)->create();
    HealthDailyAggregate::factory()->for($user)->stepCount()->create(['local_date' => '2026-11-22', 'date' => '2026-11-22', 'value_sum' => 4300]);
    $chart = "```spec\n{\"op\":\"add\",\"path\":\"/root\",\"value\":\"steps\"}\n{\"op\":\"add\",\"path\":\"/elements/steps\",\"value\":{\"type\":\"BarChart\",\"props\":{\"title\":\"Steps\",\"data\":{\"\$state\":\"/data/step_count\"},\"xKey\":\"day\",\"yKey\":\"value\",\"unit\":\"steps\"},\"children\":[]}}\n```\n";
    $summary = new ToolCall('call_summary', 'get_health_summary', ['type' => 'steps', 'days' => 1, 'date' => '2026-11-22']);
    AgentRunner::fake([
        new ToolCall('call_health_1', 'health_specialist', ['task' => 'Steps on 2026-11-22.']),
        "Here it is.\n{$chart}",
        "Here it is again.\n{$chart}",
        new ToolCall('call_health_2', 'health_specialist', ['task' => 'Steps on 2026-11-22.']),
        'You walked 4,300 steps.',
    ])->preventStrayPrompts();
    HealthSpecialist::fake([$summary, 'You walked 4,300 steps.', $summary, 'You walked 4,300 steps.'])->preventStrayPrompts();

    foreach ([['acara-chat', 'Chart my steps.'], ['acara-chat', 'Show that again.'], [null, 'How many steps?']] as [$catalog, $text]) {
        $this->actingAs($user)->postJson(route('chat.stream', $conversation->id), array_filter([
            'messages' => [['role' => 'user', 'parts' => [['type' => 'text', 'text' => $text]]]],
            'catalog' => $catalog === null ? null : ['name' => $catalog, 'version' => 1],
        ]))->assertAccepted();
    }

    [$fetched, $repeated, $proseOnly] = History::query()->where('conversation_id', $conversation->id)->where('role', MessageRole::Assistant->value)->oldest('id')->get()->all();

    expect($fetched->chatStreamMeta()['visuals'])->toBe([['id' => "{$fetched->id}-1", 'accepted' => true, 'reason' => null, 'bound' => 1, 'literal' => 0]])
        ->and($repeated->chatStreamMeta()['visuals'])->toBe([['id' => "{$repeated->id}-1", 'accepted' => false, 'reason' => 'unresolved_reference', 'bound' => 0, 'literal' => 0]])
        ->and(json_encode($fetched->steps))->toContain('Datasets for visuals:\\n- step_count: Steps, daily total in steps, 2026-11-22 to 2026-11-22, 1 day, 0 without a reading')
        ->and(json_encode($fetched->steps, JSON_UNESCAPED_UNICODE))->toContain('[visual]\\nSteps\\n- Sun Nov 22, 2026: 4,300 steps')
        ->and(json_encode($proseOnly->steps))->toContain('You walked 4,300 steps.')->not->toContain('Datasets for visuals');
});

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
