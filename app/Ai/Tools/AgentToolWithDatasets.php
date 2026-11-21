<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Ai\Attributes\AiToolSensitivity;
use App\Enums\DataSensitivity;
use App\Services\TurnDataRegistry;
use Generator;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Streaming\Events\StreamEvent;
use Laravel\Ai\Tools\AgentTool;
use Laravel\Ai\Tools\Request;

#[AiToolSensitivity(DataSensitivity::Sensitive)]
final class AgentToolWithDatasets extends AgentTool
{
    public function __construct(Agent $agent, private readonly TurnDataRegistry $datasets)
    {
        parent::__construct($agent);
    }

    public function handle(Request $request): string
    {
        $described = array_keys($this->datasets->datasets());

        return parent::handle($request).$this->datasets->footer($described);
    }

    /**
     * @return Generator<int, StreamEvent, mixed, string>
     */
    public function stream(Request $request): Generator
    {
        $described = array_keys($this->datasets->datasets());
        $text = yield from parent::stream($request);

        return $this->agent instanceof HasStructuredOutput ? $text : $text.$this->datasets->footer($described);
    }
}
