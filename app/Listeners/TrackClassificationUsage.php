<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\AiUsage;
use App\Services\AiUsageService;
use Illuminate\Support\Facades\Context;
use Laravel\Ai\Classification;
use Laravel\Ai\Events\Classified;

final readonly class TrackClassificationUsage
{
    public function handle(Classified $event): void
    {
        $model = $event->response->meta->model ?? $event->model;
        $classifier = Context::getHidden('ai_classifier');

        $usageService = new AiUsageService;
        $tokens = $usageService->tokenCounts($event->response->usage);

        AiUsage::query()->firstOrCreate(['invocation_id' => $event->invocationId], [
            'user_id' => null,
            'agent' => is_string($classifier) ? $classifier : Classification::class,
            'model' => $model,
            'provider' => $event->provider->name(),
            ...$tokens,
            'cost' => $usageService->calculateCost($model, $tokens),
        ]);
    }
}
