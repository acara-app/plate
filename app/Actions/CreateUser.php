<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use SensitiveParameter;

final readonly class CreateUser
{
    private const string FALLBACK_NAME = 'Friend';

    /**
     * @param  array{email: string, name?: string|null, email_verified_at?: CarbonInterface}  $attributes
     */
    public function handle(array $attributes, #[SensitiveParameter] ?string $password): User
    {
        $user = User::query()->create([
            ...$attributes,
            'name' => $attributes['name'] ?? $this->nameFromEmail($attributes['email']),
            'password' => $password === null ? null : Hash::make($password),
        ]);

        event(new Registered($user));

        return $user;
    }

    private function nameFromEmail(string $email): string
    {
        $words = preg_split('/[^\p{L}]+/u', Str::before($email, '@')) ?: [];
        $word = Arr::first($words, fn (string $word): bool => mb_strlen($word) > 1);

        return $word === null ? self::FALLBACK_NAME : Str::ucfirst(Str::lower($word));
    }
}
