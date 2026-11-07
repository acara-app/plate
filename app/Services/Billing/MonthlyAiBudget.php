<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use App\Contracts\Billing\ProvidesAiBudget;
use App\Contracts\Billing\ResolvesUserTier;
use App\Data\Billing\AiBudget;
use App\Models\AiUsage;
use App\Models\User;
use Carbon\CarbonImmutable;

final readonly class MonthlyAiBudget implements ProvidesAiBudget
{
    public function __construct(private PhotoPlanCatalog $catalog, private ResolvesUserTier $tiers) {}

    public function forUser(User $user): ?AiBudget
    {
        if (! $this->catalog->isSelling() || $this->tiers->resolve($user)->isPaying()) {
            return null;
        }

        $start = CarbonImmutable::now('UTC')->startOfMonth();
        $used = (float) AiUsage::query()->forUser($user)->where('agent', '!=', FoodPhotoAnalyzerAgent::class)
            ->where('created_at', '>=', $start)->where('created_at', '<', $start->addMonth())->sum('cost');

        $limit = config('snap.other_ai_monthly_usd', 0.50);

        return new AiBudget($used, is_numeric($limit) ? (float) $limit : 0.50, $start->addMonth());
    }
}
