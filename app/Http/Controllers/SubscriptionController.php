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

        // WICHTIG: Stripe Customer erstellen falls noch nicht vorhanden
        // Damit der Webhook den User später finden kann (über stripe_id)
        if (!$user->stripe_id) {
            try {
                $user->createAsStripeCustomer();
                \Log::info('Stripe Customer erstellt für User', ['user_id' => $user->id, 'stripe_id' => $user->stripe_id]);
            } catch (\Exception $e) {
                \Log::error('Stripe Customer konnte nicht erstellt werden: ' . $e->getMessage());
            }
        }

        // Customer Session für Pricing Table erstellen
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

        // Zahlungsmethode abrufen (nur wenn stripe_id vorhanden)
        $paymentMethod = null;
        $subscription = null;
        $invoices = [];

        if ($user->stripe_id) {
            try {
                // Subscription abrufen
                $subscription = $user->subscription('default');

                // Rechnungen abrufen
                $invoices = $user->invoices();

                // Zahlungsmethode abrufen
                if ($user->hasDefaultPaymentMethod()) {
                    $pm = $user->defaultPaymentMethod();
                    $paymentMethod = [
                        'brand' => $pm->card->brand ?? 'card',
                        'last4' => $pm->card->last4 ?? '****',
                        'exp_month' => $pm->card->exp_month ?? null,
                        'exp_year' => $pm->card->exp_year ?? null,
                    ];
                }
            } catch (\Exception $e) {
                \Log::warning('Error fetching Stripe data for user: ' . $e->getMessage());
            }
        }

        return Inertia::render('Subscription/Manage', [
            // Cashier subscription (null wenn kein aktives Abo)
            'subscription' => $subscription,

            // Plan-Details (aus plan_id)
            'currentPlan' => $user->plan,

            // Rechnungen (nur bei Cashier subscriptions)
            'invoices' => $invoices,

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
            return redirect()->route('subscription.index')
                ->with('error', 'Kein Stripe-Konto vorhanden. Bitte wähle zuerst einen Plan.');
        }

        // Billing Portal URL generieren (mit Rückkehr zum Dashboard)
        $billingPortalUrl = $user->billingPortalUrl(route('dashboard'));

        // Inertia::location() für externe Redirects
        return \Inertia\Inertia::location($billingPortalUrl);
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

    /**
     * Direct Checkout für einen spezifischen Plan.
     *
     * Erstellt eine Stripe Checkout Session und leitet direkt zu Stripe weiter.
     * Wird verwendet wenn User von der Landing Page einen Plan wählt.
     *
     * @param string $planSlug Plan-Slug (z.B. 'professional')
     * @param string $interval 'monthly' oder 'yearly' (default: monthly)
     */
    public function checkout(string $planSlug, Request $request)
    {
        $user = auth()->user();
        $interval = $request->get('interval', 'monthly');

        // Plan finden
        $plan = Plan::where('slug', $planSlug)
            ->where('is_active', true)
            ->first();

        if (!$plan) {
            return redirect()->route('subscription.index')
                ->with('error', 'Plan nicht gefunden.');
        }

        // Kostenloser Plan: direkt zuweisen
        if ($plan->isFree()) {
            $user->update(['plan_id' => $plan->id]);
            return redirect()->route('dashboard')
                ->with('success', "Du nutzt jetzt den {$plan->name} Plan!");
        }

        // Stripe Price ID basierend auf Interval
        $stripePriceId = $interval === 'yearly'
            ? $plan->stripe_price_id_yearly
            : $plan->stripe_price_id_monthly;

        if (!$stripePriceId) {
            return redirect()->route('subscription.index')
                ->with('error', 'Dieser Plan ist für das gewählte Intervall nicht verfügbar.');
        }

        // Stripe Customer erstellen falls noch nicht vorhanden
        if (!$user->stripe_id) {
            try {
                $user->createAsStripeCustomer();
            } catch (\Exception $e) {
                \Log::error('Stripe Customer konnte nicht erstellt werden: ' . $e->getMessage());
                return redirect()->route('subscription.index')
                    ->with('error', 'Fehler bei der Zahlungsvorbereitung. Bitte versuche es erneut.');
            }
        }

        // Stripe Checkout Session erstellen
        try {
            Stripe::setApiKey(config('cashier.secret'));

            $checkoutSession = StripeCheckoutSession::create([
                'customer' => $user->stripe_id,
                'mode' => 'subscription',
                'line_items' => [[
                    'price' => $stripePriceId,
                    'quantity' => 1,
                ]],
                'success_url' => route('subscription.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('subscription.index'),
                'allow_promotion_codes' => true,
                'billing_address_collection' => 'required',
                'metadata' => [
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                ],
            ]);

            // Inertia::location() für externe Redirects (Stripe)
            return \Inertia\Inertia::location($checkoutSession->url);
        } catch (\Exception $e) {
            \Log::error('Stripe Checkout Session konnte nicht erstellt werden: ' . $e->getMessage());
            return redirect()->route('subscription.index')
                ->with('error', 'Fehler beim Erstellen der Checkout-Session. Bitte versuche es erneut.');
        }
    }
}
