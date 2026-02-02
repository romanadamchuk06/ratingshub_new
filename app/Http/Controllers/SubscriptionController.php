<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\PromoCode;
use App\Models\SubscriptionActivityLog;
use App\Models\PromoCodeActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    /**
     * Display pricing plans.
     */
    public function index()
    {
        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Subscription/Pricing', [
            'plans' => $plans,
            'currentPlan' => auth()->user()->plan,
        ]);
    }

    /**
     * Show checkout page for a plan.
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
            'intent' => $user->createSetupIntent(),
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
     * Process subscription.
     *
     * HYBRID-SYSTEM ERKLÄRUNG:
     * ------------------------
     * Diese App nutzt zwei verschiedene Systeme für Subscriptions:
     *
     * 1. KOSTENLOSE PLÄNE (finalPrice = 0):
     *    - Wird nur plan_id auf User gesetzt
     *    - KEINE Cashier subscription in DB
     *    - KEINE Stripe-Billing, keine Rechnungen
     *    - Beispiel: Free-Plan, 100% Promo Code
     *
     * 2. BEZAHLTE PLÄNE (finalPrice > 0):
     *    - plan_id UND Cashier subscription werden gesetzt
     *    - Stripe-Billing aktiv
     *    - Rechnungen, Cancel/Resume funktioniert
     *
     * Warum zwei Systeme?
     * - plan_id ist einfach und schnell für kostenlose Nutzung
     * - Cashier subscription ist notwendig für automatische Zahlungen
     */
    public function subscribe(Request $request, Plan $plan)
    {
        $user = auth()->user();

        // Schritt 1: Preis berechnen (mit Promo Code falls vorhanden)
        $finalPrice = $plan->price;
        $promoCode = null;

        if ($request->promo_code) {
            $promoCode = PromoCode::where('code', strtoupper($request->promo_code))->first();
            if ($promoCode && $promoCode->canBeUsedBy($user)) {
                $finalPrice = $promoCode->applyDiscount($plan->price);
            }
        }

        // Schritt 2: Zahlungsmethode nur bei bezahlten Plänen erforderlich
        if ($finalPrice > 0) {
            $request->validate([
                'payment_method' => 'required|string',
            ]);
        }

        try {
            // Schritt 3: Alte Cashier subscription löschen falls vorhanden
            // (verhindert mehrere aktive subscriptions)
            if ($user->subscribed('default')) {
                $user->subscription('default')->cancelNow();
            }

            // ========================================
            // PFAD 1: KOSTENLOSER PLAN (finalPrice = 0)
            // ========================================
            if ($finalPrice == 0) {
                // Nur plan_id setzen, KEINE Cashier subscription
                $user->update(['plan_id' => $plan->id]);

                // Promo Code als verwendet markieren
                if ($promoCode) {
                    $promoCode->markAsUsed($user);

                    // LOG: Promo-Code verwendet
                    PromoCodeActivityLog::log(
                        performedBy: $user,
                        promoCode: $promoCode,
                        action: 'used',
                        usedBy: $user,
                        changes: [
                            'plan' => $plan->name,
                            'discount' => $promoCode->discount_value . ($promoCode->discount_type === 'percentage' ? '%' : '€'),
                            'final_price' => $finalPrice,
                        ],
                        description: "Promo-Code '{$promoCode->code}' verwendet für Plan '{$plan->name}'"
                    );
                }

                // LOG: Kostenlose Subscription erstellt
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
            // PFAD 2: BEZAHLTER PLAN (finalPrice > 0)
            // ========================================

            // Cashier subscription erstellen (wird in 'subscriptions' Tabelle gespeichert)
            $subscription = $user->newSubscription('default', $plan->stripe_plan_id);

            // Promo Code als verwendet markieren
            if ($promoCode) {
                $promoCode->markAsUsed($user);

                // LOG: Promo-Code verwendet
                PromoCodeActivityLog::log(
                    performedBy: $user,
                    promoCode: $promoCode,
                    action: 'used',
                    usedBy: $user,
                    changes: [
                        'plan' => $plan->name,
                        'discount' => $promoCode->discount_value . ($promoCode->discount_type === 'percentage' ? '%' : '€'),
                        'original_price' => $plan->price,
                        'final_price' => $finalPrice,
                    ],
                    description: "Promo-Code '{$promoCode->code}' verwendet für Plan '{$plan->name}'"
                );
            }

            // Stripe subscription mit Zahlungsmethode erstellen
            $stripeSubscription = $subscription->create($request->payment_method);

            // plan_id ebenfalls setzen (für schnellen Zugriff auf Plan-Details)
            $user->update(['plan_id' => $plan->id]);

            // LOG: Bezahlte Subscription erstellt
            SubscriptionActivityLog::log(
                performedBy: $user,
                targetUser: $user,
                plan: $plan,
                action: 'subscribed',
                changes: [
                    'plan' => $plan->name,
                    'price' => $finalPrice,
                    'promo_code' => $promoCode?->code,
                    'type' => 'paid',
                ],
                stripeSubscriptionId: $stripeSubscription->id ?? null,
                description: "Bezahlter Plan '{$plan->name}' abonniert für {$finalPrice}€/Monat"
            );

            return redirect()->route('subscription.success')
                ->with('success', 'Subscription erfolgreich abgeschlossen!');
        } catch (\Exception $e) {
            \Log::error('Subscription error: ' . $e->getMessage());
            return back()->with('error', 'Fehler beim Abschluss: ' . $e->getMessage());
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

        return Inertia::render('Subscription/Manage', [
            // Cashier subscription (null wenn kostenloser Plan)
            'subscription' => $user->subscription('default'),

            // Plan-Details (immer vorhanden über plan_id)
            'currentPlan' => $user->plan,

            // Rechnungen (nur bei Cashier subscriptions)
            'invoices' => $user->invoices(),

            // Plattform-Nutzung
            'platformsConnected' => $user->connectedPlatforms()->count(),
            'maxPlatforms' => $user->plan->max_platforms ?? 1,

            // Trial-Info
            'onTrial' => $user->onTrial(),
            'trialEndsAt' => $user->trial_ends_at,
        ]);
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
