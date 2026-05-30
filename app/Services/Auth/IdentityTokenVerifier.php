<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Exceptions\AuthTokenException;
use Firebase\JWT\JWK;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

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
            $response = Http::get($this->jwksUrl());

            throw_if($response->failed(), AuthTokenException::class, 'Unable to fetch identity provider signing keys.');

            return (array) $response->json();
        });

        return JWK::parseKeySet($jwks);
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
