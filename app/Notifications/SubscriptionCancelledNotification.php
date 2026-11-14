<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use App\Utilities\LocalizedDate;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class SubscriptionCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $plan,
        public readonly CarbonImmutable $endsAt,
        public readonly bool $endedNow,
    ) {
        $this->afterCommit();
    }

    /**
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $locale = app()->getLocale();
        $date = LocalizedDate::format($this->endsAt->setTimezone($notifiable->resolveTimezone()), $locale);

        return (new MailMessage)
            ->subject(__('common.subscription_cancelled_email.subject', ['plan' => $this->plan]))
            ->greeting(__('common.subscription_cancelled_email.greeting', ['name' => $notifiable->name]))
            ->line(__($this->endedNow ? 'common.subscription_cancelled_email.ended' : 'common.subscription_cancelled_email.scheduled', ['plan' => $this->plan, 'date' => $date]))
            ->line(__('common.subscription_cancelled_email.data_kept'))
            ->action(__('common.subscription_cancelled_email.action'), route('billing.index'))
            ->line(__('common.subscription_cancelled_email.unexpected', ['url' => route('support')]))
            ->salutation(__('common.subscription_cancelled_email.salutation', ['app' => config()->string('app.name')]));
    }
}
