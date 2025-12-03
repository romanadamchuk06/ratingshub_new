<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * SUBSCRIPTION CANCELLED NOTIFICATION
 * ====================================
 *
 * Wird gesendet wenn:
 * - User sein Abo aktiv kündigt
 * - Abo wegen Zahlungsausfall endet
 * - Grace Period abläuft
 */
class SubscriptionCancelled extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Dein Abonnement wurde gekündigt')
            ->greeting('Hallo ' . $notifiable->name . ',')
            ->line('Dein Abonnement wurde gekündigt und ist nicht mehr aktiv.')
            ->line('Du hast ab sofort keinen Zugriff mehr auf Premium-Features.')
            ->line('**Was du jetzt tun kannst:**')
            ->line('- Dein Abonnement neu aktivieren')
            ->line('- Deine Zahlungsmethode aktualisieren')
            ->line('- Zu einem anderen Plan wechseln')
            ->action('Abonnement reaktivieren', route('subscription.index'))
            ->line('Wenn du Fragen hast, antworte einfach auf diese E-Mail.')
            ->salutation('Viele Grüße, Dein RatingsHub Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Abonnement gekündigt',
            'message' => 'Dein Abonnement wurde gekündigt. Reaktiviere es, um wieder Zugriff zu erhalten.',
            'action_url' => route('subscription.index'),
            'action_text' => 'Abonnement reaktivieren',
        ];
    }
}
