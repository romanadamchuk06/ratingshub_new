<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\SubscriptionCancelled;
use App\Notifications\PaymentFailed;
use Illuminate\Http\Request;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierController;

/**
 * STRIPE WEBHOOK CONTROLLER
 * ==========================
 *
 * Erweitert den Standard Cashier WebhookController um eigene Logik.
 *
 * WICHTIGE STRIPE EVENTS:
 * 1. customer.subscription.updated - Abo geändert (Plan-Wechsel, etc.)
 * 2. customer.subscription.deleted - Abo gekündigt/abgelaufen
 * 3. invoice.payment_failed - Zahlung fehlgeschlagen
 * 4. invoice.payment_succeeded - Zahlung erfolgreich
 *
 * FLOW BEI ZAHLUNGSAUSFALL:
 * 1. invoice.payment_failed → Grace Period starten (3 Tage)
 * 2. Nach 3 Tagen ohne Zahlung → customer.subscription.deleted
 * 3. User verliert Zugriff auf Features
 */
class StripeWebhookController extends CashierController
{
    /**
     * Handle customer subscription updated
     *
     * Wird aufgerufen wenn:
     * - Plan gewechselt wird
     * - Abo reaktiviert wird
     * - Status sich ändert (active, past_due, canceled, etc.)
     */
    public function handleCustomerSubscriptionUpdated(array $payload)
    {
        $data = $payload['data']['object'];

        \Log::info('Stripe Webhook: Subscription Updated', [
            'subscription_id' => $data['id'],
            'customer_id' => $data['customer'],
            'status' => $data['status'],
        ]);

        // Wenn Subscription aktiv ist, Grace Period aufheben
        if ($data['status'] === 'active') {
            $user = $this->getUserByStripeId($data['customer']);
            if ($user) {
                $user->ends_grace_period_at = null;
                $user->save();

                \Log::info('Grace Period aufgehoben für User', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);
            }
        }

        // Wenn Status "past_due" (Zahlung überfällig), Grace Period setzen
        if ($data['status'] === 'past_due') {
            $user = $this->getUserByStripeId($data['customer']);
            if ($user && !$user->ends_grace_period_at) {
                // Grace Period: 3 Tage ab jetzt
                $user->ends_grace_period_at = now()->addDays(3);
                $user->save();

                \Log::warning('Grace Period gestartet für User', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ends_at' => $user->ends_grace_period_at,
                ]);
            }
        }

        // Standard Cashier Handling
        return parent::handleCustomerSubscriptionUpdated($payload);
    }

    /**
     * Handle customer subscription deleted
     *
     * Wird aufgerufen wenn:
     * - Abo vom User gekündigt wird
     * - Abo wegen Zahlungsausfall endet
     * - Grace Period abgelaufen ist
     */
    public function handleCustomerSubscriptionDeleted(array $payload)
    {
        $data = $payload['data']['object'];

        \Log::warning('Stripe Webhook: Subscription Deleted', [
            'subscription_id' => $data['id'],
            'customer_id' => $data['customer'],
            'ended_at' => $data['ended_at'] ?? 'now',
        ]);

        $user = $this->getUserByStripeId($data['customer']);

        if ($user) {
            // Grace Period zurücksetzen
            $user->ends_grace_period_at = null;
            $user->save();

            // Benachrichtigung senden
            try {
                $user->notify(new SubscriptionCancelled());
            } catch (\Exception $e) {
                \Log::error('Fehler beim Senden der Kündigungs-Mail', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            \Log::info('Subscription gelöscht für User', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
        }

        // Standard Cashier Handling
        return parent::handleCustomerSubscriptionDeleted($payload);
    }

    /**
     * Handle invoice payment failed
     *
     * Wird aufgerufen wenn:
     * - Kreditkarte abgelehnt wird
     * - Guthaben nicht ausreicht
     * - Zahlung aus anderen Gründen fehlschlägt
     */
    public function handleInvoicePaymentFailed(array $payload)
    {
        $data = $payload['data']['object'];

        \Log::error('Stripe Webhook: Payment Failed', [
            'invoice_id' => $data['id'],
            'customer_id' => $data['customer'],
            'amount' => $data['amount_due'] / 100, // Cents → Euro
            'attempt_count' => $data['attempt_count'],
        ]);

        $user = $this->getUserByStripeId($data['customer']);

        if ($user) {
            // Benachrichtigung an User
            try {
                $user->notify(new PaymentFailed($data));
            } catch (\Exception $e) {
                \Log::error('Fehler beim Senden der Payment-Failed-Mail', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            \Log::warning('Payment Failed - Benachrichtigung gesendet', [
                'user_id' => $user->id,
                'email' => $user->email,
                'amount' => $data['amount_due'] / 100,
            ]);
        }

        // Standard Cashier Handling
        return parent::handleInvoicePaymentFailed($payload);
    }

    /**
     * Handle invoice payment succeeded
     *
     * Wird aufgerufen wenn Zahlung erfolgreich war
     */
    public function handleInvoicePaymentSucceeded(array $payload)
    {
        $data = $payload['data']['object'];

        \Log::info('Stripe Webhook: Payment Succeeded', [
            'invoice_id' => $data['id'],
            'customer_id' => $data['customer'],
            'amount' => $data['amount_paid'] / 100,
        ]);

        $user = $this->getUserByStripeId($data['customer']);

        if ($user) {
            // Grace Period aufheben (falls aktiv)
            if ($user->ends_grace_period_at) {
                $user->ends_grace_period_at = null;
                $user->save();

                \Log::info('Grace Period aufgehoben nach erfolgreicher Zahlung', [
                    'user_id' => $user->id,
                ]);
            }
        }

        // Standard Cashier Handling
        return parent::handleInvoicePaymentSucceeded($payload);
    }

    /**
     * Hilfsmethode: User anhand Stripe Customer ID finden
     */
    protected function getUserByStripeId(string $stripeId): ?User
    {
        return User::where('stripe_id', $stripeId)->first();
    }
}
