<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\DeletedUser;
use App\Models\MealPhoto;
use App\Models\User;
use App\Notifications\AccountDeletedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

final readonly class DeleteUser
{
    public function handle(User $user): void
    {
        Storage::disk(MealPhoto::DISK)->deleteDirectory(MealPhoto::directoryFor($user));

        DB::transaction(function () use ($user): void {
            DeletedUser::query()->create([
                'user_id' => $user->id,
                'email' => $user->email,
                'deleted_at' => now(),
            ]);

            $user->delete();
        });

        Notification::route('mail', [$user->email => $user->name])
            ->notify(new AccountDeletedNotification($user->name));
    }
}
