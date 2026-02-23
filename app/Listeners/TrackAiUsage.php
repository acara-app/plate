<?php

declare(strict_types=1);

namespace App\Listeners;

use App\DataObjects\AiUsageData;
use App\Models\AiUsage;
use App\Services\AiUsageService;
use Illuminate\Http\Request;
use Laravel\Ai\Events\AgentPrompted;

final readonly class TrackAiUsage
{
    public function __construct(
        private AiUsageService $usageService,
        private Request $request,
    ) {}

    public function handle(AgentPrompted $event): void
    {
        $response = $event->response;

        $usage = $response->usage;
        $meta = $response->meta;

        $model = $meta->model ?? 'unknown';
        $provider = $meta->provider ?? 'unknown';

        $usageArray = [
            'prompt_tokens' => $usage->promptTokens,
            'completion_tokens' => $usage->completionTokens,
            'cache_read_input_tokens' => $usage->cacheReadInputTokens,
            'reasoning_tokens' => $usage->reasoningTokens,
        ];

        $cost = $this->usageService->calculateCost($model, $usageArray);

        $user = $this->request->user();

        $agentClass = $event->prompt->agent::class;

        $usageData = AiUsageData::from([
            'userId' => $user?->id,
            'agent' => $agentClass,
            'model' => $model,
            'provider' => $provider,
            'promptTokens' => $usage->promptTokens,
            'completionTokens' => $usage->completionTokens,
            'cacheReadInputTokens' => $usage->cacheReadInputTokens,
            'reasoningTokens' => $usage->reasoningTokens,
            'cost' => $cost,
        ]);

        AiUsage::query()->create([
            'user_id' => $usageData->userId,
            'agent' => $usageData->agent,
            'model' => $usageData->model,
            'provider' => $usageData->provider,
            'prompt_tokens' => $usageData->promptTokens,
            'completion_tokens' => $usageData->completionTokens,
            'cache_read_input_tokens' => $usageData->cacheReadInputTokens,
            'reasoning_tokens' => $usageData->reasoningTokens,
            'cost' => $usageData->cost,
        ]);
    }
}
