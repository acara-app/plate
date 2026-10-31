<?php

declare(strict_types=1);

use App\Notifications\AccountDeletedNotification;
use Illuminate\Notifications\AnonymousNotifiable;

covers(AccountDeletedNotification::class);

it('confirms the deletion by email and points to support', function (): void {
    config(['app.name' => 'Acara Plate']);

    $notifiable = new AnonymousNotifiable;
    $notification = new AccountDeletedNotification('Sam');
    $mail = $notification->toMail($notifiable);

    expect($notification->via($notifiable))->toBe(['mail'])
        ->and($mail->subject)->toBe('Your Acara Plate account has been deleted')
        ->and($mail->greeting)->toBe('Hi Sam,')
        ->and($mail->actionUrl)->toBe(route('support'))
        ->and((string) $mail->render())->toContain('permanently erased within 30 days');
});
