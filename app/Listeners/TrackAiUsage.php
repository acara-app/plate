<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Billing\BuildCreditWarning;
use App\Models\AiUsage;
use App\Models\User;
use App\Services\AiUsageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Laravel\Ai\Events\AgentPrompted;
use ReflectionClass;
use Throwable;

final readonly class TrackAiUsage
{
    public function __construct(
        private Request $request,
        private BuildCreditWarning $buildCreditWarning,
    ) {}

    public function handle(AgentPrompted $event): void
    {
        $invocationId = $event->invocationId;

        $cached = Cache::get('ai_usage_'.$invocationId);
        if ($cached) {
            return;
        }

        Cache::put('ai_usage_'.$invocationId, true, now()->addMinutes(5));

        $response = $event->response;

        $usage = $response->usage;
        $meta = $response->meta;

        $model = $meta->model ?? 'unknown';
        $provider = $meta->provider ?? 'unknown';

        $user = $this->request->user() ?? $this->getUserFromAgent($event->prompt->agent);

        $agentClass = $event->prompt->agent::class;

        $usageArray = [
            'prompt_tokens' => $usage->promptTokens,
            'completion_tokens' => $usage->completionTokens,
            'cache_read_input_tokens' => $usage->cacheReadInputTokens,
            'reasoning_tokens' => $usage->reasoningTokens,
        ];

        $cost = new AiUsageService()->calculateCost($model, $usageArray);

        AiUsage::query()->create([
            'user_id' => $user?->id,
            'agent' => $agentClass,
            'model' => $model,
            'provider' => $provider,
            'prompt_tokens' => $usage->promptTokens,
            'completion_tokens' => $usage->completionTokens,
            'cache_read_input_tokens' => $usage->cacheReadInputTokens,
            'reasoning_tokens' => $usage->reasoningTokens,
            'cost' => $cost,
        ]);

        if ($user instanceof User) {
            $this->buildCreditWarning->handle($user);
        }
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
