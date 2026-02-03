<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\PromoCode;
use App\Models\SubscriptionActivityLog;
use App\Models\PromoCodeActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeCheckoutSession;

class SubscriptionController extends Controller
{
    /**
     * Display Stripe Pricing Table.
     *
     * Verwendet Stripe Pricing Table für die Plan-Auswahl.
     * Stripe handhabt automatisch monatlich/jährlich Toggle und Checkout.
     *
     * SETUP:
     * 1. Stripe Dashboard → Produkte → Pricing Tables → Neue erstellen
     * 2. STRIPE_PRICING_TABLE_ID in .env eintragen
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
     * Show checkout page for a plan.
     *
     * NEUER FLOW MIT STRIPE CHECKOUT:
     * 1. Zeigt Plan-Zusammenfassung und Promo-Code Eingabe
     * 2. User klickt "Weiter zur Zahlung"
     * 3. Redirect zu Stripe Checkout Session
     * 4. Stripe wickelt Zahlung ab (Karte, Apple Pay, Google Pay, SEPA, etc.)
     * 5. Redirect zurück zur Success-Seite
     *
     * Lädt auch den Schwester-Plan (gleicher Name, anderes Intervall)
     * damit User im Checkout zwischen monatlich/jährlich wechseln können.
     */
    public function checkout(Plan $plan)
    {
        $user = auth()->user();

        // Check if user already has this plan
        if ($user->plan_id === $plan->id) {
            return redirect()->route('subscription.index')
                ->with('error', 'Du hast bereits diesen Plan.');
        }

        // Free plan - just update
        if ($plan->isFree()) {
            $user->update(['plan_id' => $plan->id]);
            return redirect()->route('subscription.index')
                ->with('success', 'Du nutzt jetzt den Free Plan.');
        }

        // Finde den Schwester-Plan (gleicher Name, anderes Intervall)
        $siblingInterval = $plan->billing_interval === 'monthly' ? 'yearly' : 'monthly';
        $siblingPlan = Plan::where('name', $plan->name)
            ->where('billing_interval', $siblingInterval)
            ->where('is_active', true)
            ->first();

        return Inertia::render('Subscription/Checkout', [
            'plan' => $plan,
            'siblingPlan' => $siblingPlan, // Kann null sein wenn kein Schwester-Plan existiert
            'stripeKey' => config('cashier.key'), // Für eventuelle Client-Side Nutzung
        ]);
    }

    /**
     * Validate promo code.
     */
    public function validatePromoCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'plan_id' => 'required|exists:plans,id',
        ]);

        $promoCode = PromoCode::where('code', strtoupper($request->code))->first();
        $plan = Plan::findOrFail($request->plan_id);

        if (!$promoCode) {
            return response()->json([
                'valid' => false,
                'message' => 'Ungültiger Promo Code',
            ], 422);
        }

        $user = auth()->user();

        // Detailed validation checks for better error messages (skip for admins)
        if (!$user->is_admin) {
            if (!$promoCode->is_active) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Dieser Promo Code ist nicht aktiv',
                ], 422);
            }

            if ($promoCode->expires_at && $promoCode->expires_at->isPast()) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Dieser Promo Code ist abgelaufen',
                ], 422);
            }

            if ($promoCode->max_uses && $promoCode->used_count >= $promoCode->max_uses) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Dieser Promo Code hat sein Nutzungslimit erreicht',
                ], 422);
            }

            if ($promoCode->usages()->where('user_id', $user->id)->exists()) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Du hast diesen Promo Code bereits verwendet',
                ], 422);
            }
        } else {
            // Admins können nur inaktive Codes nicht nutzen
            if (!$promoCode->is_active) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Dieser Promo Code ist nicht aktiv',
                ], 422);
            }
        }

        $originalPrice = $plan->price;
        $discount = $promoCode->calculateDiscount($originalPrice);
        $finalPrice = $promoCode->applyDiscount($originalPrice);

        return response()->json([
            'valid' => true,
            'promo_code' => $promoCode,
            'original_price' => $originalPrice,
            'discount' => $discount,
            'final_price' => $finalPrice,
            'message' => 'Promo Code angewendet!',
        ]);
    }

    /**
     * Process subscription via Stripe Checkout Session.
     *
     * NEUER FLOW MIT STRIPE CHECKOUT:
     * --------------------------------
     * 1. User kommt von Checkout-Seite mit Plan-ID und optionalem Promo-Code
     * 2. Wir erstellen eine Stripe Checkout Session
     * 3. User wird zu Stripe weitergeleitet
     * 4. Nach Zahlung: Redirect zur Success-Seite
     * 5. Webhook (checkout.session.completed) erstellt die Subscription
     *
     * VORTEILE:
     * - Stripe hosted Checkout (PCI compliant)
     * - Alle Zahlungsmethoden: Karte, Apple Pay, Google Pay, SEPA, etc.
     * - Payment Links funktionieren
     * - Weniger eigener Code
     *
     * KOSTENLOSE PLÄNE (finalPrice = 0):
     * - Werden direkt aktiviert ohne Stripe
     */
    public function subscribe(Request $request, Plan $plan)
    {
        $user = auth()->user();

        // Schritt 1: Preis berechnen (mit Promo Code falls vorhanden)
        // NUR für 100% Rabatt-Codes (kostenlose Aktivierung)
        $finalPrice = $plan->price;
        $promoCode = null;

        if ($request->promo_code) {
            $promoCode = PromoCode::where('code', strtoupper($request->promo_code))->first();
            if ($promoCode && $promoCode->canBeUsedBy($user)) {
                $finalPrice = $promoCode->applyDiscount($plan->price);
            }
        }

        try {
            // Alte Cashier subscription löschen falls vorhanden
            if ($user->subscribed('default')) {
                $user->subscription('default')->cancelNow();
            }

            // ========================================
            // PFAD 1: KOSTENLOSER PLAN (finalPrice = 0)
            // ========================================
            // Gilt für: Free-Plan ODER 100% Rabatt-Code
            if ($finalPrice == 0) {
                // Nur plan_id setzen, KEINE Stripe Session nötig
                $user->update(['plan_id' => $plan->id]);

                // Promo Code als verwendet markieren
                if ($promoCode) {
                    $promoCode->markAsUsed($user);

                    PromoCodeActivityLog::log(
                        performedBy: $user,
                        promoCode: $promoCode,
                        action: 'used',
                        usedBy: $user,
                        changes: [
                            'plan' => $plan->name,
                            'discount' => $promoCode->value . ($promoCode->type === 'percentage' ? '%' : '€'),
                            'final_price' => $finalPrice,
                        ],
                        description: "Promo-Code '{$promoCode->code}' verwendet für Plan '{$plan->name}'"
                    );
                }

                SubscriptionActivityLog::log(
                    performedBy: $user,
                    targetUser: $user,
                    plan: $plan,
                    action: 'subscribed',
                    changes: [
                        'plan' => $plan->name,
                        'price' => $finalPrice,
                        'promo_code' => $promoCode?->code,
                        'type' => 'free',
                    ],
                    description: "Kostenloser Plan '{$plan->name}' aktiviert"
                );

                return redirect()->route('subscription.success')
                    ->with('success', 'Plan erfolgreich aktiviert!');
            }

            // ========================================
            // PFAD 2: STRIPE CHECKOUT SESSION
            // ========================================
            // Für alle bezahlten Pläne - Stripe übernimmt alles

            // Stripe API initialisieren
            Stripe::setApiKey(config('cashier.secret'));

            // Stripe Customer erstellen/abrufen
            if (!$user->stripe_id) {
                $user->createAsStripeCustomer();
            }

            // Checkout Session erstellen
            $checkoutSession = StripeCheckoutSession::create([
                'customer' => $user->stripe_id,
                'mode' => 'subscription',
                'line_items' => [[
                    'price' => $plan->stripe_plan_id,
                    'quantity' => 1,
                ]],
                'success_url' => route('subscription.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('subscription.checkout', $plan),
                'metadata' => [
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                ],
                // Promo Codes direkt auf Stripe erlauben
                // User kann Stripe Promotion Codes im Checkout eingeben
                'allow_promotion_codes' => true,
                // Rechnungsadresse abfragen
                'billing_address_collection' => 'auto',
                // Kundeninfos aktualisieren
                'customer_update' => [
                    'address' => 'auto',
                    'name' => 'auto',
                ],
            ]);

            // Redirect zu Stripe Checkout
            return redirect($checkoutSession->url);

        } catch (\Exception $e) {
            \Log::error('Stripe Checkout Session error: ' . $e->getMessage());
            return back()->with('error', 'Fehler beim Erstellen der Checkout-Session: ' . $e->getMessage());
        }
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
     * WICHTIG: Diese Seite funktioniert für BEIDE Subscription-Typen:
     * - Kostenlose Pläne: subscription = null, nur currentPlan vorhanden
     * - Bezahlte Pläne: subscription + currentPlan vorhanden
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
            // Cashier subscription (null wenn kostenloser Plan)
            'subscription' => $user->subscription('default'),

            // Plan-Details (immer vorhanden über plan_id)
            'currentPlan' => $user->plan,

            // Rechnungen (nur bei Cashier subscriptions)
            'invoices' => $user->invoices(),

            // Zahlungsmethode
            'paymentMethod' => $paymentMethod,

            // Plattform-Nutzung
            'platformsConnected' => $user->connectedPlatforms()->count(),
            'maxPlatforms' => $user->plan->max_platforms ?? 1,

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

        if ($user->subscription('default')->cancelled()) {
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
     * Update payment method.
     */
    public function updatePaymentMethod(Request $request)
    {
        $request->validate([
            'payment_method' => 'required|string',
        ]);

        $user = auth()->user();

        try {
            $user->updateDefaultPaymentMethod($request->payment_method);

            // LOG: Zahlungsmethode geändert
            SubscriptionActivityLog::log(
                performedBy: $user,
                targetUser: $user,
                plan: $user->plan,
                action: 'payment_method_updated',
                changes: [
                    'plan' => $user->plan?->name,
                ],
                description: "Zahlungsmethode aktualisiert"
            );

            return back()->with('success', 'Zahlungsmethode aktualisiert!');
        } catch (\Exception $e) {
            return back()->with('error', 'Fehler: ' . $e->getMessage());
        }
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
