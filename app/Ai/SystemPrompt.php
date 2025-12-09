<?php

declare(strict_types=1);

namespace App\Ai;

use const PHP_EOL;

use Stringable;

use function implode;

/**
 * Inspired by Neuron-AI
 *
 * @see https://github.com/neuron-core/neuron-ai
 */
final class SystemPrompt implements Stringable
{
    /**
     * @param  array<int, string>  $background
     * @param  array<int, string>  $steps
     * @param  array<int, string>  $output
     * @param  array<int, string>  $toolsUsage
     */
    public function __construct(
        public array $background,
        public array $steps = [],
        public array $output = [],
        public array $toolsUsage = []
    ) {}

    public function __toString(): string
    {
        $prompt = '# IDENTITY AND PURPOSE'.PHP_EOL.implode(PHP_EOL, $this->background);

        if ($this->steps !== []) {
            $prompt .= PHP_EOL.PHP_EOL.'# INTERNAL ASSISTANT STEPS'.PHP_EOL.implode(PHP_EOL, $this->steps);
        }

        if ($this->output !== []) {
            $prompt .= PHP_EOL.PHP_EOL.'# OUTPUT INSTRUCTIONS'.PHP_EOL.' - '.implode(PHP_EOL.' - ', $this->output);
        }

        if ($this->toolsUsage !== []) {
            $prompt .= PHP_EOL.PHP_EOL.'# TOOLS USAGE RULES'.PHP_EOL.' - '.implode(PHP_EOL.' - ', $this->toolsUsage);
        }

        return $prompt;
    }
}
