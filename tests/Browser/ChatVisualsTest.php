<?php

declare(strict_types=1);

use App\Actions\CreatePendingChatStreamTurn;
use App\Jobs\ProcessChatStream;
use App\Models\Conversation;
use App\Models\History;
use App\Models\User;
use App\Services\StreamEventStore;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\Messages\MessageRole;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('reopens a reply with its visuals between the paragraphs Altani wrote', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create();
    savedVisualReply($conversation, 'two-visuals');

    $this->actingAs($user);

    visit(route('chat.create', $conversation->id, absolute: false))
        ->assertSee('Higher likelihood of a substantial rise')
        ->assertSee('Teriyaki chicken rice bowl')
        ->assertCount('figure[data-test="chat-visual"]', 2)
        ->assertScript(visualBlockOrder(), ['text', 'visual', 'text', 'visual', 'text'])
        ->assertNoJavaScriptErrors()
        ->assertNoAccessibilityIssues();
});

it('streams each visual once in its place, even when the replay repeats events', function (string $scenario, string $lastWords, int $visuals, array $order): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create();
    replayGolden($this->app, $conversation, $user, $scenario, streamingPolls: 2);

    $this->actingAs($user);

    visit(route('chat.create', $conversation->id, absolute: false))
        ->waitForText($lastWords)
        ->wait(2.5)
        ->assertCount('figure[data-test="chat-visual"]', $visuals)
        ->assertCount('[data-test="chat-visual-placeholder"]', 0)
        ->assertScript(visualBlockOrder(), $order)
        ->assertNoJavaScriptErrors();
})->with([
    'a visual between two paragraphs' => ['prose-visual-prose', 'stop if you feel dizzy', 1, ['text', 'visual', 'text']],
    'a visual Plate rejected' => ['rejected-visual', 'more gently than white rice', 0, ['text']],
    'a visual after a tool call' => ['visual-after-tool-call', 'Add berries if you want more fibre.', 1, ['text', 'visual', 'text']],
    'a chart of the steps a health tool fetched' => ['bound-week', 'lift your average.', 1, ['text', 'visual', 'text']],
    'interactive visuals and follow-up chips' => ['interactive', 'helps your body clock stay steady.', 2, ['text', 'visual', 'text', 'visual']],
]);

it('keeps a tick made while the reply was still streaming', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create();
    replayGolden($this->app, $conversation, $user, 'interactive', streamingPolls: 5);

    $this->actingAs($user);

    visit(route('chat.create', $conversation->id, absolute: false))
        ->waitForText('helps your body clock stay steady.')
        ->assertPresent('[aria-label="Stop the reply"]')
        ->click('Close the blinds')
        ->wait(6)
        ->assertMissing('[aria-label="Stop the reply"]')
        ->assertScript(tickedItems(), ['Close the blinds'])
        ->assertNoJavaScriptErrors();
});

it('gives every catalog component a web view', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create();
    $catalog = json_decode(File::get(resource_path('js/components/chat/visuals/generated/acara-chat.json')));
    $parts = [];

    foreach (get_object_vars($catalog->components) as $type => $component) {
        $props = clone $component->example;
        $state = new stdClass;

        foreach (['Segmented' => 'value', 'Checklist' => 'checked'] as $boundType => $prop) {
            if ($type === $boundType) {
                $state->{$prop} = $props->{$prop};
                $props->{$prop} = (object) ['$bindState' => "/ui/{$prop}"];
            }
        }

        $element = (object) ['type' => $type, 'props' => $props, 'children' => $component->acceptsChildren ? ['child'] : []];

        if ($type === 'Button') {
            $element->on = (object) ['press' => (object) ['action' => 'sendMessage', 'params' => (object) ['text' => 'Plan my lunch']]];
        }

        $parts[] = ['type' => 'data-spec', 'data' => ['id' => "example-{$type}", 'fallback_text' => $type, 'spec' => json_encode([
            'root' => 'element',
            'elements' => ['element' => $element, 'child' => ['type' => 'Text', 'props' => ['text' => 'Inside'], 'children' => []]],
            'state' => ['ui' => $state],
        ])]];
    }

    History::factory()->forConversation($conversation)->assistantMessage()->create([
        'meta' => History::streamMeta('stream-1', History::STREAM_STATUS_COMPLETED, ['parts' => $parts]),
    ]);

    $this->actingAs($user);

    visit(route('chat.create', $conversation->id, absolute: false))
        ->assertCount('figure[data-test="chat-visual"]', count(get_object_vars($catalog->components)))
        ->assertCount('[data-test="chat-visual-fallback"]', 0)
        ->assertScript("Array.from(document.querySelectorAll('figure[data-test=\"chat-visual\"]')).every((figure) => figure.firstElementChild !== null)", true)
        ->assertNoJavaScriptErrors();
});

it('switches views and ticks items in a visual, by pointer and by keyboard', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create();
    savedVisualReply($conversation, 'interactive');

    $this->actingAs($user);

    $page = visit(route('chat.create', $conversation->id, absolute: false))
        ->assertSee('Dim the lights')
        ->assertDontSee('Take a warm shower')
        ->click('Weekends')
        ->assertSee('Take a warm shower')
        ->assertDontSee('Dim the lights')
        ->keys('input[type="radio"][value="weekend"]', ['ArrowLeft'])
        ->assertSee('Dim the lights')
        ->assertDontSee('Take a warm shower')
        ->keys('input[type="radio"][value="weeknight"]', ['Tab'])
        ->keys(':focus', ['Space'])
        ->click('Put water by the bed')
        ->assertScript(tickedItems(), ['Close the blinds', 'Put water by the bed']);

    $page->keys('input[type="radio"][value="weeknight"]', ['Tab']);

    $reached = [];

    for ($tab = 0; $tab < 7; $tab++) {
        $reached[] = $page->script('(() => { const element = document.activeElement; return ((element.labels && element.labels[0]) || element).textContent.trim(); })()');
        $page->keys(':focus', ['Tab']);
    }

    expect($reached)->toBe(['Close the blinds', "Set tomorrow's alarm", 'Put water by the bed', "Log last night's sleep", 'Make it shorter', 'Snap my dinner', 'Sleep hygiene guide(opens in a new tab)']);

    $page->assertNoJavaScriptErrors()->assertNoAccessibilityIssues();
});

it('sends a follow-up chip once and keeps what the person ticked', function (): void {
    Queue::fake();
    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create();
    savedVisualReply($conversation, 'interactive');
    holdRepliesInFlight($this->app);

    $this->actingAs($user);

    $page = visit(route('chat.create', $conversation->id, absolute: false));
    $page->click('Close the blinds')
        ->script("(() => { const chip = Array.from(document.querySelectorAll('button')).find((button) => button.textContent.trim() === 'Make it shorter'); chip.click(); chip.click(); })()");

    $page->assertSee('Make the weeknight routine 15 minutes long.')
        ->assertPresent('[aria-label="Stop the reply"]')
        ->assertAttribute('[aria-label="Suggested next steps"] li:first-child button', 'aria-disabled', 'true')
        ->assertScript(tickedItems(), ['Close the blinds'])
        ->wait(1)
        ->assertNoJavaScriptErrors();

    expect(userMessages($conversation))->toBe(['Make the weeknight routine 15 minutes long.']);
    Queue::assertPushed(ProcessChatStream::class, 1);
});

it('sends one message from a button that would send two', function (): void {
    Queue::fake();
    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create();
    holdRepliesInFlight($this->app);
    savedVisuals($conversation, [[
        'root' => 'ask',
        'elements' => ['ask' => ['type' => 'Button', 'props' => ['label' => 'Ask both'], 'children' => [], 'on' => ['press' => [
            ['action' => 'sendMessage', 'params' => ['text' => 'What should I eat tonight?']],
            ['action' => 'sendMessage', 'params' => ['text' => 'And tomorrow?']],
        ]]]],
    ]]);

    $this->actingAs($user);

    visit(route('chat.create', $conversation->id, absolute: false))
        ->click('Ask both')
        ->assertSee('What should I eat tonight?')
        ->wait(1)
        ->assertDontSee('And tomorrow?')
        ->assertNoJavaScriptErrors();

    expect(userMessages($conversation))->toBe(['What should I eat tonight?']);
});

it('opens a Plate destination in place', function (string $kind, string $destination, string $path): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create();
    $action = ['action' => 'openDestination', 'params' => ['destination' => $destination]];
    savedVisuals($conversation, [[
        'root' => 'go',
        'elements' => ['go' => $kind === 'button'
            ? ['type' => 'Button', 'props' => ['label' => 'Go there'], 'children' => [], 'on' => ['press' => $action]]
            : ['type' => 'SuggestionChips', 'props' => ['items' => [['label' => 'Go there', 'action' => $action]]], 'children' => []]],
    ]]);

    $this->actingAs($user);

    visit(route('chat.create', $conversation->id, absolute: false))
        ->click('Go there')
        ->assertPathIs($path);
})->with([
    'the daily log from a button' => ['button', 'log', '/health-entries'],
    'snap to track from a chip' => ['chip', 'snap', '/app/snap-to-track'],
    'the health sync guide from a chip' => ['chip', 'sync', '/tools/health-sync/setup'],
]);

it('opens an outside link in a new tab without a way back to Plate', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create();
    savedVisualReply($conversation, 'interactive');

    $this->actingAs($user);

    $page = visit(route('chat.create', $conversation->id, absolute: false));
    $page->script('window.open = (...options) => { window.openedWith = options; return null; }');

    $page->click('Sleep hygiene guide')
        ->assertScript('window.openedWith', ['https://www.example.com/sleep-hygiene', '_blank', 'noopener,noreferrer'])
        ->assertNoJavaScriptErrors();
});

it('draws the bound rows as a chart that tells a recorded zero from a missing day', function (string $type, string $mark): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create();
    savedVisualReply($conversation, 'bound-week', function (stdClass $spec) use ($type): stdClass {
        $spec->elements->steps->type = $type;

        return $spec;
    });

    $this->actingAs($user);

    visit(route('chat.create', $conversation->id, absolute: false))
        ->assertSee('Daily steps')
        ->assertCount("[data-test=\"{$mark}\"]", 7)
        ->assertCount("[data-test=\"{$mark}\"][data-reading=\"zero\"]", 1)
        ->assertCount("[data-test=\"{$mark}\"][data-reading=\"missing\"]", 1)
        ->assertCount('[data-test="chat-chart-goal"]', 1)
        ->assertSee('Goal 7,000 steps')
        ->assertSee('Each day runs from midnight to midnight UTC.')
        ->assertScript(
            "Array.from(document.querySelectorAll('figure ul.sr-only li')).map((item) => item.textContent)",
            ['Mon, Nov 16: 6,200 steps', 'Tue, Nov 17: 7,400 steps', 'Wed, Nov 18: 0 steps', 'Thu, Nov 19: No reading', 'Fri, Nov 20: 5,100 steps', 'Sat, Nov 21: 9,800 steps', 'Sun, Nov 22: 4,300 steps'],
        )
        ->assertNoJavaScriptErrors()
        ->assertNoAccessibilityIssues();
})->with([
    'bars' => ['BarChart', 'chat-chart-bar'],
    'a line' => ['LineChart', 'chat-chart-point'],
]);

it('shows the plain text of a visual the web cannot draw, line by line', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create();
    savedVisualReply($conversation, 'two-visuals', function (stdClass $spec): stdClass {
        if (isset($spec->elements->macros)) {
            $spec->elements->macros->type = 'FutureTimeline';
        }

        return $spec;
    });

    $this->actingAs($user);

    visit(route('chat.create', $conversation->id, absolute: false))
        ->assertSee('Higher likelihood of a substantial rise')
        ->assertCount('figure[data-test="chat-visual"]', 1)
        ->assertScript(
            "document.querySelector('[data-test=\"chat-visual-fallback\"]').innerText",
            "Teriyaki chicken rice bowl\n820 kcal: carbs 110 g, protein 42 g, fat 20 g\nHalf the rice saves about 40 g of carbohydrate.",
        )
        ->assertScript(visualBlockOrder(), ['text', 'visual', 'text', 'fallback', 'text'])
        ->assertNoJavaScriptErrors();
});

function savedVisualReply(Conversation $conversation, string $scenario, ?Closure $change = null): void
{
    $message = json_decode(File::get(base_path("tests/Fixtures/chat-visuals/{$scenario}.json")))->message;
    $parts = array_map(fn (stdClass $part): array => $part->type === 'data-spec'
        ? ['type' => 'data-spec', 'data' => [
            'id' => $part->data->id,
            'spec' => json_encode($change instanceof Closure ? $change($part->data->spec) : $part->data->spec),
            'fallback_text' => $part->data->fallback_text,
        ]]
        : ['type' => 'text', 'text' => $part->text], $message->parts);

    History::factory()->forConversation($conversation)->assistantMessage()->create([
        'meta' => History::streamMeta('stream-1', History::STREAM_STATUS_COMPLETED, ['parts' => $parts]),
    ]);
}

function savedVisuals(Conversation $conversation, array $specs): void
{
    History::factory()->forConversation($conversation)->assistantMessage()->create([
        'meta' => History::streamMeta('stream-1', History::STREAM_STATUS_COMPLETED, ['parts' => array_map(
            fn (array $spec, int $index): array => ['type' => 'data-spec', 'data' => ['id' => "visual-{$index}", 'spec' => json_encode($spec), 'fallback_text' => '']],
            $specs,
            array_keys($specs),
        )]),
    ]);
}

function replayGolden(Application $app, Conversation $conversation, User $user, string $scenario, int $streamingPolls): void
{
    resolve(CreatePendingChatStreamTurn::class)->handle($conversation, $user, 'Hello', [], 'web', catalogVersion: 1);
    $events = array_map(
        fn (stdClass $event): array => (array) $event,
        json_decode(File::get(base_path("tests/Fixtures/chat-visuals/{$scenario}.json")))->events,
    );

    $app->instance(StreamEventStore::class, new class($events, $streamingPolls) extends StreamEventStore
    {
        private int $polls = 0;

        /**
         * @param  list<array<string, mixed>>  $replay
         */
        public function __construct(private readonly array $replay, private readonly int $streamingPolls) {}

        public function eventsAfter(string $conversationId, int $afterSequence): array
        {
            return $this->replay;
        }

        public function isStreaming(string $conversationId): bool
        {
            return ++$this->polls <= $this->streamingPolls;
        }
    });
}

function holdRepliesInFlight(Application $app): void
{
    $app->instance(StreamEventStore::class, new class extends StreamEventStore
    {
        private bool $started = false;

        public function __construct() {}

        public function clear(string $conversationId): void
        {
            $this->started = true;
        }

        public function eventsAfter(string $conversationId, int $afterSequence): array
        {
            return [];
        }

        public function isStreaming(string $conversationId): bool
        {
            return $this->started;
        }
    });
}

/**
 * @return list<string>
 */
function userMessages(Conversation $conversation): array
{
    return History::query()
        ->where('conversation_id', $conversation->id)
        ->where('role', MessageRole::User->value)
        ->pluck('content')
        ->all();
}

function tickedItems(): string
{
    return "Array.from(document.querySelectorAll('[role=\"checkbox\"][aria-checked=\"true\"]')).map((box) => box.labels[0].textContent.trim())";
}

function visualBlockOrder(): string
{
    return "Array.from(document.querySelectorAll('.chat-markdown, [data-test^=\"chat-visual\"]')).map((block) => ({ FIGURE: 'visual', P: 'fallback' })[block.tagName] ?? 'text')";
}
