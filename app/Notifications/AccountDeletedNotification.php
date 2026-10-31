<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class AccountDeletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $name)
    {
        $this->afterCommit();
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = config()->string('app.name');

        return (new MailMessage)
            ->subject(sprintf('Your %s account has been deleted', $appName))
            ->greeting(sprintf('Hi %s,', $this->name))
            ->line(sprintf('Your %s account and profile have been deleted, just as you asked.', $appName))
            ->line('Any remaining data tied to your account is permanently erased within 30 days. This is the last email you will get from us.')
            ->line("Didn't ask for this? Let us know right away.")
            ->action('Contact support', route('support'))
            ->salutation(sprintf('Thanks for giving %s a try.', $appName));
    }
}
