<?php

declare(strict_types=1);

namespace App\Data\Billing;

use App\Data\Ai\AgentModelData;
use Spatie\LaravelData\Data;

final class PhotoModel extends Data
{
    public function __construct(
        public string $provider,
        public string $model,
        public int $maxTokens = 35000,
    ) {}

    public static function standard(): self
    {
        $model = AgentModelData::current();

        return new self($model->provider, $model->model);
    }
}
