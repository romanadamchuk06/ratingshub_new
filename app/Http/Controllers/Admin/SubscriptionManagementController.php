<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\User;
use App\Models\SubscriptionActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubscriptionManagementController extends Controller
{
    /**
     * Display all users with their subscriptions.
     *
     * Features:
     * - Suche nach Name/Email
     * - Filter nach Plan
     * - Filter nach Status (aktiv, gekündigt, kein Abo)
     * - Sortierung
     * - Statistiken
     */
    public function index(Request $request)
    {
        $query = User::with(['plan', 'subscriptions' => function ($q) {
            $q->where('type', 'default')->latest();
        }]);

        // Suche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter nach Plan
        if ($request->filled('plan_id')) {
            if ($request->plan_id === 'none') {
                $query->whereNull('plan_id');
            } else {
                $query->where('plan_id', $request->plan_id);
            }
        }

        // Filter nach Status
        if ($request->filled('status')) {
            switch ($request->status) {
                case 'active':
                    $query->whereHas('subscriptions', function ($q) {
                        $q->where('type', 'default')
                          ->whereNull('ends_at');
                    });
                    break;
                case 'cancelled':
                    $query->whereHas('subscriptions', function ($q) {
                        $q->where('type', 'default')
                          ->whereNotNull('ends_at');
                    });
                    break;
                case 'free':
                    $query->whereDoesntHave('subscriptions', function ($q) {
                        $q->where('type', 'default');
                    });
                    break;
            }
        }

        // Sortierung
        $sortField = $request->get('sort', 'created_at');
        $sortDir = $request->get('dir', 'desc');
        $query->orderBy($sortField, $sortDir);

        $users = $query->paginate(20)->withQueryString();

        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        // Statistiken berechnen
        // Hinweis: Revenue-Berechnung wurde entfernt, da Stripe jetzt monatlich/jährlich
        // über separate Price IDs handhabt. Nutze Stripe Dashboard für Revenue-Daten.
        $stats = [
            'totalUsers' => User::count(),
            'activeSubscriptions' => User::whereHas('subscriptions', function ($q) {
                $q->where('type', 'default')->whereNull('ends_at');
            })->count(),
            'cancelledSubscriptions' => User::whereHas('subscriptions', function ($q) {
                $q->where('type', 'default')->whereNotNull('ends_at');
            })->count(),
            'usersWithoutSubscription' => User::whereDoesntHave('subscriptions', function ($q) {
                $q->where('type', 'default');
            })->count(),
            // Anzahl User pro Plan (nützlicher als ungenaue Revenue-Berechnung)
            'usersWithPlan' => User::whereNotNull('plan_id')->count(),
        ];

        // Letzte Aktivitäten
        $recentActivity = SubscriptionActivityLog::with(['performedBy', 'targetUser', 'plan'])
            ->latest()
            ->take(5)
            ->get();

        return Inertia::render('Admin/Subscriptions/Index', [
            'users' => $users,
            'plans' => $plans,
            'stats' => $stats,
            'recentActivity' => $recentActivity,
            'filters' => [
                'search' => $request->search,
                'plan_id' => $request->plan_id,
                'status' => $request->status,
                'sort' => $sortField,
                'dir' => $sortDir,
            ],
        ]);
    }

    /**
     * Update user's plan.
     */
    public function updatePlan(Request $request, User $user)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $plan = Plan::findOrFail($request->plan_id);
        $oldPlan = $user->plan;

        try {
            // If switching to free plan
            if ($plan->isFree()) {
                // Cancel active subscription if exists
                if ($user->subscribed('default')) {
                    $user->subscription('default')->cancelNow();
                }
            } else {
                // If user has existing subscription, swap it
                if ($user->subscribed('default')) {
                    $user->subscription('default')->swap($plan->stripe_plan_id);
                }
            }

            // Update user's plan
            $user->update(['plan_id' => $plan->id]);

            // LOG: Admin hat Plan geändert
            SubscriptionActivityLog::log(
                performedBy: auth()->user(),
                targetUser: $user,
                plan: $plan,
                action: 'admin_plan_changed',
                changes: [
                    'old_plan' => $oldPlan?->name,
                    'new_plan' => $plan->name,
                ],
                description: "Admin hat Plan von '{$oldPlan?->name}' auf '{$plan->name}' geändert"
            );

            return back()->with('success', "Plan für {$user->name} wurde auf {$plan->name} geändert.");
        } catch (\Exception $e) {
            return back()->with('error', 'Fehler beim Ändern des Plans: ' . $e->getMessage());
        }
    }

    /**
     * Cancel user's subscription.
     */
    public function cancelSubscription(User $user)
    {
        if (!$user->subscribed('default')) {
            return back()->with('error', 'Benutzer hat keine aktive Subscription.');
        }

        try {
            $user->subscription('default')->cancel();

            return back()->with('success', "Subscription für {$user->name} wurde gekündigt.");
        } catch (\Exception $e) {
            return back()->with('error', 'Fehler beim Kündigen: ' . $e->getMessage());
        }
    }

    /**
     * Resume cancelled subscription.
     */
    public function resumeSubscription(User $user)
    {
        if (!$user->subscription('default')?->cancelled()) {
            return back()->with('error', 'Benutzer hat keine gekündigte Subscription.');
        }

        try {
            $user->subscription('default')->resume();

            return back()->with('success', "Subscription für {$user->name} wurde reaktiviert.");
        } catch (\Exception $e) {
            return back()->with('error', 'Fehler beim Reaktivieren: ' . $e->getMessage());
        }
    }

    /**
     * Cancel subscription immediately.
     */
    public function cancelSubscriptionNow(User $user)
    {
        if (!$user->subscribed('default')) {
            return back()->with('error', 'Benutzer hat keine aktive Subscription.');
        }

        try {
            $user->subscription('default')->cancelNow();

            // Plan entfernen - User hat keinen Zugriff mehr
            $user->update(['plan_id' => null]);

            return back()->with('success', "Subscription für {$user->name} wurde sofort beendet. User hat keinen Zugriff mehr.");
        } catch (\Exception $e) {
            return back()->with('error', 'Fehler beim Beenden: ' . $e->getMessage());
        }
    }
}
