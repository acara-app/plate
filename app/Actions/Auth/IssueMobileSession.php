<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;

/** @codeCoverageIgnore */
final readonly class IssueMobileSession
{
    public function __construct(private IssueMobileAuthToken $issueMobileAuthToken) {}

    /**
     * @param  list<string>  $abilities
     * @return array<string, mixed>
     */
    public function handle(User $user, string $deviceIdentifier, array $abilities): array
    {
        $token = $this->issueMobileAuthToken->handle($user, $deviceIdentifier, $abilities);

        return [
            'api_token' => $token->plainTextToken,
            'abilities' => $abilities,
            'user' => [
                'name' => $user->name,
            ],
            'consent_required' => $user->requiresConsent(),
        ];
    }
}
