<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\GetAiUsageForBillingAction;
use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use App\Models\AiUsage;
use App\Models\SubscriptionProduct;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Laravel\Cashier\Subscription;

#[Description('Report paid subscribers with their daily and weekly AI usage, photo scans, and new subscriptions')]
#[Signature('billing:usage-report
        {--days=7 : How many days of photo scans and new subscriptions to include}')]
final class ReportSubscriberUsageCommand extends Command
{
    public function handle(GetAiUsageForBillingAction $usage): int
    {
        $days = max(1, (int) $this->option('days'));
        $since = now()->subDays($days);
        $planNames = $this->planNamesByPrice();
        $planOf = function (Subscription $subscription) use ($planNames): string {
            $price = $subscription->stripe_price ?? $subscription->items()->value('stripe_price');

            return is_string($price) ? ($planNames[$price] ?? '—') : '—';
        };

        /** @var Collection<int, Subscription> $subscriptions */
        $subscriptions = Subscription::query()->get();
        $users = User::query()->whereIn('id', $subscriptions->pluck('user_id'))->get()->keyBy('id');
        $active = $subscriptions->filter(fn (Subscription $subscription): bool => $subscription->valid())->unique('user_id')->sortBy('user_id');
        $new = $subscriptions->filter(fn (Subscription $subscription): bool => $subscription->created_at?->greaterThanOrEqualTo($since) ?? false);

        $rows = [];
        foreach ($active as $subscription) {
            $user = $users->get($subscription->user_id);
            if (! $user instanceof User) {
                continue;
            }

            $snapshot = $usage->handle($user);
            $rows[] = [
                $user->id,
                $planOf($subscription),
                $subscription->created_at?->toDateString() ?? '—',
                $snapshot['rolling']['current'].' / '.$snapshot['rolling']['limit'],
                $snapshot['weekly']['current'].' / '.$snapshot['weekly']['limit'],
                $this->photoScans($user, now()->startOfDay()),
                $this->photoScans($user, $since),
            ];
        }

        $this->table(['User', 'Plan', 'Since', 'Daily credits (24h)', 'Weekly credits', 'Scans today', sprintf('Scans %dd', $days)], $rows);

        $activeByPlan = $active->countBy($planOf);
        $newByPlan = $new->countBy($planOf);

        $this->table(
            ['Plan', 'Active subscribers', sprintf('New in %dd', $days)],
            $activeByPlan->keys()->merge($newByPlan->keys())->unique()->sort()
                ->map(fn (string $plan): array => [$plan, $activeByPlan->get($plan, 0), $newByPlan->get($plan, 0)])
                ->values()->all(),
        );

        return self::SUCCESS;
    }

    /**
     * @return array<string, string>
     */
    private function planNamesByPrice(): array
    {
        $names = [];

        foreach (SubscriptionProduct::query()->get() as $product) {
            foreach ([$product->stripe_price_id, $product->yearly_stripe_price_id] as $price) {
                if (filled($price)) {
                    $names[$price] = $product->name;
                }
            }
        }

        return $names;
    }

    private function photoScans(User $user, CarbonInterface $since): int
    {
        return AiUsage::query()
            ->forUser($user)
            ->byAgent(FoodPhotoAnalyzerAgent::class)
            ->where('created_at', '>=', $since)
            ->count();
    }
}
