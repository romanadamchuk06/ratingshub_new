<?php

namespace App\Listeners;

use App\Models\Plan;
use Illuminate\Auth\Events\Registered;

class AssignFreePlanToNewUser
{
    /**
     * Handle the event.
     */
    public function handle(Registered $event): void
    {
        $user = $event->user;

        // Find the Free plan
        $freePlan = Plan::where('slug', 'free')->first();

        if ($freePlan && !$user->plan_id) {
            // Assign Free plan and start 30-day trial
            $user->update([
                'plan_id' => $freePlan->id,
            ]);

            // Start 30-day trial
            $user->startTrial(30);
        }
    }
}
