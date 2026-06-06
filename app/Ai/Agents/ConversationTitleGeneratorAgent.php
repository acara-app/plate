<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Contracts\GeneratesConversationTitle;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Laravel\Ai\Responses\StructuredAgentResponse;

#[Provider('openai')]
#[MaxTokens(100)]
#[Timeout(30)]
final class ConversationTitleGeneratorAgent implements Agent, GeneratesConversationTitle, HasStructuredOutput
{
    use Promptable;

    private string $language = 'English';

    private string $languageCode = 'en';

    public function instructions(): string
    {
        return view('ai.prompts.conversation-title', [
            'language' => $this->language,
            'languageCode' => $this->languageCode,
        ])->render();
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->required(),
        ];
    }

    // @codeCoverageIgnoreStart
    public function generate(string $message, string $language, string $languageCode): string
    {
        $this->language = $language;
        $this->languageCode = $languageCode;

        /** @var StructuredAgentResponse $response */
        $response = $this->prompt(
            prompt: $message,
            model: 'gpt-5-nano',
        );

        /** @var array{title?: string} $data */
        $data = $response->toArray();

        return $data['title'] ?? '';
    }

    // @codeCoverageIgnoreEnd
}
