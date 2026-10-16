<?php

declare(strict_types=1);

use App\Ai\Agents\AgentRunner;
use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use App\Console\Commands\ReportSubscriberUsageCommand;
use App\Models\AiUsage;
use App\Models\SubscriptionProduct;
use App\Models\User;

covers(ReportSubscriberUsageCommand::class);

function subscribeToPlan(User $user, string $price, int $daysAgo, bool $canceled = false): void
{
    $user->subscriptions()->create([
        'type' => 'default', 'stripe_id' => 'sub_'.$user->id, 'stripe_status' => $canceled ? 'canceled' : 'active',
        'stripe_price' => $price, 'quantity' => 1, 'created_at' => now()->subDays($daysAgo),
        'ends_at' => $canceled ? now()->subDay() : null,
    ]);
}

it('reports paid subscribers on their plan limits with daily and weekly usage and new subscriptions', function (): void {
    config()->set('plate.enable_premium_upgrades', true);
    SubscriptionProduct::factory()->create(['name' => 'Starter', 'price' => 2.99, 'stripe_price_id' => 'price_starter']);
    SubscriptionProduct::query()->where('name', 'Snap Pro')->update(['stripe_price_id' => 'price_snap_pro']);

    $starter = User::factory()->create();
    subscribeToPlan($starter, 'price_starter', daysAgo: 2);
    AiUsage::factory()->count(2)->for($starter)->create(['agent' => FoodPhotoAnalyzerAgent::class, 'cost' => 0.01]);
    AiUsage::factory()->for($starter)->create(['agent' => FoodPhotoAnalyzerAgent::class, 'cost' => 0.02, 'created_at' => now()->subDays(3)]);
    AiUsage::factory()->for($starter)->create(['agent' => AgentRunner::class, 'cost' => 0.1]);

    $snapPro = User::factory()->create();
    subscribeToPlan($snapPro, 'price_snap_pro', daysAgo: 10);

    subscribeToPlan(User::factory()->create(), 'price_starter', daysAgo: 3, canceled: true);
    AiUsage::factory()->for(User::factory()->create())->create(['agent' => FoodPhotoAnalyzerAgent::class]);

    $this->artisan('billing:usage-report')
        ->expectsTable(
            ['User', 'Plan', 'Since', 'Daily credits (24h)', 'Weekly credits', 'Scans today', 'Scans 7d'],
            [
                [$starter->id, 'Starter', now()->subDays(2)->toDateString(), '120 / 500', '140 / 1500', 2, 3],
                [$snapPro->id, 'Snap Pro', now()->subDays(10)->toDateString(), '0 / 1500', '0 / 6000', 0, 0],
            ],
        )
        ->expectsTable(
            ['Plan', 'Active subscribers', 'New in 7d'],
            [
                ['Snap Pro', 1, 0],
                ['Starter', 1, 2],
            ],
        )
        ->assertSuccessful();
});
