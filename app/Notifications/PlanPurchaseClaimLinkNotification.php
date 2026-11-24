<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\PlanPurchase;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class PlanPurchaseClaimLinkNotification extends Notification
{
    public function __construct(public readonly string $link) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Activate your Starter plan')
            ->greeting('Thanks for choosing Starter!')
            ->line('Your payment went through. Tap the button to activate Starter and keep logging meals from photos.')
            ->action('Activate Starter', $this->link)
            ->line(sprintf('The link works once and expires in %d minutes. If it expires, open it anyway and we will send you a new one.', PlanPurchase::CLAIM_LINK_MINUTES))
            ->line('No password needed. After you activate, you can add one to log in on other devices.')
            ->line('Did not buy Starter? You can ignore this email.')
            ->salutation('— '.config()->string('app.name'));
    }
}
