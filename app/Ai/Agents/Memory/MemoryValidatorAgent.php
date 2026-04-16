<?php

declare(strict_types=1);

namespace App\Ai\Agents\Memory;

use App\Ai\Agents\Memory\Concerns\UsesMemoryAgentConfig;
use App\Ai\SystemPrompt;
use App\Data\Memory\MemoryValidationResultData;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Laravel\Ai\Responses\StructuredAgentResponse;

#[MaxTokens(1024)]
#[Timeout(60)]
final class MemoryValidatorAgent implements Agent, HasStructuredOutput
{
    use Promptable;
    use UsesMemoryAgentConfig;

    public function instructions(): string
    {
        return (string) new SystemPrompt(
            background: [
                'You are a memory validator.',
                'You judge whether a stored memory statement is still accurate given the context supplied.',
            ],
            steps: [
                '1. Read the memory and the provided context (if any).',
                '2. Decide whether the memory is still valid (is_valid: true/false).',
                '3. Give a confidence score between 0.0 and 1.0.',
                '4. If invalid, briefly explain why in "reason" and propose a corrected phrasing in "suggested_update".',
            ],
            output: [
                'is_valid and confidence are always required.',
                'Populate reason only when the memory looks wrong or stale.',
                'Populate suggested_update only when you can propose a corrected statement.',
            ],
        );
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'is_valid' => $schema->boolean()->required()->description('True if the memory is still accurate.'),
            'confidence' => $schema->number()->required()->description('Confidence in the judgment, 0.0-1.0.'),
            'reason' => $schema->string()->nullable()->description('Explanation when the memory is invalid.'),
            'suggested_update' => $schema->string()->nullable()->description('Suggested corrected statement when invalid.'),
        ];
    }

    /**
     * @codeCoverageIgnore
     */
    public function validateMemory(string $prompt): MemoryValidationResultData
    {
        /** @var StructuredAgentResponse $response */
        $response = $this->prompt($prompt);

        /** @var array{is_valid?: bool, confidence?: float|int, reason?: string|null, suggested_update?: string|null} $data */
        $data = $response->toArray();

        return new MemoryValidationResultData(
            isValid: (bool) ($data['is_valid'] ?? false),
            confidence: (float) ($data['confidence'] ?? 0.0),
            reason: isset($data['reason']) && is_string($data['reason']) ? $data['reason'] : null,
            suggestedUpdate: isset($data['suggested_update']) && is_string($data['suggested_update']) ? $data['suggested_update'] : null,
        );
    }
}
