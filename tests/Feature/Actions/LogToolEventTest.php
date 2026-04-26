<?php

declare(strict_types=1);

use App\Actions\LogToolEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

covers(LogToolEvent::class);

it('persists a tool_events row with bucketed properties and stripped PII', function (): void {
    $action = new LogToolEvent;

    $action->handle('caffeine-calculator', 'calculated', [
        'session_id' => 'sess_abc',
        'locale' => 'en-US',
        'weight' => 72.4,
        'safe_mg' => 410.0,
        'cups' => 4,
        'ip' => '1.2.3.4',
        'ip_address' => '5.6.7.8',
        'user_agent' => 'Mozilla/5.0',
        'userAgent' => 'Mozilla/5.0',
        'ua' => 'Mozilla/5.0',
        'sensitivity' => 'normal',
    ]);

    $row = DB::table('tool_events')->latest('id')->first();

    expect($row)->not->toBeNull()
        ->and($row->tool_name)->toBe('caffeine-calculator')
        ->and($row->event_name)->toBe('calculated')
        ->and($row->session_id)->toBe('sess_abc')
        ->and($row->locale)->toBe('en-US');

    $properties = json_decode($row->properties, true);

    expect($properties)->toHaveKey('weight', '70-79')
        ->and($properties)->toHaveKey('safe_mg', '400-449')
        ->and($properties)->toHaveKey('cups', '4')
        ->and($properties)->toHaveKey('sensitivity', 'normal')
        ->and($properties)->not->toHaveKey('ip')
        ->and($properties)->not->toHaveKey('ip_address')
        ->and($properties)->not->toHaveKey('user_agent')
        ->and($properties)->not->toHaveKey('userAgent')
        ->and($properties)->not->toHaveKey('ua')
        ->and($properties)->not->toHaveKey('session_id')
        ->and($properties)->not->toHaveKey('locale');
});

it('buckets weights into open-ended caps and closed ranges', function (float $value, string $expected): void {
    expect((new LogToolEvent)->bucketWeight($value))->toBe($expected);
})->with([
    [0.0, '0-9'],
    [9.99, '0-9'],
    [10.0, '10-19'],
    [55.0, '50-59'],
    [129.99, '120-129'],
    [130.0, '130+'],
    [200.0, '130+'],
]);

it('buckets safe_mg into open-ended caps and closed ranges', function (float $value, string $expected): void {
    expect((new LogToolEvent)->bucketSafeMg($value))->toBe($expected);
})->with([
    [0.0, '0-49'],
    [49.99, '0-49'],
    [50.0, '50-99'],
    [410.0, '400-449'],
    [599.99, '550-599'],
    [600.0, '600+'],
    [1000.0, '600+'],
]);

it('buckets cups exactly under cap and as N+ at or above cap', function (mixed $value, string $expected): void {
    expect((new LogToolEvent)->bucketCups($value))->toBe($expected);
})->with([
    [0, '0'],
    [3, '3'],
    [9, '9'],
    [10, '10+'],
    [25, '10+'],
    [3.7, '3'],
]);

it('returns null bucket for non-numeric inputs', function (): void {
    $action = new LogToolEvent;

    expect($action->bucketWeight('abc'))->toBeNull()
        ->and($action->bucketSafeMg(null))->toBeNull()
        ->and($action->bucketCups([]))->toBeNull();
});

it('returns void and swallows database failures while logging a warning', function (): void {
    Log::spy();

    DB::shouldReceive('table')
        ->once()
        ->with('tool_events')
        ->andThrow(new RuntimeException('db down'));

    $action = new LogToolEvent;

    $result = $action->handle('caffeine-calculator', 'calculated', ['cups' => 2]);

    expect($result)->toBeNull();

    Log::shouldHaveReceived('warning')
        ->once()
        ->with('Failed to log tool event', Mockery::on(fn (array $context): bool => $context['tool_name'] === 'caffeine-calculator'
            && $context['event_name'] === 'calculated'
            && $context['exception'] === 'db down'));
});
