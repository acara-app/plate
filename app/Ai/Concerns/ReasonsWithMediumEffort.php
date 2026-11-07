<?php

declare(strict_types=1);

namespace App\Ai\Concerns;

use Laravel\Ai\Enums\Lab;

trait ReasonsWithMediumEffort
{
    /** @return array<string, mixed> */
    public function providerOptions(Lab|string $provider): array
    {
        $provider = $provider instanceof Lab ? $provider->value : $provider;

        return $provider === Lab::OpenAI->value ? ['reasoning' => ['effort' => 'medium']] : [];
    }
}
