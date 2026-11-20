<?php

declare(strict_types=1);

use App\Ai\AgentBuilder;
use App\Ai\AgentRequest;
use App\Ai\Tools\ShowWeeklySteps;
use App\Data\WeeklyStepsChart;
use App\Models\HealthDailyAggregate;
use App\Models\User;
use Illuminate\Support\Facades\Context;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Tools\Request;

covers(ShowWeeklySteps::class, WeeklyStepsChart::class);

it('offers the chart to Altani in chat channels that support visual replies', function (string $channel, bool $available): void {
    Context::add('chat.channel', $channel);

    $agent = resolve(AgentBuilder::class)->build(new AgentRequest(message: 'How did my steps vary this week?'));

    expect(collect($agent['tools'])->contains(fn ($tool): bool => $tool instanceof ShowWeeklySteps))->toBe($available)
        ->and(str_contains($agent['instructions'], 'show_weekly_steps'))->toBe($available);
})->with(['web' => ['web', true], 'mobile' => ['mobile', true], 'telegram' => ['telegram', false]]);

it('charts seven UTC dates using only step totals from the signed-in user', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);
    $this->travelTo(now('UTC')->setDateTime(2026, 11, 15, 23, 59, 59));
    HealthDailyAggregate::factory()->for($user)->stepCount(4200)->create(['local_date' => '2026-11-09']);
    HealthDailyAggregate::factory()->for($user)->stepCount()->create(['local_date' => '2026-11-10', 'value_sum' => 0]);
    HealthDailyAggregate::factory()->for($user)->stepCount(3000)->create(['local_date' => '2026-11-08']);
    HealthDailyAggregate::factory()->stepCount(9000)->create(['local_date' => '2026-11-11']);

    $tool = resolve(ShowWeeklySteps::class);
    $chart = WeeklyStepsChart::fromToolResult($tool->name(), $tool->handle(new Request(['date' => null])));

    $days = $chart['spec']['elements']['weekly-steps']['props']['days'];
    expect(array_column($days, 'date'))->toBe(['2026-11-09', '2026-11-10', '2026-11-11', '2026-11-12', '2026-11-13', '2026-11-14', '2026-11-15'])
        ->and(array_column($days, 'steps'))->toBe([4200, 0, null, null, null, null, null])
        ->and($chart['fallbackText'])->toContain('2026-11-09: 4200', '2026-11-10: 0', '2026-11-11: no reading');
});

it('keeps a week with no recorded steps as prose', function (): void {
    $this->actingAs(User::factory()->create());
    $tool = resolve(ShowWeeklySteps::class);
    $result = $tool->handle(new Request(['date' => '2026-11-15']));

    expect(json_decode($result, true)['summaries'])->toBeEmpty()
        ->and(WeeklyStepsChart::fromToolResult($tool->name(), $result))->toBeNull();
});

it('rejects a date that cannot identify a UTC day', function (): void {
    resolve(ShowWeeklySteps::class)->handle(new Request(['date' => '2026-02-30']));
})->throws(ValidationException::class);

it('rejects incomplete or incompatible chart data', function (array $range, array $rows): void {
    $result = json_encode(['success' => true, 'date_range' => $range, 'summaries' => $rows], JSON_THROW_ON_ERROR);

    expect(WeeklyStepsChart::fromToolResult(ShowWeeklySteps::NAME, $result))->toBeNull();
})->with([
    'wrong range' => [['from' => '2026-11-08', 'to' => '2026-11-15'], [['date' => '2026-11-09', 'type' => 'stepCount', 'primary_value' => 0]]],
    'out of range' => [['from' => '2026-11-09', 'to' => '2026-11-15'], [['date' => '2026-11-16', 'type' => 'stepCount', 'primary_value' => 10]]],
    'fractional steps' => [['from' => '2026-11-09', 'to' => '2026-11-15'], [['date' => '2026-11-09', 'type' => 'stepCount', 'primary_value' => 1.5]]],
    'missing values' => [['from' => '2026-11-09', 'to' => '2026-11-15'], [['date' => '2026-11-09', 'type' => 'stepCount', 'primary_value' => null]]],
]);
