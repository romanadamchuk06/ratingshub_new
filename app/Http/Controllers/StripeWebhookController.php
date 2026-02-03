<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\User;
use App\Models\SubscriptionActivityLog;
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
     * Handle customer subscription created
     *
     * WICHTIG für Stripe Pricing Table!
     * Die Pricing Table sendet kein metadata, daher müssen wir
     * den Plan anhand der Stripe Price ID ermitteln.
     *
     * Flow:
     * 1. Kunde wählt Plan in Pricing Table
     * 2. Stripe erstellt Subscription mit Price ID
     * 3. Dieser Webhook setzt plan_id basierend auf stripe_plan_id
     */
    public function handleCustomerSubscriptionCreated(array $payload)
    {
        $data = $payload['data']['object'];

        \Log::info('Stripe Webhook: Subscription Created', [
            'subscription_id' => $data['id'],
            'customer_id' => $data['customer'],
            'status' => $data['status'],
            'items' => $data['items']['data'] ?? [],
        ]);

        $user = $this->getUserByStripeId($data['customer']);

        if (!$user) {
            \Log::warning('User nicht gefunden für Stripe Customer', [
                'customer_id' => $data['customer'],
            ]);
            return response('OK', 200);
        }

        // Stripe Price ID aus Subscription Items holen
        $stripePriceId = $data['items']['data'][0]['price']['id'] ?? null;

        if (!$stripePriceId) {
            \Log::error('Keine Price ID in Subscription gefunden', [
                'subscription_id' => $data['id'],
            ]);
            return response('OK', 200);
        }

        // Plan anhand Stripe Price ID finden
        $plan = Plan::findByStripePrice($stripePriceId);

        if (!$plan) {
            \Log::error('Kein Plan für Stripe Price ID gefunden', [
                'stripe_price_id' => $stripePriceId,
                'subscription_id' => $data['id'],
            ]);
            return response('OK', 200);
        }

        // Plan dem User zuweisen
        $oldPlan = $user->plan;
        $user->plan_id = $plan->id;
        $user->ends_grace_period_at = null; // Grace Period aufheben
        $user->save();

        // Activity Log
        SubscriptionActivityLog::log(
            performedBy: $user,
            targetUser: $user,
            plan: $plan,
            action: 'subscribed',
            changes: [
                'from_plan' => $oldPlan?->name,
                'to_plan' => $plan->name,
                'stripe_price_id' => $stripePriceId,
                'source' => 'pricing_table',
            ],
            stripeSubscriptionId: $data['id'] ?? null,
            description: "Plan '{$plan->name}' via Stripe Pricing Table abonniert"
        );

        \Log::info('Plan erfolgreich zugewiesen via Pricing Table', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'stripe_price_id' => $stripePriceId,
        ]);

        // Standard Cashier Handling
        return parent::handleCustomerSubscriptionCreated($payload);
    }

    /**
     * Handle customer subscription updated
     *
     * Wird aufgerufen wenn:
     * - Plan gewechselt wird (wichtig!)
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

        $user = $this->getUserByStripeId($data['customer']);

        if (!$user) {
            return parent::handleCustomerSubscriptionUpdated($payload);
        }

        // Plan-Wechsel erkennen und aktualisieren
        $stripePriceId = $data['items']['data'][0]['price']['id'] ?? null;
        if ($stripePriceId) {
            $newPlan = Plan::findByStripePrice($stripePriceId);
            if ($newPlan && $user->plan_id !== $newPlan->id) {
                $oldPlan = $user->plan;
                $user->plan_id = $newPlan->id;

                SubscriptionActivityLog::log(
                    performedBy: $user,
                    targetUser: $user,
                    plan: $newPlan,
                    action: 'plan_changed',
                    changes: [
                        'from_plan' => $oldPlan?->name,
                        'to_plan' => $newPlan->name,
                        'stripe_price_id' => $stripePriceId,
                    ],
                    stripeSubscriptionId: $data['id'] ?? null,
                    description: "Plan gewechselt: {$oldPlan?->name} → {$newPlan->name}"
                );

                \Log::info('Plan gewechselt', [
                    'user_id' => $user->id,
                    'old_plan' => $oldPlan?->name,
                    'new_plan' => $newPlan->name,
                ]);
            }
        }

        // Wenn Subscription aktiv ist, Grace Period aufheben
        if ($data['status'] === 'active') {
            $user->ends_grace_period_at = null;
            \Log::info('Grace Period aufgehoben für User', [
                'user_id' => $user->id,
            ]);
        }

        // Wenn Status "past_due" (Zahlung überfällig), Grace Period setzen
        if ($data['status'] === 'past_due' && !$user->ends_grace_period_at) {
            $user->ends_grace_period_at = now()->addDays(3);
            \Log::warning('Grace Period gestartet für User', [
                'user_id' => $user->id,
                'ends_at' => $user->ends_grace_period_at,
            ]);
        }

        $user->save();

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
     *
     * WICHTIG: User wird auf Free Plan zurückgesetzt!
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
            $oldPlan = $user->plan;

            // Grace Period zurücksetzen
            $user->ends_grace_period_at = null;

            // WICHTIG: Plan auf NULL setzen = kein Zugriff mehr
            // User muss neues Abo abschließen um Features zu nutzen
            $user->plan_id = null;

            // Activity Log
            SubscriptionActivityLog::log(
                performedBy: $user,
                targetUser: $user,
                plan: $oldPlan,
                action: 'subscription_ended',
                changes: [
                    'from_plan' => $oldPlan?->name,
                    'to_plan' => null,
                    'reason' => 'subscription_deleted',
                ],
                stripeSubscriptionId: $data['id'] ?? null,
                description: "Abo beendet - Zugriff gesperrt (war: {$oldPlan?->name})"
            );

            \Log::info('User Abo beendet - kein Zugriff mehr', [
                'user_id' => $user->id,
                'old_plan' => $oldPlan?->name,
            ]);

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
            'invoice_id' => $data['id'] ?? null,
            'customer_id' => $data['customer'] ?? null,
            'amount' => ($data['amount_due'] ?? 0) / 100, // Cents → Euro
            'attempt_count' => $data['attempt_count'] ?? 0,
        ]);

        $user = $this->getUserByStripeId($data['customer'] ?? null);

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
                'amount' => ($data['amount_due'] ?? 0) / 100,
            ]);
        }

        return $this->successMethod();
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
            'amount' => ($data['amount_paid'] ?? 0) / 100,
        ]);

        $user = $this->getUserByStripeId($data['customer'] ?? null);

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

        return $this->successMethod();
    }

    /**
     * Handle checkout.session.completed
     *
     * WICHTIG: Dieser Handler wird aufgerufen wenn ein User erfolgreich
     * durch Stripe Checkout bezahlt hat.
     *
     * Flow:
     * 1. User kommt von Checkout-Seite
     * 2. Stripe Checkout Session erstellt
     * 3. User zahlt auf Stripe-Seite
     * 4. Stripe sendet checkout.session.completed Webhook
     * 5. Wir erstellen die Subscription in unserer DB
     */
    public function handleCheckoutSessionCompleted(array $payload)
    {
        $session = $payload['data']['object'];

        \Log::info('Stripe Webhook: Checkout Session Completed', [
            'session_id' => $session['id'],
            'customer_id' => $session['customer'],
            'subscription_id' => $session['subscription'] ?? null,
            'metadata' => $session['metadata'] ?? [],
        ]);

        // Metadata aus der Session holen
        $metadata = $session['metadata'] ?? [];
        $userId = $metadata['user_id'] ?? null;
        $planId = $metadata['plan_id'] ?? null;

        if (!$userId || !$planId) {
            \Log::warning('Checkout Session ohne User/Plan Metadata', [
                'session_id' => $session['id'],
            ]);
            return response('OK', 200);
        }

        $user = User::find($userId);
        $plan = Plan::find($planId);

        if (!$user || !$plan) {
            \Log::error('User oder Plan nicht gefunden', [
                'user_id' => $userId,
                'plan_id' => $planId,
            ]);
            return response('OK', 200);
        }

        try {
            // Plan-ID auf User setzen
            $user->update(['plan_id' => $plan->id]);

            // Subscription Activity Log
            // (Promo Codes werden jetzt direkt in Stripe verwaltet)
            SubscriptionActivityLog::log(
                performedBy: $user,
                targetUser: $user,
                plan: $plan,
                action: 'subscribed',
                changes: [
                    'plan' => $plan->name,
                    'type' => 'stripe_checkout',
                    'checkout_session_id' => $session['id'],
                ],
                stripeSubscriptionId: $session['subscription'] ?? null,
                description: "Plan '{$plan->name}' via Stripe Checkout abonniert"
            );

            \Log::info('Subscription erfolgreich erstellt via Checkout', [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'subscription_id' => $session['subscription'] ?? null,
            ]);

        } catch (\Exception $e) {
            \Log::error('Fehler bei Checkout Session Verarbeitung', [
                'error' => $e->getMessage(),
                'session_id' => $session['id'],
            ]);
        }

        return response('OK', 200);
    }

    /**
     * Hilfsmethode: User anhand Stripe Customer ID finden
     */
    protected function getUserByStripeId($stripeId)
    {
        return User::where('stripe_id', $stripeId)->first();
    }
}
