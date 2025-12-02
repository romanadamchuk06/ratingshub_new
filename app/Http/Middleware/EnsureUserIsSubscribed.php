<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ENSURE USER IS SUBSCRIBED
 * ==========================
 *
 * Blockiert Features für User ohne aktives Abo.
 *
 * WARUM?
 * - Verhindert Zugriff auf Premium-Features ohne Zahlung
 * - Berücksichtigt Grace Periods (z.B. 3 Tage nach Zahlungsausfall)
 * - Leitet zu Billing-Seite um wenn Abo abgelaufen
 *
 * VERWENDUNG:
 * Route::middleware(['auth', 'subscribed'])->group(...)
 */
class EnsureUserIsSubscribed
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $subscription = 'default'): Response
    {
        $user = $request->user();

        // Admin hat immer Zugriff (selbst ohne Abo)
        if ($user && $user->is_admin) {
            return $next($request);
        }

        // Prüfe ob User ein aktives Abo hat
        // WICHTIG: Wir prüfen zwei Systeme:
        // 1. Plan-basiert: user.plan_id (direktes Plan-Assignment)
        // 2. Cashier-basiert: subscriptions Tabelle (Stripe/Paddle Integration)
        $hasAccess = $user && (
            $user->plan_id !== null ||              // User hat direkt zugewiesenen Plan
            $user->subscribed($subscription) ||      // User hat Cashier Subscription
            $user->onTrial()                         // User ist in Trial-Phase
        );

        if (!$hasAccess) {
            // Redirect zu Subscription-Seite mit Nachricht
            return redirect()->route('subscription.index')
                ->with('error', 'Du benötigst ein aktives Abonnement, um auf diese Funktion zuzugreifen.');
        }

        return $next($request);
    }
}
