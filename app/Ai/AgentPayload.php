<?php

declare(strict_types=1);

namespace App\Ai;

use App\Enums\AgentMode;
use App\Enums\ModelName;
use Laravel\Ai\Files\Base64Image;

final readonly class AgentPayload
{
    /**
     * @param  array<int, Base64Image>  $images
     */
    public function __construct(
        public int $userId,
        public string $message,
        public array $images = [],
        public AgentMode $mode = AgentMode::Ask,
        public ?ModelName $modelName = null,
    ) {}

    public function hasImages(): bool
    {
        return $this->images !== [];
    }

    public function shouldEnableWebSearch(): bool
    {
        return $this->modelName instanceof ModelName && $this->modelName->supportsWebSearch();
    }
}
