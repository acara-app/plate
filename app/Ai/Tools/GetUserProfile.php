<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Actions\BuildAiSafeUserProfile;
use App\Ai\Attributes\AiToolSensitivity;
use App\Data\Ai\AiSafeUserProfileData;
use App\Enums\DataSensitivity;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

#[AiToolSensitivity(DataSensitivity::Sensitive)]
final readonly class GetUserProfile implements Tool
{
    public function __construct(
        private BuildAiSafeUserProfile $profileData,
    ) {}

    public function name(): string
    {
        return 'get_user_profile';
    }

    public function description(): string
    {
        return "Retrieve the current user's AI-safe profile information. Use the smallest relevant section before giving personalized nutrition, fitness, health-condition, allergy, medication, household, calorie, macro, or meal advice.";
    }

    public function handle(Request $request): string
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return (string) json_encode([
                'error' => 'User not authenticated',
                'profile' => null,
            ]);
        }

        /** @var string $section */
        $section = $request['section'] ?? 'all';

        $profileData = $this->profileData->handle($user);

        if ($section === 'all') {
            return (string) json_encode([
                'success' => true,
                'onboarding_completed' => $profileData->onboardingCompleted,
                'missing_data' => $profileData->missingData,
                'profile' => $profileData->toArray(),
            ]);
        }

        if (! $profileData->hasSection($section)) {
            return (string) json_encode([
                'error' => sprintf("Section '%s' not found. Available sections: %s", $section, implode(', ', AiSafeUserProfileData::supportedSections())),
                'profile' => null,
            ]);
        }

        return (string) json_encode([
            'success' => true,
            'section' => $section,
            'data' => $profileData->section($section),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'section' => $schema->string()
                ->enum(AiSafeUserProfileData::supportedSections())
                ->description('Which profile section to retrieve. Use the smallest relevant section first; use "safety" for allergies, health conditions, medications, and household constraints; use "all" only when the request spans multiple profile areas.')
                ->required()
                ->nullable(),
        ];
    }
}
