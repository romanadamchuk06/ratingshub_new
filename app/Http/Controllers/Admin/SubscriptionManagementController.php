<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubscriptionManagementController extends Controller
{
    /**
     * Display all users with their subscriptions.
     */
    public function index()
    {
        $users = User::with(['plan', 'subscriptions' => function ($query) {
            $query->where('type', 'default')->latest();
        }])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $plans = Plan::where('is_active', true)->get();

        return Inertia::render('Admin/Subscriptions/Index', [
            'users' => $users,
            'plans' => $plans,
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

            // Set to free plan
            $freePlan = Plan::where('slug', 'free')->first();
            if ($freePlan) {
                $user->update(['plan_id' => $freePlan->id]);
            }

            return back()->with('success', "Subscription für {$user->name} wurde sofort beendet.");
        } catch (\Exception $e) {
            return back()->with('error', 'Fehler beim Beenden: ' . $e->getMessage());
        }
    }
}
