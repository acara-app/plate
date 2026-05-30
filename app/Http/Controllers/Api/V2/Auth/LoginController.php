<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Auth;

use App\Actions\Auth\IssueMobileSession;
use App\Http\Requests\Api\V2\Auth\LoginRequest;
use App\Models\MobileTwoFactorChallenge;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class LoginController
{
    public function __construct(private IssueMobileSession $issueMobileSession) {}

    public function __invoke(LoginRequest $request): JsonResponse
    {
        $password = $request->string('password')->toString();
        $deviceIdentifier = $request->string('device_identifier')->toString();

        $provider = Auth::getProvider();
        $user = $provider->retrieveByCredentials([
            'email' => $request->string('email')->toString(),
            'password' => $password,
        ]);

        if (! $user instanceof User || ! $provider->validateCredentials($user, ['password' => $password])) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        if ($user->hasEnabledTwoFactorAuthentication()) {
            $challengeToken = Str::random(64);

            MobileTwoFactorChallenge::query()->create([
                'token_hash' => hash('sha256', $challengeToken),
                'user_id' => $user->id,
                'device_identifier' => $deviceIdentifier,
                'expires_at' => now()->addMinutes(5),
            ]);

            return response()->json([
                'two_factor_required' => true,
                'challenge_token' => $challengeToken,
            ], 409);
        }

        return response()->json(
            $this->issueMobileSession->handle($user, $deviceIdentifier, ['chat:converse'])
        );
    }
}
