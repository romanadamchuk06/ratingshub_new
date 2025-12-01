<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use App\Models\PromoCodeActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PromoCodeController extends Controller
{
    public function index()
    {
        $promoCodes = PromoCode::withCount('usages')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Admin/PromoCodes/Index', [
            'promoCodes' => $promoCodes,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:promo_codes,code|max:50',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date',
            'description' => 'nullable|string|max:255',
        ]);

        $validated['code'] = strtoupper($validated['code']);

        $promoCode = PromoCode::create($validated);

        // LOG: Promo-Code erstellt
        PromoCodeActivityLog::log(
            performedBy: auth()->user(),
            promoCode: $promoCode,
            action: 'created',
            changes: $validated,
            description: "Promo-Code '{$promoCode->code}' erstellt ({$promoCode->discount_value}" . ($promoCode->discount_type === 'percentage' ? '%' : '€') . " Rabatt)"
        );

        return back()->with('success', 'Promo Code erstellt!');
    }

    public function update(Request $request, PromoCode $promoCode)
    {
        $validated = $request->validate([
            'is_active' => 'sometimes|boolean',
            'max_uses' => 'sometimes|nullable|integer|min:1',
            'expires_at' => 'sometimes|nullable|date',
        ]);

        // Alte Werte speichern für Changelog
        $oldValues = $promoCode->only(array_keys($validated));

        $promoCode->update($validated);

        // Änderungen berechnen
        $changes = [];
        foreach ($validated as $key => $newValue) {
            if ($oldValues[$key] != $newValue) {
                $changes[$key] = ['old' => $oldValues[$key], 'new' => $newValue];
            }
        }

        // LOG: Promo-Code aktualisiert (nur wenn es Änderungen gab)
        if (!empty($changes)) {
            $action = isset($changes['is_active']) ? 'toggled_active' : 'updated';

            PromoCodeActivityLog::log(
                performedBy: auth()->user(),
                promoCode: $promoCode,
                action: $action,
                changes: $changes,
                description: "Promo-Code '{$promoCode->code}' aktualisiert"
            );
        }

        return back()->with('success', 'Promo Code aktualisiert!');
    }

    public function destroy(PromoCode $promoCode)
    {
        $code = $promoCode->code;
        $promoCodeData = $promoCode->toArray();

        // LOG: Promo-Code gelöscht (BEVOR wir löschen)
        PromoCodeActivityLog::log(
            performedBy: auth()->user(),
            promoCode: $promoCode,
            action: 'deleted',
            changes: $promoCodeData,
            description: "Promo-Code '{$code}' gelöscht"
        );

        $promoCode->delete();

        return back()->with('success', 'Promo Code gelöscht!');
    }
}
