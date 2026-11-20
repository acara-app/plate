<?php

declare(strict_types=1);

use App\Models\Conversation;
use App\Models\History;
use App\Models\User;
use App\Services\StreamEventStore;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Vite;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

beforeEach(function (): void {
    Vite::useHotFile(storage_path('framework/testing/vite.hot'));

    $user = User::factory()->create();
    $this->actingAs($user);
    $this->conversation = Conversation::factory()->forUser($user)->create();
    $this->chatUrl = route('chat.create', $this->conversation->id, absolute: false);
    $this->example = File::json(base_path('tests/Fixtures/chat-weekly-steps.json'));
    $this->store = $this->mock(StreamEventStore::class);
    $this->store->shouldReceive('isStreaming')->andReturnFalse()->byDefault();
    $this->store->shouldReceive('eventsAfter')->andReturn([])->byDefault();
});

it('shows the saved dates and distinguishes missing days from recorded zeros', function (): void {
    [$takeaway, $visual] = $this->example['savedMessage']['parts'];
    History::factory()->forConversation($this->conversation)->assistantMessage()->create([
        'content' => $takeaway['text'],
        'meta' => History::streamMeta('stream-1', History::STREAM_STATUS_COMPLETED, ['visual_part' => $visual]),
    ]);

    visit($this->chatUrl)->withTimezone('America/Los_Angeles')
        ->assertSee($takeaway['text'])
        ->assertSeeIn('article[aria-labelledby] figure figcaption', 'Daily steps (UTC)')
        ->assertCount('article[aria-labelledby] figure li', 7)
        ->assertSeeIn('li:has(time[datetime="2026-11-10"]) > span:last-child', '0')
        ->assertSeeIn('time[datetime="2026-11-10"]', 'Tue, Nov 10')
        ->assertSeeIn('li:has(time[datetime="2026-11-11"])', 'No reading')
        ->assertSeeIn('li:has(time[datetime="2026-11-12"])', '7,900')
        ->assertNoJavaScriptErrors();
});

it('recovers one visual after the takeaway when its event arrives again', function (): void {
    $visual = [...$this->example['liveEvent'], 'conversationId' => $this->conversation->id];
    $takeaway = $this->example['savedMessage']['parts'][0]['text'];
    $events = [
        ['type' => 'stream_start', 'id' => 'stream-1'],
        $visual,
        ['type' => 'text_delta', 'id' => 'takeaway', 'delta' => $takeaway],
        [...$visual, 'id' => 'redelivery'],
    ];
    $envelopes = array_map(fn (array $event, int $index): array => [
        'sequence' => $index, 'type' => $event['type'], 'data' => $event,
    ], $events, array_keys($events));

    $this->store->shouldReceive('isStreaming')->andReturnTrue();
    $this->store->shouldReceive('eventsAfter')->with($this->conversation->id, -1)->andReturn($envelopes);

    visit($this->chatUrl)
        ->assertSee($takeaway)
        ->assertSeeIn('article[aria-labelledby] figure', 'No reading')
        ->assertCount('article[aria-labelledby] figure', 1)
        ->assertScript("document.querySelector('article').innerText.indexOf('Thursday had the most steps') < document.querySelector('article').innerText.indexOf('Daily steps (UTC)')")
        ->assertNoJavaScriptErrors();
});

it('accepts live visuals only from the current conversation', function (): void {
    $visual = [...$this->example['liveEvent'], 'conversationId' => $this->conversation->id];
    History::factory()->forConversation($this->conversation)->userMessage()->create(['content' => 'How did my steps vary?']);

    $page = visit($this->chatUrl)->assertSee('How did my steps vary?');
    $channel = "window.Pusher.instances[0].channel('private-chat.{$this->conversation->participant_id}')";
    $page->assertScript("Boolean({$channel})")
        ->script("{$channel}.emit('stream_start', { type: 'stream_start', id: 'stream-1' });");

    $foreignEvent = [...$visual, 'conversationId' => 'other-conversation'];
    $page->script("{$channel}.emit('chat_visual', ".json_encode($foreignEvent, JSON_THROW_ON_ERROR).');');
    $page->script("{$channel}.emit('text_delta', {type:'text_delta', id:'takeaway', delta:'Here are your steps.'});");
    $page->assertSee('Here are your steps.')->assertCount('article[aria-labelledby] figure', 0);

    $page->script("{$channel}.emit('chat_visual', ".json_encode($visual, JSON_THROW_ON_ERROR).');');
    $page->assertSeeIn('article[aria-labelledby] figure', '7,900')->assertNoJavaScriptErrors();
});

it('keeps one readable summary when using a chart fallback', function (string $component, int $chartCount): void {
    $part = $this->example['savedMessage']['parts'][1];
    $part['data']['spec']['elements']['weekly-steps']['type'] = $component;
    $text = $part['data']['fallbackText'];
    History::factory()->forConversation($this->conversation)->assistantMessage()->create([
        'content' => $text,
        'meta' => History::streamMeta('stream-1', History::STREAM_STATUS_COMPLETED, ['visual_part' => $part]),
    ]);

    visit($this->chatUrl)
        ->assertSee('Daily steps (UTC)')
        ->assertCount('article[aria-labelledby] figure', $chartCount)
        ->assertScript("document.querySelector('article').innerText.includes(".json_encode($text, JSON_THROW_ON_ERROR).')', $chartCount === 0)
        ->assertNoJavaScriptErrors();
})->with([
    'supported chart' => ['WeeklyStepsChart', 1],
    'unsupported chart' => ['UnsupportedChart', 0],
]);
