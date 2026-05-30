<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Exceptions\AuthTokenException;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Throwable;

final class GoogleIdentityTokenVerifier extends IdentityTokenVerifier
{
    private const string JWKS_URL = 'https://www.googleapis.com/oauth2/v3/certs';

    private const array ISSUERS = ['https://accounts.google.com', 'accounts.google.com'];

    /**
     * @return array{sub: string, email: string|null, email_verified: bool, name: string|null}
     */
    public function verify(string $idToken): array
    {
        $allowedAudiences = config()->array('services.google.allowed_audiences');

        throw_if($allowedAudiences === [], AuthTokenException::class, 'Google authentication is not configured.');

        try {
            /** @var array<string, mixed> $decoded */
            $decoded = (array) JWT::decode($idToken, $this->signingKeys());
        } catch (Throwable) {
            throw new AuthTokenException('Invalid token.');
        }

        throw_unless(in_array($decoded['iss'] ?? null, self::ISSUERS, true), AuthTokenException::class, 'Invalid token.');

        throw_unless(in_array($decoded['aud'] ?? null, $allowedAudiences, true), AuthTokenException::class, 'Invalid token.');

        $this->guardAgainstReplay($decoded);

        $email = $decoded['email'] ?? null;
        $name = $decoded['name'] ?? null;

        return [
            'sub' => $this->claimString($decoded, 'sub'),
            'email' => is_string($email) ? $email : null,
            'email_verified' => filter_var($decoded['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'name' => is_string($name) ? $name : null,
        ];
    }

    protected function jwksUrl(): string
    {
        return self::JWKS_URL;
    }

    protected function jwksCacheKey(): string
    {
        return 'google-oauth-jwks';
    }

    /**
     * @param  array<string, mixed>  $decoded
     */
    private function guardAgainstReplay(array $decoded): void
    {
        $jti = $this->claimString($decoded, 'jti');

        if ($jti === '') {
            $jti = $this->claimString($decoded, 'sub').':'.$this->claimString($decoded, 'iat');
        }

        $cacheKey = 'google-idtoken:'.hash('sha256', $jti);

        throw_if(Cache::has($cacheKey), AuthTokenException::class, 'Token has already been used.');

        Cache::put($cacheKey, true, now()->addHour());
    }
}
