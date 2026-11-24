<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use SensitiveParameter;

final readonly class SetFirstPassword
{
    public function handle(User $user, #[SensitiveParameter] string $password): bool
    {
        $saved = User::query()->whereKey($user->id)->whereNull('password')->update([
            'password' => Hash::make($password),
            'remember_token' => Str::random(60),
        ]) === 1;

        if ($saved) {
            $user->refresh();
        }

        return $saved;
    }
}
