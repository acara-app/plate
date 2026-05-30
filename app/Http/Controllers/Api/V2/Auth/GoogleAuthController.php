<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Auth;

use App\Actions\Auth\IssueMobileSession;
use App\Actions\Auth\ResolveGoogleUser;
use App\Exceptions\AccountLinkException;
use App\Exceptions\AuthTokenException;
use App\Http\Requests\Api\V2\Auth\GoogleAuthRequest;
use App\Services\Auth\GoogleIdentityTokenVerifier;
use Illuminate\Http\JsonResponse;

final readonly class GoogleAuthController
{
    public function __construct(
        private GoogleIdentityTokenVerifier $verifier,
        private ResolveGoogleUser $resolveGoogleUser,
        private IssueMobileSession $issueMobileSession,
    ) {}

    public function __invoke(GoogleAuthRequest $request): JsonResponse
    {
        try {
            $claims = $this->verifier->verify($request->string('id_token')->toString());
        } catch (AuthTokenException) {
            return response()->json(['message' => __('Invalid token.')], 401);
        }

        try {
            $user = $this->resolveGoogleUser->handle($claims);
        } catch (AccountLinkException) {
            return response()->json([
                'message' => __('This email is already registered. Please sign in with your password.'),
                'code' => 'email_exists',
            ], 409);
        }

        return response()->json(
            $this->issueMobileSession->handle(
                $user,
                $request->string('device_identifier')->toString(),
                ['chat:converse'],
            )
        );
    }
}
