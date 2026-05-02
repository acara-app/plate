<?php

declare(strict_types=1);

use App\Enums\GatedFeature;
use App\Enums\SubscriptionTier;
use App\Exceptions\Billing\FeatureGateException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

covers(FeatureGateException::class);

it('renders a 402 JSON response with structured payload', function (): void {
    $exception = new FeatureGateException(
        feature: GatedFeature::MealPlanner,
        currentTier: SubscriptionTier::Free,
        requiredTier: SubscriptionTier::Basic,
    );

    $response = $exception->render(Request::create('/'));

    expect($response)->toBeInstanceOf(JsonResponse::class)
        ->and($response->getStatusCode())->toBe(Response::HTTP_PAYMENT_REQUIRED)
        ->and(json_decode($response->getContent() ?: '', true))->toMatchArray([
            'error' => 'feature_gated',
            'feature' => 'meal_planner',
            'current_tier' => 'free',
            'current_tier_label' => 'Free',
            'required_tier' => 'basic',
            'required_tier_label' => 'Basic',
        ]);
});

it('exposes its message for logging', function (): void {
    $exception = new FeatureGateException(
        feature: GatedFeature::ImageAnalysis,
        currentTier: SubscriptionTier::Free,
        requiredTier: SubscriptionTier::Basic,
    );

    expect($exception->getMessage())
        ->toContain('image_analysis')
        ->toContain('basic')
        ->toContain('free');
});
