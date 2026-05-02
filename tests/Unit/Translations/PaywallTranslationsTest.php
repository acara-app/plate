<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Lang;

beforeEach(function (): void {
    app()->setLocale('en');
});

it('exposes paywall plan copy for both paid tiers', function (string $tier): void {
    expect(Lang::has(sprintf('common.billing.paywall.plans.%s.name', $tier)))->toBeTrue()
        ->and(Lang::has(sprintf('common.billing.paywall.plans.%s.price', $tier)))->toBeTrue()
        ->and(Lang::has(sprintf('common.billing.paywall.plans.%s.pitch', $tier)))->toBeTrue();
})->with(['basic', 'plus']);

it('exposes paywall feature copy for every gated feature', function (string $feature): void {
    expect(Lang::has('common.billing.paywall.features.'.$feature))->toBeTrue()
        ->and(__('common.billing.paywall.features.'.$feature))->not->toBeEmpty();
})->with(['meal_planner', 'image_analysis', 'memory', 'health_sync']);

it('exposes cap- and feature-trigger paywall headings', function (): void {
    expect(Lang::has('common.billing.paywall.cap_title'))->toBeTrue()
        ->and(Lang::has('common.billing.paywall.cap_description'))->toBeTrue()
        ->and(Lang::has('common.billing.paywall.feature_title'))->toBeTrue()
        ->and(Lang::has('common.billing.paywall.feature_description'))->toBeTrue()
        ->and(Lang::has('common.billing.paywall.compare_plans'))->toBeTrue()
        ->and(Lang::has('common.billing.paywall.upgrade_to'))->toBeTrue();
});

it('exposes upsell-card copy for the inline feature gate', function (): void {
    expect(Lang::has('common.billing.upsell.card_eyebrow'))->toBeTrue()
        ->and(Lang::has('common.billing.upsell.card_title'))->toBeTrue()
        ->and(Lang::has('common.billing.upsell.card_description'))->toBeTrue()
        ->and(Lang::has('common.billing.upsell.upgrade_button'))->toBeTrue();
});

it('exposes a label for every subscription tier', function (string $tier): void {
    expect(Lang::has('common.billing.tier.labels.'.$tier))->toBeTrue()
        ->and(__('common.billing.tier.labels.'.$tier))->not->toBeEmpty();
})->with(['free', 'basic', 'plus']);
