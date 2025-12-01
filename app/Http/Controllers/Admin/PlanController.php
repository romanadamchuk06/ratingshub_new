<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PlanActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * PLAN-MANAGEMENT CONTROLLER
 * ==========================
 *
 * Verwaltet Subscription-Pläne im Admin-Panel:
 *
 * Features:
 * - Pläne erstellen, bearbeiten, löschen
 * - Preise ändern
 * - Features verwalten (JSON-Array)
 * - Pläne aktivieren/deaktivieren
 * - Sortierung ändern
 *
 * WICHTIG:
 * - stripe_plan_id muss mit Stripe-Price übereinstimmen
 * - Löschen nur wenn keine User den Plan haben
 * - Free-Plan sollte nicht gelöscht werden (System-Fallback)
 */
class PlanController extends Controller
{
    /**
     * Display list of all plans
     */
    public function index(): Response
    {
        // Alle Pläne mit User-Count
        $plans = Plan::withCount('users')
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();

        return Inertia::render('Admin/Plans/Index', [
            'plans' => $plans,
        ]);
    }

    /**
     * Show create form
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Plans/Create');
    }

    /**
     * Store new plan
     */
    public function store(Request $request)
    {
        // Validation
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:plans,slug|regex:/^[a-z0-9-]+$/',
            'stripe_plan_id' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0|max:9999.99',
            'max_platforms' => 'required|integer|min:1|max:1000',
            'description' => 'nullable|string',
            'features' => 'nullable|array',
            'features.*' => 'string|max:255',
            'is_active' => 'boolean',
            'is_popular' => 'boolean', // Zeigt "Beliebt"-Badge auf Pricing-Seite
            'sort_order' => 'nullable|integer|min:0|max:100',
        ]);

        // Stripe Plan ID ist required wenn Preis > 0
        if ($validated['price'] > 0 && empty($validated['stripe_plan_id'])) {
            return back()->with('error', 'Stripe Plan ID ist erforderlich für bezahlte Pläne.');
        }

        // Default sort_order
        if (!isset($validated['sort_order'])) {
            $validated['sort_order'] = Plan::max('sort_order') + 1;
        }

        // Default boolean values (HTML forms don't send unchecked checkboxes)
        $validated['is_active'] = $validated['is_active'] ?? false;
        $validated['is_popular'] = $validated['is_popular'] ?? false;

        try {
            $plan = Plan::create($validated);

            // LOG: Plan erstellt
            PlanActivityLog::log(
                performedBy: auth()->user(),
                plan: $plan,
                action: 'created',
                changes: $validated,
                description: "Plan '{$plan->name}' wurde erstellt"
            );

            return redirect()->route('admin.plans.index')
                ->with('success', "Plan '{$plan->name}' wurde erstellt.");
        } catch (\Exception $e) {
            return back()->with('error', 'Fehler beim Erstellen: ' . $e->getMessage());
        }
    }

    /**
     * Show edit form
     */
    public function edit(Plan $plan): Response
    {
        // User-Count für Warnung beim Bearbeiten
        $plan->loadCount('users');

        return Inertia::render('Admin/Plans/Edit', [
            'plan' => $plan,
        ]);
    }

    /**
     * Update plan
     */
    public function update(Request $request, Plan $plan)
    {
        // Validation
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|regex:/^[a-z0-9-]+$/|unique:plans,slug,' . $plan->id,
            'stripe_plan_id' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0|max:9999.99',
            'max_platforms' => 'required|integer|min:1|max:1000',
            'description' => 'nullable|string',
            'features' => 'nullable|array',
            'features.*' => 'string|max:255',
            'is_active' => 'boolean',
            'is_popular' => 'boolean', // Zeigt "Beliebt"-Badge auf Pricing-Seite
            'sort_order' => 'nullable|integer|min:0|max:100',
        ]);

        // Stripe Plan ID ist required wenn Preis > 0
        if ($validated['price'] > 0 && empty($validated['stripe_plan_id'])) {
            return back()->with('error', 'Stripe Plan ID ist erforderlich für bezahlte Pläne.');
        }

        // Default boolean values (HTML forms don't send unchecked checkboxes)
        $validated['is_active'] = $validated['is_active'] ?? false;
        $validated['is_popular'] = $validated['is_popular'] ?? false;

        try {
            // Alte Werte speichern für Changelog
            $oldValues = $plan->only(array_keys($validated));

            $plan->update($validated);

            // Änderungen berechnen (nur geänderte Felder)
            $changes = [];
            foreach ($validated as $key => $newValue) {
                if ($oldValues[$key] != $newValue) {
                    $changes[$key] = ['old' => $oldValues[$key], 'new' => $newValue];
                }
            }

            // LOG: Plan aktualisiert (nur wenn es Änderungen gab)
            if (!empty($changes)) {
                PlanActivityLog::log(
                    performedBy: auth()->user(),
                    plan: $plan,
                    action: 'updated',
                    changes: $changes,
                    description: "Plan '{$plan->name}' wurde aktualisiert"
                );
            }

            return redirect()->route('admin.plans.index')
                ->with('success', "Plan '{$plan->name}' wurde aktualisiert.");
        } catch (\Exception $e) {
            return back()->with('error', 'Fehler beim Aktualisieren: ' . $e->getMessage());
        }
    }

    /**
     * Toggle plan active status
     */
    public function toggleActive(Plan $plan)
    {
        try {
            $oldStatus = $plan->is_active;
            $plan->update(['is_active' => !$plan->is_active]);

            $status = $plan->is_active ? 'aktiviert' : 'deaktiviert';

            // LOG: Plan aktiviert/deaktiviert
            PlanActivityLog::log(
                performedBy: auth()->user(),
                plan: $plan,
                action: 'toggled_active',
                changes: ['is_active' => ['old' => $oldStatus, 'new' => $plan->is_active]],
                description: "Plan '{$plan->name}' wurde {$status}"
            );

            return back()->with('success', "Plan '{$plan->name}' wurde {$status}.");
        } catch (\Exception $e) {
            return back()->with('error', 'Fehler: ' . $e->getMessage());
        }
    }

    /**
     * Toggle plan popular status
     *
     * Toggelt den "Beliebt"-Badge für einen Plan.
     * Dies beeinflusst die Darstellung auf der Pricing-Seite:
     * - Badge "Beliebt" wird angezeigt
     * - Hervorhebung mit border-primary und shadow
     * - Button wird als Primary dargestellt
     */
    public function togglePopular(Plan $plan)
    {
        try {
            $oldStatus = $plan->is_popular;
            $plan->update(['is_popular' => !$plan->is_popular]);

            $status = $plan->is_popular ? 'als "Beliebt" markiert' : 'von "Beliebt" entfernt';

            // LOG: Plan als beliebt markiert/entfernt
            PlanActivityLog::log(
                performedBy: auth()->user(),
                plan: $plan,
                action: 'toggled_popular',
                changes: ['is_popular' => ['old' => $oldStatus, 'new' => $plan->is_popular]],
                description: "Plan '{$plan->name}' wurde {$status}"
            );

            return back()->with('success', "Plan '{$plan->name}' wurde {$status}.");
        } catch (\Exception $e) {
            return back()->with('error', 'Fehler: ' . $e->getMessage());
        }
    }

    /**
     * Delete plan
     *
     * WICHTIG: Nur möglich wenn keine User den Plan haben
     * Verhindert Data-Integrity-Probleme
     */
    public function destroy(Plan $plan)
    {
        try {
            // Prüfen: Hat dieser Plan User?
            $userCount = $plan->users()->count();

            if ($userCount > 0) {
                return back()->with('error', "Plan '{$plan->name}' kann nicht gelöscht werden. {$userCount} Benutzer nutzen diesen Plan.");
            }

            // Warnung: Free-Plan sollte nicht gelöscht werden
            if ($plan->slug === 'free') {
                return back()->with('error', "Der Free-Plan sollte nicht gelöscht werden (System-Fallback).");
            }

            $planName = $plan->name;
            $planId = $plan->id;
            $planData = $plan->toArray();

            // LOG: Plan gelöscht (BEVOR wir löschen, damit plan_id noch existiert)
            PlanActivityLog::log(
                performedBy: auth()->user(),
                plan: $plan,
                action: 'deleted',
                changes: $planData,
                description: "Plan '{$planName}' wurde gelöscht"
            );

            $plan->delete();

            return redirect()->route('admin.plans.index')
                ->with('success', "Plan '{$planName}' wurde gelöscht.");
        } catch (\Exception $e) {
            return back()->with('error', 'Fehler beim Löschen: ' . $e->getMessage());
        }
    }
}
