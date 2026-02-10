<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests;

use App\Enums\AgentMode;
use App\Enums\ModelName;
use App\Http\Requests\StoreAgentConversationRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class StoreAgentConversationRequestTest extends TestCase
{
    protected function createRequest(array $data): StoreAgentConversationRequest
    {
        $request = new StoreAgentConversationRequest();

        // Merge data so it's available in all()
        $request->merge($data);

        // Run validation to populate validated data
        $validator = Validator::make($data, $request->rules());
        $request->setValidator($validator);

        if ($validator->fails()) {
            $this->fail('Validation failed: ' . json_encode($validator->errors()->all()));
        }

        return $request;
    }

    public function test_user_message_extraction(): void
    {
        $messages = [
            [
                'role' => 'user',
                'parts' => [
                    ['type' => 'text', 'text' => 'Hello world'],
                ],
            ],
            [
                'role' => 'assistant',
                'parts' => [
                    ['type' => 'text', 'text' => 'AI Response'],
                ],
            ],
        ];

        $request = $this->createRequest([
            'messages' => $messages,
            'mode' => AgentMode::Ask->value,
            'model' => ModelName::GPT_5_MINI->value,
        ]);

        $this->assertSame('Hello world', $request->userMessage());
    }

    public function test_user_message_extraction_ignores_non_text_parts(): void
    {
        $messages = [
            [
                'role' => 'user',
                'parts' => [
                    ['type' => 'image', 'image' => '...'],
                    ['type' => 'text', 'text' => 'Text content'],
                ],
            ],
        ];

        $request = $this->createRequest([
            'messages' => $messages,
            'mode' => AgentMode::Ask->value,
            'model' => ModelName::GPT_5_MINI->value,
        ]);

        $this->assertSame('Text content', $request->userMessage());
    }

    public function test_mode_and_model_extraction(): void
    {
        $request = $this->createRequest([
            'messages' => [['role' => 'user', 'parts' => [['type' => 'text', 'text' => 'Hi']]]],
            'mode' => AgentMode::GenerateMealPlan->value,
            'model' => ModelName::GEMINI_2_5_FLASH->value,
        ]);

        $this->assertEquals(AgentMode::GenerateMealPlan, $request->mode());
        $this->assertEquals(ModelName::GEMINI_2_5_FLASH, $request->modelName());
    }
}
