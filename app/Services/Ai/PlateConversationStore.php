<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Actions\AbandonPendingApprovals;
use Illuminate\Support\Facades\Context;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Storage\DatabaseConversationStore;

final class PlateConversationStore extends DatabaseConversationStore
{
    public const string APP_MANAGED = 'chat.app_managed_persistence';

    public static function appManaged(): bool
    {
        return Context::get(self::APP_MANAGED) === true;
    }

    public function storeUserMessage(string $conversationId, ?string $participantType, string|int|null $participantId, AgentPrompt $prompt): string
    {
        if (self::appManaged()) {
            return '';
        }

        resolve(AbandonPendingApprovals::class)->handle($conversationId);

        return parent::storeUserMessage($conversationId, $participantType, $participantId, $prompt);
    }

    public function storeAssistantMessage(string $conversationId, ?string $participantType, string|int|null $participantId, AgentPrompt $prompt, AgentResponse $response): ?string
    {
        if (self::appManaged()) {
            return null;
        }

        return parent::storeAssistantMessage($conversationId, $participantType, $participantId, $prompt, $response);
    }
}
