<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\UpdateUserTimezoneAction;
use App\Models\User;
use Illuminate\Auth\Events\Login;

final readonly class SyncUserTimezoneOnSignIn
{
    public function __construct(private UpdateUserTimezoneAction $updateUserTimezone) {}

    public function handle(Login $event): void
    {
        $timezone = request()->hasSession() ? request()->session()->get('timezone') : null;

        if ($event->user instanceof User && is_string($timezone) && $timezone !== $event->user->resolveTimezone()) {
            $this->updateUserTimezone->handle($event->user, $timezone);
        }
    }
}
