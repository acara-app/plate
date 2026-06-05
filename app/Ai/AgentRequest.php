<?php

declare(strict_types=1);

namespace App\Ai;

use App\Enums\ModelName;
use Laravel\Ai\Files\Base64Image;

final readonly class AgentRequest
{
    /**
     * @param  array<int, Base64Image>  $images
     */
    public function __construct(
        public string $message,
        public array $images = [],
        public ?ModelName $modelName = null,
        public ?string $conversationId = null,
        public ?string $streamId = null,
    ) {}

    public function hasImages(): bool
    {
        return $this->images !== [];
    }

    public function hasExistingConversation(): bool
    {
        return $this->conversationId !== null;
    }

    public function shouldEnableWebSearch(): bool
    {
        return $this->modelName instanceof ModelName && $this->modelName->supportsWebSearch();
    }
}
