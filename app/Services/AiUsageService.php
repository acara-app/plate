<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ModelName;
use App\Models\AiUsage;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @property array{input: float, output: float, reasoning: float, cache_read: float} $pricing
 */
final class AiUsageService
{
    /**
     * @param  array{prompt_tokens?: int, completion_tokens?: int, cache_read_input_tokens?: int, reasoning_tokens?: int}  $usage
     */
    public function calculateCost(string $model, array $usage): float
    {
        $modelEnum = ModelName::tryFrom($model);

        $pricing = $modelEnum?->getPricing() ?? $this->getDefaultPricing();

        $inputCost = ($usage['prompt_tokens'] ?? 0) / 1_000_000 * $pricing['input'];
        $outputCost = ($usage['completion_tokens'] ?? 0) / 1_000_000 * $pricing['output'];
        $reasoningCost = ($usage['reasoning_tokens'] ?? 0) / 1_000_000 * $pricing['reasoning'];
        $cacheCost = ($usage['cache_read_input_tokens'] ?? 0) / 1_000_000 * $pricing['cache_read'];

        return $inputCost + $outputCost + $reasoningCost + $cacheCost;
    }

    /**
     * @return Collection<int, AiUsage>
     *
     * @codeCoverageIgnore
     */
    public function getUsageForUser(User $user, ?string $startDate = null, ?string $endDate = null): Collection
    {
        return AiUsage::query()
            ->forUser($user)
            ->dateRange($startDate, $endDate)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @return array<array-key, array{model: string, provider: string, prompt_tokens: int, completion_tokens: int, cache_read_input_tokens: int, reasoning_tokens: int, cost: float, requests: int}>
     *
     * @codeCoverageIgnore
     */
    public function getUsageByModel(User $user, ?string $startDate = null, ?string $endDate = null): array
    {
        /** @var array<array-key, array{model: string, provider: string, prompt_tokens: int, completion_tokens: int, cache_read_input_tokens: int, reasoning_tokens: int, cost: float, requests: int}> $result */
        $result = AiUsage::query()
            ->forUser($user)
            ->dateRange($startDate, $endDate)
            ->selectRaw('model, provider, SUM(prompt_tokens) as prompt_tokens, SUM(completion_tokens) as completion_tokens, SUM(cache_read_input_tokens) as cache_read_input_tokens, SUM(reasoning_tokens) as reasoning_tokens, SUM(cost) as cost, COUNT(*) as requests')
            ->groupBy('model', 'provider')
            ->orderByDesc('cost')
            ->get()
            ->toArray();

        return $result;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTotalCost(User $user, ?string $startDate = null, ?string $endDate = null): float
    {
        return (float) AiUsage::query()
            ->forUser($user)
            ->dateRange($startDate, $endDate)
            ->sum('cost');
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTotalTokens(User $user, ?string $startDate = null, ?string $endDate = null): int
    {
        return (int) AiUsage::query()
            ->forUser($user)
            ->dateRange($startDate, $endDate)
            ->sum(DB::raw('prompt_tokens + completion_tokens + cache_read_input_tokens + reasoning_tokens'));
    }

    /**
     * @return array{input: float, output: float, reasoning: float, cache_read: float}
     */
    private function getDefaultPricing(): array
    {
        return [
            'input' => 0.50,
            'output' => 2.00,
            'reasoning' => 0.0,
            'cache_read' => 0.25,
        ];
    }
}
