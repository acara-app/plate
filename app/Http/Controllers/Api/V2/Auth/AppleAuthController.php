<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Auth;

use App\Actions\Auth\FindOrCreateUserFromAppleSignIn;
use App\Actions\Auth\IssueMobileSession;
use App\Exceptions\AccountLinkException;
use App\Exceptions\AuthTokenException;
use App\Http\Requests\Api\V2\Auth\AppleAuthRequest;
use App\Models\MobileAuthNonce;
use App\Services\Auth\AppleIdentityTokenVerifier;
use Illuminate\Http\JsonResponse;

final readonly class AppleAuthController
{
    public function __construct(
        private AppleIdentityTokenVerifier $verifier,
        private FindOrCreateUserFromAppleSignIn $findOrCreateUser,
        private IssueMobileSession $issueMobileSession,
    ) {}

    public function __invoke(AppleAuthRequest $request): JsonResponse
    {
        $deviceIdentifier = $request->string('device_identifier')->toString();

        $nonce = MobileAuthNonce::query()
            ->where('nonce_id', $request->string('nonce_id')->toString())
            ->where('device_identifier', $deviceIdentifier)
            ->first();

        if (! $nonce instanceof MobileAuthNonce || $nonce->isExpired()) {
            $nonce?->delete();

            return response()->json(['message' => __('Invalid token.')], 401);
        }

        $rawNonce = $nonce->nonce;
        $nonce->delete();

        try {
            $claims = $this->verifier->verify($request->string('identity_token')->toString(), $rawNonce);
        } catch (AuthTokenException) {
            return response()->json(['message' => __('Invalid token.')], 401);
        }

        $name = $request->string('full_name')->toString();

        try {
            $user = $this->findOrCreateUser->handle($claims, $name !== '' ? $name : null);
        } catch (AccountLinkException) {
            return response()->json([
                'message' => __('This email is already registered. Please sign in with your password.'),
                'code' => 'email_exists',
            ], 409);
        }

        return response()->json(
            $this->issueMobileSession->handle($user, $deviceIdentifier, ['chat:converse'])
        );
    }
}
