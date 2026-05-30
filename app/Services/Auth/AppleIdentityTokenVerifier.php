<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Exceptions\AuthTokenException;

final class AppleIdentityTokenVerifier extends IdentityTokenVerifier
{
    private const string JWKS_URL = 'https://appleid.apple.com/auth/keys';

    private const string ISSUER = 'https://appleid.apple.com';

    private const string PRIVATE_RELAY_DOMAIN = '@privaterelay.appleid.com';

    /**
     * @return array{sub: string, email: string|null, email_verified: bool, is_private_relay: bool}
     */
    public function verify(string $identityToken, string $expectedNonce): array
    {
        $audience = config()->string('services.apple.client_id');

        throw_if($audience === '', AuthTokenException::class, 'Apple authentication is not configured.');

        $decoded = $this->decode($identityToken);

        throw_if(($decoded['iss'] ?? null) !== self::ISSUER, AuthTokenException::class, 'Invalid token.');

        throw_if(($decoded['aud'] ?? null) !== $audience, AuthTokenException::class, 'Invalid token.');

        throw_unless(hash_equals(hash('sha256', $expectedNonce), $this->claimString($decoded, 'nonce')), AuthTokenException::class, 'Invalid token.');

        $email = $decoded['email'] ?? null;
        $emailString = is_string($email) ? $email : null;

        $isPrivateRelay = filter_var($decoded['is_private_email'] ?? false, FILTER_VALIDATE_BOOLEAN)
            || ($emailString !== null && str_ends_with($emailString, self::PRIVATE_RELAY_DOMAIN));

        return [
            'sub' => $this->claimString($decoded, 'sub'),
            'email' => $emailString,
            'email_verified' => filter_var($decoded['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'is_private_relay' => $isPrivateRelay,
        ];
    }

    protected function jwksUrl(): string
    {
        return self::JWKS_URL;
    }

    protected function jwksCacheKey(): string
    {
        return 'apple-oauth-jwks';
    }
}
