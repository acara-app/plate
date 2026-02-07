<?php

declare(strict_types=1);

namespace App\Actions;

use App\Ai\Agents\NutritionAdvisor;
use App\Enums\AgentMode;
use App\Models\History;
use App\Models\User;
use Laravel\Ai\Messages\MessageRole;

final readonly class GenerateAiResponseAction
{
    public function __construct(private NutritionAdvisor $nutritionAdvisor) {}

    /**
     * Generate an AI response for a user message.
     *
     * @param  string  $message  The user's message
     * @param  AgentMode  $mode  The agent mode (ask, generate-meal-plan, etc.)
     * @return string The AI response text
     */
    public function handle(User $user, string $message, AgentMode $mode = AgentMode::Ask): string
    {
        $agent = $this->nutritionAdvisor
            ->withMode($mode)
            ->forUser($user);

        // Store user message
        History::query()->create([
            'user_id' => $user->id,
            'conversation_id' => null, // Telegram chats don't have conversation IDs
            'agent' => NutritionAdvisor::class,
            'role' => MessageRole::User,
            'content' => $message,
        ]);

        // Generate AI response (non-streaming)
        $response = $agent->prompt($message);

        // Store assistant response
        History::query()->create([
            'user_id' => $user->id,
            'conversation_id' => null,
            'agent' => NutritionAdvisor::class,
            'role' => MessageRole::Assistant,
            'content' => $response->text,
            'usage' => $response->usage?->toArray(),
        ]);

        return $response->text;
    }
}
