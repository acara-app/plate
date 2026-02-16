<?php

declare(strict_types=1);

namespace App\Contracts\Actions;

use App\Actions\GetUserProfileContextAction;
use App\Models\User;
use Illuminate\Container\Attributes\Bind;

#[Bind(GetUserProfileContextAction::class)]
interface GetsUserProfileContext
{
    /**
     * @return array<string, mixed>
     */
    public function handle(User $user): array;
}
