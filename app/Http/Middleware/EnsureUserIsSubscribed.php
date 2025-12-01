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

        // Prüfe ob User ein aktives Abo hat (inkl. Grace Period!)
        if ($user && !$user->subscribed($subscription)) {
            // Redirect zu Subscription-Seite mit Nachricht
            return redirect()->route('subscription.index')
                ->with('error', 'Du benötigst ein aktives Abonnement, um auf diese Funktion zuzugreifen.');
        }

        return $next($request);
    }
}
