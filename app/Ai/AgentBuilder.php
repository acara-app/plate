<?php

declare(strict_types=1);

namespace App\Ai;

use App\Actions\GetUserProfileContextAction;
use App\Enums\AgentMode;
use App\Models\User;
use App\Services\ToolRegistry;
use App\Utilities\LanguageUtil;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Files\Base64Image;
use Laravel\Ai\Providers\Tools\ProviderTool;

final readonly class AgentBuilder
{
    public function __construct(
        private ToolRegistry $toolRegistry,
    ) {}

    /**
     * @return array{instructions: string, tools: array<int, Tool|ProviderTool>}
     */
    public function build(AgentPayload $payload, ?User $user = null): array
    {
        $mode = $payload->mode;
        $attachments = $payload->images;
        $webSearchEnabled = $payload->shouldEnableWebSearch();

        return [
            'instructions' => $this->buildInstructions($user, $mode),
            'tools' => $this->buildTools($attachments, $webSearchEnabled),
        ];
    }

    private function buildInstructions(?User $user, AgentMode $mode): string
    {
        $profileData = $this->getProfileData($user);
        $languageCode = $user instanceof User ? ($user->preferred_language ?? 'en') : 'en';
        $timezone = $this->resolveTimezone($user);

        return view('ai.prompts.altani-static', [
            'profileContext' => $profileData['context'],
            'currentTime' => now($timezone)->format('Y-m-d H:i (l)').' ('.$timezone.')',
            'chatMode' => $mode->value,
            'languageLabel' => LanguageUtil::get($languageCode) ?? 'English',
            'languageCode' => $languageCode,
            'isCreateMealPlanMode' => $mode->value === 'create-meal-plan',
        ])->render();
    }

    /**
     * @param  array<int, Base64Image>  $attachments
     * @return array<int, Tool|ProviderTool>
     */
    private function buildTools(array $attachments, bool $webSearchEnabled): array
    {
        $tools = $this->toolRegistry->getTools();

        if ($attachments !== []) {
            $imageTools = $this->toolRegistry->getImageTools($attachments);
            $tools = [...$tools, ...$imageTools];
        }

        if ($webSearchEnabled) {
            $providerTools = $this->toolRegistry->getProviderTools();
            $tools = [...$tools, ...$providerTools];
        }

        return $tools;
    }

    /**
     * @return array<string, mixed>
     */
    private function getProfileData(?User $user): array
    {
        if (! $user instanceof User) {
            return [
                'context' => 'No user context available.',
            ];
        }

        return resolve(GetUserProfileContextAction::class)->handle($user);
    }

    private function resolveTimezone(?User $user): string
    {
        /** @var string|null $sessionTimezone */
        $sessionTimezone = session('timezone');

        if (! $user instanceof User) {
            return $sessionTimezone ?? 'UTC';
        }

        return $sessionTimezone
            ?? $user->timezone
            ?? 'UTC';
    }
}
