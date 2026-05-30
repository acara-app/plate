<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Auth;

use App\Actions\Auth\IssueMobileSession;
use App\Actions\Auth\ResolveGoogleUser;
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
        $claims = $this->verifier->verify($request->string('id_token')->toString());

        $user = $this->resolveGoogleUser->handle($claims);

        return response()->json(
            $this->issueMobileSession->handle(
                $user,
                $request->string('device_identifier')->toString(),
                ['chat:converse'],
            )
        );
    }
}
