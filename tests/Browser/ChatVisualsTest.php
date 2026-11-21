<?php

declare(strict_types=1);

use App\Actions\CreatePendingChatStreamTurn;
use App\Models\Conversation;
use App\Models\History;
use App\Models\User;
use App\Services\StreamEventStore;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\File;
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
