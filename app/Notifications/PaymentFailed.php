<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * PAYMENT FAILED NOTIFICATION
 * ============================
 *
 * Wird gesendet wenn Stripe-Zahlung fehlschlägt
 *
 * Gründe:
 * - Kreditkarte abgelehnt
 * - Kein Guthaben
 * - Karte abgelaufen
 * - etc.
 */
class PaymentFailed extends Notification implements ShouldQueue
{
    use Queueable;

    protected array $invoiceData;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $invoiceData)
    {
        $this->invoiceData = $invoiceData;
    }

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
        $amount = ($this->invoiceData['amount_due'] ?? 0) / 100; // Cents → Euro
        $attemptCount = $this->invoiceData['attempt_count'] ?? 1;

        return (new MailMessage)
            ->subject('⚠️ Zahlung fehlgeschlagen')
            ->greeting('Hallo ' . $notifiable->name . ',')
            ->error()
            ->line('Die Zahlung für dein Abonnement konnte nicht durchgeführt werden.')
            ->line('**Details:**')
            ->line('Betrag: ' . number_format($amount, 2, ',', '.') . ' €')
            ->line('Versuch: ' . $attemptCount)
            ->line('**Was passiert jetzt?**')
            ->line('- Du hast noch **3 Tage Grace Period** mit vollem Zugriff')
            ->line('- Bitte aktualisiere deine Zahlungsmethode')
            ->line('- Nach 3 Tagen ohne Zahlung wird dein Abo deaktiviert')
            ->action('Zahlungsmethode aktualisieren', route('subscription.manage'))
            ->line('Falls du Hilfe benötigst, kontaktiere uns.')
            ->salutation('Viele Grüße, Dein RatingsHub Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $amount = ($this->invoiceData['amount_due'] ?? 0) / 100;

        return [
            'title' => 'Zahlung fehlgeschlagen',
            'message' => 'Die Zahlung über ' . number_format($amount, 2, ',', '.') . ' € konnte nicht durchgeführt werden. Bitte aktualisiere deine Zahlungsmethode.',
            'amount' => $amount,
            'action_url' => route('subscription.manage'),
            'action_text' => 'Zahlungsmethode aktualisieren',
        ];
    }
}
