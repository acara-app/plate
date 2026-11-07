<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use App\Contracts\Billing\ProvidesAiBudget;
use App\Data\Billing\AiBudget;
use App\Models\AiUsage;
use App\Models\User;
use Carbon\CarbonImmutable;

final readonly class MonthlyAiBudget implements ProvidesAiBudget
{
    public function __construct(private MeteredPhotoAnalyses $photos) {}

    public function forUser(User $user): ?AiBudget
    {
        if (! $this->photos->enabled() || $this->photos->paid($user)) {
            return null;
        }

        $start = CarbonImmutable::now('UTC')->startOfMonth();
        $used = (float) AiUsage::query()->forUser($user)->where('agent', '!=', FoodPhotoAnalyzerAgent::class)
            ->where('created_at', '>=', $start)->where('created_at', '<', $start->addMonth())->sum('cost');

        return new AiBudget($used, config()->float('snap.other_ai_monthly_usd', 0.50), $start->addMonth());
    }
}
