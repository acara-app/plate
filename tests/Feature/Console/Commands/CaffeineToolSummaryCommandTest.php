<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Console\Commands\CaffeineToolSummaryCommand;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

covers(CaffeineToolSummaryCommand::class);

beforeEach(function (): void {
    DB::table('tool_events')->truncate();
});

it('is registered in the artisan list', function (): void {
    expect(Artisan::all())->toHaveKey('tools:caffeine:summary');
});

it('reports when there are no events in the window', function (): void {
    $this->artisan('tools:caffeine:summary', ['--days' => 3])
        ->assertSuccessful()
        ->expectsOutputToContain('No caffeine-calculator events found');
});

it('outputs a daily counts table for each event', function (): void {
    $today = CarbonImmutable::now()->startOfDay();
    $yesterday = $today->subDay();

    DB::table('tool_events')->insert([
        ['tool_name' => 'caffeine-calculator', 'event_name' => 'viewed', 'created_at' => $yesterday->copy()->addHours(9)],
        ['tool_name' => 'caffeine-calculator', 'event_name' => 'viewed', 'created_at' => $yesterday->copy()->addHours(10)],
        ['tool_name' => 'caffeine-calculator', 'event_name' => 'weight_entered', 'created_at' => $yesterday->copy()->addHours(11)],
        ['tool_name' => 'caffeine-calculator', 'event_name' => 'viewed', 'created_at' => $today->copy()->addHours(8)],
        ['tool_name' => 'caffeine-calculator', 'event_name' => 'unit_toggled', 'created_at' => $today->copy()->addHours(9)],
        ['tool_name' => 'spike-calculator', 'event_name' => 'viewed', 'created_at' => $today->copy()->addHours(10)],
    ]);

    $exitCode = Artisan::call('tools:caffeine:summary', ['--days' => 7]);
    $output = Artisan::output();

    expect($exitCode)->toBe(0)
        ->and($output)->toContain($yesterday->toDateString())
        ->and($output)->toContain($today->toDateString())
        ->and($output)->toContain('viewed')
        ->and($output)->toContain('weight_entered')
        ->and($output)->toContain('unit_toggled');
});

it('respects --from and --to flags', function (): void {
    $inWindow = CarbonImmutable::parse('2026-04-10')->setTime(12, 0);
    $outOfWindow = CarbonImmutable::parse('2026-04-01')->setTime(12, 0);

    DB::table('tool_events')->insert([
        ['tool_name' => 'caffeine-calculator', 'event_name' => 'viewed', 'created_at' => $inWindow],
        ['tool_name' => 'caffeine-calculator', 'event_name' => 'viewed', 'created_at' => $outOfWindow],
    ]);

    $this->artisan('tools:caffeine:summary', [
        '--from' => '2026-04-09',
        '--to' => '2026-04-11',
    ])
        ->assertSuccessful()
        ->expectsOutputToContain('2026-04-10');
});

it('rejects an inverted date range', function (): void {
    $this->artisan('tools:caffeine:summary', [
        '--from' => '2026-04-20',
        '--to' => '2026-04-10',
    ])
        ->assertFailed()
        ->expectsOutputToContain('--from must be before --to.');
});
