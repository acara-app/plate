<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Ai\Agents\AgentRunner;
use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use App\Contracts\Ai\ResolvesAgentModel;
use App\Data\Ai\AgentModelData;
use App\Models\History;
use App\Models\User;
use App\Services\ModelPricing;
use InvalidArgumentException;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;

final readonly class ConfiguredAgentModels implements ResolvesAgentModel
{
    public function resolve(string $agentClass, ?User $user = null): AgentModelData
    {
        return $this->forAgent($agentClass);
    }

    public function forAgent(string $agentClass, string $profile = 'standard'): AgentModelData
    {
        $profiles = config()->array('ai.agent_profiles');
        $agentProfiles = $profiles[$agentClass] ?? null;
        $model = is_array($agentProfiles) ? ($agentProfiles[$profile] ?? null) : null;
        throw_unless(is_string($model) && $model !== '', InvalidArgumentException::class, "No {$profile} model configured for {$agentClass}.");

        $selected = $this->forModel($model);
        $required = [];
        if (is_a($agentClass, HasTools::class, true)) {
            $required[] = 'tools';
        }
        if (is_a($agentClass, HasStructuredOutput::class, true)) {
            $required[] = 'structured_output';
        }
        if (in_array($agentClass, [AgentRunner::class, FoodPhotoAnalyzerAgent::class], true)) {
            $required[] = 'vision';
        }
        foreach ($required as $capability) {
            throw_unless($selected->supports($capability), InvalidArgumentException::class, "Model {$model} does not support {$capability} required by {$agentClass}.");
        }

        return $selected;
    }

    public function forModel(string $model, ?string $provider = null): AgentModelData
    {
        $catalog = config()->array('ai.model_catalog');
        $definition = $catalog[$model] ?? null;
        throw_unless(is_array($definition), InvalidArgumentException::class, "Register model {$model} in ai.model_catalog before using it.");
        throw_unless(is_string($definition['provider'] ?? null), InvalidArgumentException::class, "Missing provider for model {$model}.");
        throw_if($provider !== null && $definition['provider'] !== $provider, InvalidArgumentException::class, "Model {$model} is not registered for {$provider}.");
        throw_unless(is_array($definition['pricing'] ?? null) && isset($definition['pricing']['input'], $definition['pricing']['output']), InvalidArgumentException::class, "Missing pricing for model {$model}.");
        throw_unless(is_int($definition['max_output_tokens'] ?? null) && $definition['max_output_tokens'] > 0, InvalidArgumentException::class, "Missing output limit for model {$model}.");
        throw_unless(config('ai.providers.'.$definition['provider'].'.driver'), InvalidArgumentException::class, "Unknown provider for model {$model}.");

        return AgentModelData::from([
            'provider' => $definition['provider'], 'model' => $model,
            'maxOutputTokens' => $definition['max_output_tokens'],
            'capabilities' => $definition['capabilities'] ?? [], 'options' => $definition['options'] ?? [],
            'pricing' => ModelPricing::forModel($model, $definition['provider']),
        ]);
    }

    /** @return array<string, array<string, mixed>> */
    public function forTurn(?History $paused, User $user): array
    {
        $recorded = $paused?->chatStreamMeta()['agent_models'] ?? null;
        if (is_array($recorded)) {
            $models = [];
            foreach ($recorded as $agent => $definition) {
                throw_unless(is_string($agent) && is_array($definition), InvalidArgumentException::class, 'Invalid recorded agent profile.');
                $models[$agent] = AgentModelData::from($definition)->toSnapshot();
            }
            throw_unless(isset($models[AgentRunner::class]), InvalidArgumentException::class, 'Missing recorded chat profile.');

            return $models;
        }

        $models = $this->snapshot($user);
        $model = $paused?->chatStreamMeta()['model'] ?? $paused?->meta['model'] ?? null;
        $provider = $paused?->meta['provider'] ?? $paused?->chatStreamMeta()['provider'] ?? null;
        if (is_string($model)) {
            $models[AgentRunner::class] = $this->forModel($model, is_string($provider) ? $provider : null)->toSnapshot();
        }

        return $models;
    }

    /** @return array<string, array<string, mixed>> */
    public function snapshot(?User $user): array
    {
        $models = [];
        foreach (array_keys(config()->array('ai.agent_profiles')) as $agentClass) {
            throw_unless(is_string($agentClass), InvalidArgumentException::class, 'Agent profile keys must be class names.');
            $models[$agentClass] = resolve(ResolvesAgentModel::class)->resolve($agentClass, $user)->toSnapshot();
        }

        return $models;
    }
}
