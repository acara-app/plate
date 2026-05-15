<?php

declare(strict_types=1);

namespace App\Actions;

use App\Contracts\Actions\GetsUserProfileContext;
use App\Models\User;
use App\Services\Ai\UserProfileContextFormatter;

final readonly class GetUserProfileContextAction implements GetsUserProfileContext
{
    public function __construct(
        private BuildAiSafeUserProfile $profileData,
        private UserProfileContextFormatter $formatter,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(User $user): array
    {
        $profileData = $this->profileData->handle($user);

        return [
            'onboarding_completed' => $profileData->onboardingCompleted,
            'missing_data' => $profileData->missingData,
            'context' => $this->formatter->format($profileData),
            'raw_data' => $profileData->toArray(),
        ];
    }
}
