<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\DeletedUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class DeleteUser
{
    public function handle(User $user): void
    {
        DB::transaction(function () use ($user): void {
            DeletedUser::query()->create([
                'user_id' => $user->id,
                'email' => $user->email,
                'deleted_at' => now(),
            ]);

            $user->delete();
        });
    }
}
