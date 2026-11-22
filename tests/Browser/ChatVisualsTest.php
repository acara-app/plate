<?php

declare(strict_types=1);

use App\Actions\CreatePendingChatStreamTurn;
use App\Models\Conversation;
use App\Models\History;
use App\Models\User;
use App\Services\StreamEventStore;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\File;
use Pest\Browser\Api\AwaitableWebpage;
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
    resolve(CreatePendingChatStreamTurn::class)->handle($conversation, $user, 'Hello', [], 'web', catalogVersion: 1);
    $events = array_map(
        fn (stdClass $event): array => (array) $event,
        json_decode(File::get(base_path("tests/Fixtures/chat-visuals/{$scenario}.json")))->events,
    );

    $this->app->instance(StreamEventStore::class, new class($events) extends StreamEventStore
    {
        private int $polls = 0;

        /**
         * @param  list<array<string, mixed>>  $replay
         */
        public function __construct(private readonly array $replay) {}

        public function eventsAfter(string $conversationId, int $afterSequence): array
        {
            return $this->replay;
        }

        public function isStreaming(string $conversationId): bool
        {
            return ++$this->polls < 3;
        }
    });

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
]);

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

it("ignores live events from the person's other conversations", function (): void {
    $user = User::factory()->create();
    $streaming = Conversation::factory()->forUser($user)->create();
    $open = Conversation::factory()->forUser($user)->create();
    savedVisualReply($open, 'two-visuals');

    $this->actingAs($user);

    $page = visit(route('chat.create', $open->id, absolute: false))->assertCount('figure[data-test="chat-visual"]', 2);

    foreach (goldenEvents('prose-visual-prose', $streaming->id) as $event) {
        emitLiveEvent($page, $user, $event);
    }

    $page->wait(1)
        ->assertDontSee('Try this')
        ->assertCount('figure[data-test="chat-visual"]', 2)
        ->assertCount('[data-test="chat-visual-placeholder"]', 0)
        ->assertMissing('[aria-label="Stop the reply"]')
        ->assertNoJavaScriptErrors();
});

it('fills in a visual whose patches the live stream missed', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create();
    resolve(CreatePendingChatStreamTurn::class)->handle($conversation, $user, 'Hello', [], 'web', catalogVersion: 1);
    $events = goldenEvents('prose-visual-prose', $conversation->id);
    $store = new class($events) extends StreamEventStore
    {
        public int $stored = 51;

        /**
         * @param  list<stdClass>  $events
         */
        public function __construct(private readonly array $events) {}

        public function eventsAfter(string $conversationId, int $afterSequence): array
        {
            return array_values(array_map(
                fn (stdClass $event): array => ['sequence' => $event->sequence, 'type' => $event->type, 'data' => $event],
                array_filter($this->events, fn (stdClass $event): bool => $event->sequence > $afterSequence && $event->sequence < $this->stored),
            ));
        }

        public function isStreaming(string $conversationId): bool
        {
            return true;
        }
    };
    $this->app->instance(StreamEventStore::class, $store);

    $this->actingAs($user);

    $page = visit(route('chat.create', $conversation->id, absolute: false))->waitForText('cool down.');

    emitLiveEvent($page, $user, $events[51]);
    $page->assertPresent('[data-test="chat-visual-placeholder"]');

    $store->stored = PHP_INT_MAX;
    emitLiveEvent($page, $user, $events[53]);

    $page->waitForText('Alternate brisk and easy walking')
        ->assertCount('figure[data-test="chat-visual"]', 1)
        ->assertCount('[data-test="chat-visual-fallback"]', 0)
        ->assertNoJavaScriptErrors();
});

/**
 * @return list<stdClass>
 */
function goldenEvents(string $scenario, string $conversationId): array
{
    return array_map(function (stdClass $event) use ($conversationId): stdClass {
        $data = clone $event->data;
        $data->conversation_id = $conversationId;

        return $data;
    }, json_decode(File::get(base_path("tests/Fixtures/chat-visuals/{$scenario}.json")))->events);
}

function emitLiveEvent(AwaitableWebpage $page, User $user, stdClass $event): void
{
    $page->script(sprintf(
        "window.Pusher.instances[0].channel('private-chat.%d').emit(%s, %s)",
        $user->id,
        json_encode($event->type),
        json_encode($event, JSON_THROW_ON_ERROR),
    ));
}

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

function visualBlockOrder(): string
{
    return "Array.from(document.querySelectorAll('.chat-markdown, [data-test^=\"chat-visual\"]')).map((block) => ({ FIGURE: 'visual', P: 'fallback' })[block.tagName] ?? 'text')";
}
