<?php

declare(strict_types=1);

namespace App\Ai;

use App\Data\Ai\AgentModelData;
use Laravel\Ai\Files\Base64Image;

final readonly class AgentRequest
{
    /**
     * @param  array<int, Base64Image>  $images
     * @param  array<string, array<string, mixed>>  $agentModels
     */
    public function __construct(
        public string $message,
        public array $images = [],
        public ?string $conversationId = null,
        public ?string $streamId = null,
        public ?AgentModelData $model = null,
        public array $agentModels = [],
    ) {}

    public function hasImages(): bool
    {
        return $this->images !== [];
    }

    /**
     * @phpstan-assert-if-true !null $this->conversationId
     */
    public function hasExistingConversation(): bool
    {
        return $this->conversationId !== null;
    }

    public function shouldEnableWebSearch(): bool
    {
        return $this->model?->supports('web_search') ?? false;
    }
}
