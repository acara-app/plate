<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Support\Collection;
use Laravel\Ai\Responses\Data\Step;

/** @codeCoverageIgnore */
final readonly class ChatStreamDelivery
{
    /**
     * @param  Collection<int, Step>  $steps  the run's generation steps, carrying paused-turn replay state; persisted for a resume, never sent to clients
     */
    public function __construct(
        public ChatStreamResult $result,
        public bool $cancelled,
        public Collection $steps = new Collection,
        public ?string $provider = null,
    ) {}
}
