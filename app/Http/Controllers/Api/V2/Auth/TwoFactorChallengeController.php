<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Auth;

use App\Actions\Auth\IssueMobileSession;
use App\Http\Requests\Api\V2\Auth\TwoFactorChallengeRequest;
use App\Models\MobileTwoFactorChallenge;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;

final readonly class TwoFactorChallengeController
{
    public function __construct(
        private IssueMobileSession $issueMobileSession,
        private TwoFactorAuthenticationProvider $provider,
    ) {}

    public function __invoke(TwoFactorChallengeRequest $request): JsonResponse
    {
        $deviceIdentifier = $request->string('device_identifier')->toString();

        $challenge = MobileTwoFactorChallenge::query()
            ->where('token_hash', hash('sha256', $request->string('challenge_token')->toString()))
            ->where('device_identifier', $deviceIdentifier)
            ->first();

        if (! $challenge instanceof MobileTwoFactorChallenge || $challenge->isExpired()) {
            $challenge?->delete();

            throw ValidationException::withMessages([
                'challenge_token' => [__('This challenge is invalid or has expired.')],
            ]);
        }

        if ($challenge->attempts >= 5) {
            $challenge->delete();

            throw ValidationException::withMessages([
                'code' => [__('Too many attempts. Please sign in again.')],
            ]);
        }

        $challenge->increment('attempts');

        $user = $challenge->user;

        if (! $this->passesChallenge($request, $user)) {
            throw ValidationException::withMessages([
                'code' => [__('The provided two-factor code was invalid.')],
            ]);
        }

        $challenge->delete();

        return response()->json(
            $this->issueMobileSession->handle($user, $deviceIdentifier, ['chat:converse'])
        );
    }

    private function passesChallenge(TwoFactorChallengeRequest $request, User $user): bool
    {
        $code = $request->string('code')->toString();
        $secret = $user->two_factor_secret;

        if ($code !== '' && $secret !== null) {
            $decryptedSecret = Fortify::currentEncrypter()->decrypt($secret);

            return is_string($decryptedSecret) && $this->provider->verify($decryptedSecret, $code);
        }

        $recoveryCode = $request->string('recovery_code')->toString();

        if ($recoveryCode !== '' && in_array($recoveryCode, $user->recoveryCodes(), true)) {
            $user->replaceRecoveryCode($recoveryCode);

            return true;
        }

        return false;
    }
}
