<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\SubscriptionCancelledNotification;
use Carbon\CarbonImmutable;

covers(SubscriptionCancelledNotification::class);

it('tells the subscriber when their plan ends, in their timezone, and where to renew', function (bool $endedNow, string $endsLine): void {
    config(['app.name' => 'Acara Plate']);

    $user = User::factory()->make(['name' => 'Sam', 'timezone' => 'America/Regina']);
    $notification = new SubscriptionCancelledNotification('Snap Pro', CarbonImmutable::parse('2026-12-15 03:00:00', 'UTC'), $endedNow);
    $mail = $notification->toMail($user);

    expect($notification->via($user))->toBe(['mail'])
        ->and($mail->subject)->toBe('Your Snap Pro plan has been cancelled')
        ->and($mail->greeting)->toBe('Hi Sam,')
        ->and($mail->introLines)->toBe([$endsLine, 'Your meals, logs and history stay right where they are.'])
        ->and($mail->actionUrl)->toBe(route('billing.index'));
})->with([
    'keeps access until the paid period ends' => [false, "Your Snap Pro plan has been cancelled, and you won't be charged again. You can keep using it until December 14, 2026, then your account moves to the free plan."],
    'ended right away' => [true, "Your Snap Pro plan was cancelled on December 14, 2026, and you won't be charged again. Your account is now on the free plan."],
]);
