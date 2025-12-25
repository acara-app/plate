<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserNotificationsRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class UserNotificationsController
{
    public function edit(#[CurrentUser] User $user): Response
    {
        return Inertia::render('user-notifications/edit', [
            'notificationSettings' => $user->notification_settings,
            'defaultThresholds' => [
                'low' => config('glucose.hypoglycemia_threshold'),
                'high' => config('glucose.hyperglycemia_threshold'),
            ],
        ]);
    }

    public function update(UpdateUserNotificationsRequest $request, #[CurrentUser] User $user): RedirectResponse
    {
        $user->update([
            'settings' => $request->validated(),
        ]);

        return to_route('user-notifications.edit')->with('status', 'notification-settings-updated');
    }
}
