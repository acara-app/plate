<?php

declare(strict_types=1);

use App\Enums\SubscriptionTier;
use Illuminate\Support\Facades\Lang;

beforeEach(function (): void {
    app()->setLocale('en');
});

it('exposes paywall plan copy for both paid tiers', function (string $tier): void {
    expect(Lang::has(sprintf('common.billing.paywall.plans.%s.name', $tier)))->toBeTrue()
        ->and(Lang::has(sprintf('common.billing.paywall.plans.%s.price', $tier)))->toBeTrue()
        ->and(Lang::has(sprintf('common.billing.paywall.plans.%s.pitch', $tier)))->toBeTrue();
})->with(['basic', 'plus']);

it('exposes cap-trigger paywall headings and the upgrade-to copy', function (): void {
    expect(Lang::has('common.billing.paywall.cap_title'))->toBeTrue()
        ->and(Lang::has('common.billing.paywall.cap_description'))->toBeTrue()
        ->and(Lang::has('common.billing.paywall.upgrade_to'))->toBeTrue();
});

it('exposes pro-model upsell copy for the meal planner banner', function (): void {
    expect(Lang::has('common.billing.pro_model_upsell.title'))->toBeTrue()
        ->and(Lang::has('common.billing.pro_model_upsell.body'))->toBeTrue()
        ->and(Lang::has('common.billing.pro_model_upsell.cta'))->toBeTrue();
});

it('exposes a label for every subscription tier', function (string $tier): void {
    expect(Lang::has('common.billing.tier.labels.'.$tier))->toBeTrue()
        ->and(__('common.billing.tier.labels.'.$tier))->not->toBeEmpty();
})->with(['free', 'basic', 'plus']);

it('exposes a usage-window label for every limit type', function (string $window): void {
    expect(Lang::has('common.billing.usage.'.$window))->toBeTrue()
        ->and(__('common.billing.usage.'.$window))->not->toBeEmpty();
})->with(['rolling', 'weekly', 'monthly']);

it('aligns billing.tier.labels with the canonical SubscriptionTier label', function (SubscriptionTier $tier): void {
    expect(__('common.billing.tier.labels.'.$tier->value))->toBe($tier->label());
})->with([
    SubscriptionTier::Free,
    SubscriptionTier::Basic,
    SubscriptionTier::Plus,
]);

it('aligns billing.paywall.plans.X.name with the canonical SubscriptionTier label', function (SubscriptionTier $tier): void {
    expect(__('common.billing.paywall.plans.'.$tier->value.'.name'))->toBe($tier->label());
})->with([
    SubscriptionTier::Basic,
    SubscriptionTier::Plus,
]);
