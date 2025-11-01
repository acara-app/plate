<?php

declare(strict_types=1);

namespace Tests\Fixtures;

final class GoogleOAuthResponses
{
    /**
     * Get a mock Google OAuth user response.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function user(array $overrides = []): array
    {
        return array_merge([
            'id' => 'google123456789',
            'email' => 'user@example.com',
            'verified_email' => true,
            'name' => 'Test User',
            'given_name' => 'Test',
            'family_name' => 'User',
            'picture' => 'https://lh3.googleusercontent.com/a/default-user',
            'locale' => 'en',
        ], $overrides);
    }

    /**
     * Get a mock Google OAuth token response.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function token(array $overrides = []): array
    {
        return array_merge([
            'access_token' => 'ya29.test-access-token',
            'expires_in' => 3599,
            'scope' => 'openid https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile',
            'token_type' => 'Bearer',
            'id_token' => 'eyJhbGciOiJSUzI1NiIsImtpZCI6InRlc3QifQ.test.test',
        ], $overrides);
    }
}
