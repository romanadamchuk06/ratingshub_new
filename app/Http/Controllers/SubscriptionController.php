<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\SubscriptionActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeCheckoutSession;

/**
 * SUBSCRIPTION CONTROLLER (Vereinfacht für Stripe Pricing Table)
 * ==============================================================
 *
 * Verwaltet Subscriptions über Stripe Pricing Table.
 *
 * FLOW:
 * 1. User sieht Pricing Table auf /subscription
 * 2. Wählt Plan (monatlich/jährlich) direkt in Stripe
 * 3. Bezahlt über Stripe Checkout
 * 4. Webhook setzt plan_id
 *
 * PROMO CODES:
 * - Werden direkt in Stripe verwaltet
 * - User gibt Code im Stripe Checkout ein
 * - Kein eigenes Promo-System mehr nötig
 */
class SubscriptionController extends Controller
{
    /**
     * Display Stripe Pricing Table.
     *
     * Verwendet Stripe Pricing Table für die Plan-Auswahl.
     * Stripe handhabt automatisch monatlich/jährlich Toggle und Checkout.
     */
    public function index()
    {
        $user = auth()->user();

        // Customer Session für eingeloggte User erstellen
        // Damit Stripe den User erkennt und bestehende Subscriptions anzeigt
        $customerSessionClientSecret = null;
        if ($user->stripe_id) {
            try {
                Stripe::setApiKey(config('cashier.secret'));
                $customerSession = \Stripe\CustomerSession::create([
                    'customer' => $user->stripe_id,
                    'components' => [
                        'pricing_table' => ['enabled' => true],
                    ],
                ]);
                $customerSessionClientSecret = $customerSession->client_secret;
            } catch (\Exception $e) {
                \Log::warning('Could not create customer session: ' . $e->getMessage());
            }
        }

        return Inertia::render('Subscription/Pricing', [
            'pricingTableId' => config('services.stripe.pricing_table_id'),
            'pricingTableIdDark' => config('services.stripe.pricing_table_id_dark'),
            'publishableKey' => config('cashier.key'),
            'currentPlan' => $user->plan,
            'customerSessionClientSecret' => $customerSessionClientSecret,
        ]);
    }

    /**
     * Show subscription success page.
     */
    public function success()
    {
        return Inertia::render('Subscription/Success');
    }

    /**
     * Show subscription management page.
     *
     * Zeigt aktuelle Subscription und Rechnungen.
     * User kann zum Stripe Billing Portal weitergeleitet werden.
     */
    public function manage()
    {
        $user = auth()->user();

        // Zahlungsmethode abrufen (falls vorhanden)
        $paymentMethod = null;
        if ($user->hasDefaultPaymentMethod()) {
            $pm = $user->defaultPaymentMethod();
            $paymentMethod = [
                'brand' => $pm->card->brand ?? 'card',
                'last4' => $pm->card->last4 ?? '****',
                'exp_month' => $pm->card->exp_month ?? null,
                'exp_year' => $pm->card->exp_year ?? null,
            ];
        }

        return Inertia::render('Subscription/Manage', [
            // Cashier subscription (null wenn kein aktives Abo)
            'subscription' => $user->subscription('default'),

            // Plan-Details (aus plan_id)
            'currentPlan' => $user->plan,

            // Rechnungen (nur bei Cashier subscriptions)
            'invoices' => $user->invoices(),

            // Zahlungsmethode
            'paymentMethod' => $paymentMethod,

            // Plattform-Nutzung
            'platformsConnected' => $user->connectedPlatforms()->count(),
            'maxPlatforms' => $user->plan?->max_platforms ?? 1,

            // Trial-Info
            'onTrial' => $user->onTrial(),
            'trialEndsAt' => $user->trial_ends_at,
        ]);
    }

    /**
     * Redirect to Stripe Billing Portal.
     *
     * Das Stripe Billing Portal ermöglicht:
     * - Zahlungsmethode ändern
     * - Rechnungen einsehen
     * - Subscription kündigen/ändern
     * - Promo Codes einlösen
     */
    public function billingPortal()
    {
        $user = auth()->user();

        if (!$user->stripe_id) {
            return back()->with('error', 'Kein Stripe-Konto vorhanden.');
        }

        return $user->redirectToBillingPortal(route('subscription.manage'));
    }

    /**
     * Cancel subscription.
     */
    public function cancel()
    {
        $user = auth()->user();

        if ($user->subscribed('default')) {
            $subscription = $user->subscription('default');
            $subscription->cancel();

            // LOG: Subscription gekündigt
            SubscriptionActivityLog::log(
                performedBy: $user,
                targetUser: $user,
                plan: $user->plan,
                action: 'cancelled',
                changes: [
                    'plan' => $user->plan?->name,
                    'ends_at' => $subscription->ends_at?->format('Y-m-d H:i:s'),
                ],
                stripeSubscriptionId: $subscription->stripe_id ?? null,
                description: "Subscription gekündigt (läuft noch bis {$subscription->ends_at?->format('d.m.Y')})"
            );

            return back()->with('success', 'Subscription gekündigt. Du kannst bis zum Ende der Laufzeit weiter nutzen.');
        }

        return back()->with('error', 'Keine aktive Subscription gefunden.');
    }

    /**
     * Resume cancelled subscription.
     */
    public function resume()
    {
        $user = auth()->user();

        if ($user->subscription('default')?->cancelled()) {
            $subscription = $user->subscription('default');
            $subscription->resume();

            // LOG: Subscription wieder aktiviert
            SubscriptionActivityLog::log(
                performedBy: $user,
                targetUser: $user,
                plan: $user->plan,
                action: 'resumed',
                changes: [
                    'plan' => $user->plan?->name,
                ],
                stripeSubscriptionId: $subscription->stripe_id ?? null,
                description: "Subscription wieder aktiviert"
            );

            return back()->with('success', 'Subscription wieder aktiviert!');
        }

        return back()->with('error', 'Keine gekündigte Subscription gefunden.');
    }

    /**
     * Download invoice.
     */
    public function invoice($invoiceId)
    {
        return auth()->user()->downloadInvoice($invoiceId, [
            'vendor' => config('app.name'),
            'product' => 'Subscription',
        ]);
    }
}
