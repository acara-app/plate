<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Contracts\Actions\GetsUserProfileContext;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

final readonly class GetUserProfile implements Tool
{
    public function __construct(
        private GetsUserProfileContext $profileContext,
    ) {}

    public function name(): string
    {
        return 'get_user_profile';
    }

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): string
    {
        return "Retrieve the current user's profile information including biometrics, dietary preferences, health conditions, medications, and goals. Use this when you need specific user data to provide personalized advice.";
    }

    /**
     * Execute the tool.
     */
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

        $context = $this->profileContext->handle($user);

        if ($section === 'all') {
            return (string) json_encode([
                'success' => true,
                'onboarding_completed' => $context['onboarding_completed'],
                'missing_data' => $context['missing_data'],
                'profile' => $context['raw_data'],
            ]);
        }

        // Return specific section
        /** @var array<string, mixed>|null $rawData */
        $rawData = $context['raw_data'];

        if (! is_array($rawData)) {
            return (string) json_encode([
                'error' => 'Profile data not available',
                'profile' => null,
            ]);
        }

        $sectionData = $rawData[$section] ?? null;

        if ($sectionData === null) {
            return (string) json_encode([
                'error' => sprintf("Section '%s' not found. Available sections: biometrics, dietary_preferences, health_conditions, medications, goals", $section),
                'profile' => null,
            ]);
        }

        return (string) json_encode([
            'success' => true,
            'section' => $section,
            'data' => $sectionData,
        ]);
    }

    /**
     * Get the tool's schema definition.
     *
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'section' => $schema->string()
                ->enum(['all', 'biometrics', 'dietary_preferences', 'health_conditions', 'medications', 'goals'])
                ->description('Which section of the profile to retrieve. Use "all" for complete profile, or specify a section for specific data.')
                ->required(),
        ];
    }
}
