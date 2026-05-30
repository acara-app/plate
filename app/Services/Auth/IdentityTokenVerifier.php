<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Exceptions\AuthTokenException;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

abstract class IdentityTokenVerifier
{
    abstract protected function jwksUrl(): string;

    abstract protected function jwksCacheKey(): string;

    /**
     * @return array<string, Key>
     */
    protected function signingKeys(): array
    {
        /** @var array<string, mixed> $jwks */
        $jwks = Cache::remember($this->jwksCacheKey(), now()->addHours(6), function (): array {
            $response = Http::timeout(5)->connectTimeout(3)->get($this->jwksUrl());

            throw_if($response->failed(), AuthTokenException::class, 'Unable to fetch identity provider signing keys.');

            return (array) $response->json();
        });

        return JWK::parseKeySet($jwks);
    }

    /**
     * @return array<string, mixed>
     */
    protected function decode(string $token): array
    {
        try {
            /** @var array<string, mixed> $decoded */
            $decoded = (array) JWT::decode($token, $this->signingKeys());

            return $decoded;
        } catch (Throwable) {
            throw new AuthTokenException('Invalid token.');
        }
    }

    /**
     * @param  array<string, mixed>  $claims
     */
    protected function claimString(array $claims, string $key): string
    {
        $value = $claims[$key] ?? null;

        return is_scalar($value) ? (string) $value : '';
    }
}
