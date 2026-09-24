<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\AiUsage;
use App\Models\User;
use App\Services\AiUsageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Laravel\Ai\Events\AgentPrompted;
use ReflectionClass;
use Throwable;

final readonly class TrackAiUsage
{
    public function __construct(
        private Request $request,
    ) {}

    public function handle(AgentPrompted $event): void
    {
        $invocationId = $event->invocationId;

        $response = $event->response;

        $usage = $response->usage;
        $meta = $response->meta;

        $model = $meta->model ?? 'unknown';
        $provider = $meta->provider ?? 'unknown';

        $user = $this->getUserFromAgent($event->prompt->agent) ?? $this->request->user();

        $agentClass = $event->prompt->agent::class;

        $usageArray = [
            'prompt_tokens' => $usage->uncachedInputTokens(),
            'completion_tokens' => $usage->outputTokens - ($usage->reasoningTokens ?? 0),
            'cache_read_input_tokens' => $usage->cacheReadInputTokens ?? 0,
            'cache_write_input_tokens' => $usage->cacheWriteInputTokens ?? 0,
            'reasoning_tokens' => $usage->reasoningTokens ?? 0,
        ];

        $cost = new AiUsageService()->calculateCost($model, $usageArray);

        AiUsage::query()->firstOrCreate(['invocation_id' => $invocationId], [
            'usage_group' => Context::get('photo_usage_group'),
            'user_id' => $user?->id,
            'agent' => $agentClass,
            'model' => $model,
            'provider' => $provider,
            ...$usageArray,
            'cost' => $cost,
        ]);
    }

    private function getUserFromAgent(object $agent): ?User
    {
        try {
            $reflection = new ReflectionClass($agent);

            // @codeCoverageIgnoreStart
            if ($reflection->hasProperty('conversationUser')) {
                $property = $reflection->getProperty('conversationUser');
                $user = $property->getValue($agent);
                if ($user instanceof User) {
                    return $user;
                }
            }

            if ($reflection->hasProperty('user')) {
                $property = $reflection->getProperty('user');
                $user = $property->getValue($agent);
                if ($user instanceof User) {
                    return $user;
                }
            }

            // @codeCoverageIgnoreEnd
        } catch (Throwable) { // @codeCoverageIgnore
            return null; // @codeCoverageIgnore
        }

        return null;
    }
}
