<?php

namespace App\Http\Controllers;

use App\Models\BusinessProfile;
use App\Models\ConnectedPlatform;
use App\Services\GoogleMyBusinessService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * BusinessProfileController
 *
 * Verwaltet die Öffnungszeiten des Unternehmens
 * Synchronisiert mit Google My Business API
 */
class BusinessProfileController extends Controller
{
    /**
     * Zeigt das Edit-Formular für Öffnungszeiten
     *
     * Lädt Öffnungszeiten direkt von Google My Business (falls verbunden)
     * Keine Default-Werte!
     */
    public function edit(GoogleMyBusinessService $googleService)
    {
        $user = auth()->user();

        // Hole oder erstelle Business Profile
        $businessProfile = $user->businessProfile()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'opening_hours' => null, // Keine Default-Werte!
            ]
        );

        // Versuche Öffnungszeiten von Google zu laden
        $googlePlatform = $user->connectedPlatforms()
            ->where('provider', 'google')
            ->where('is_active', true)
            ->first();

        if ($googlePlatform) {
            try {
                $googleHours = $googleService->getBusinessHours($googlePlatform);
                $businessProfile->opening_hours = $googleHours;
            } catch (\Exception $e) {
                // Fehler beim Laden von Google - verwende gespeicherte Zeiten
                \Log::error('Fehler beim Laden der Google Öffnungszeiten: ' . $e->getMessage());
            }
        }

        return Inertia::render('settings/BusinessProfile', [
            'businessProfile' => $businessProfile,
        ]);
    }

    /**
     * Aktualisiert die Öffnungszeiten
     * Synchronisiert mit Google My Business
     */
    public function update(Request $request, GoogleMyBusinessService $googleService)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'opening_hours' => 'required|array',
            'opening_hours.*.open' => 'nullable|string',
            'opening_hours.*.close' => 'nullable|string',
            'opening_hours.*.closed' => 'nullable|boolean',
        ]);

        // Speichere lokal
        $businessProfile = $user->businessProfile()->firstOrCreate(['user_id' => $user->id]);
        $businessProfile->update($validated);

        // Versuche mit Google zu synchronisieren
        $googlePlatform = $user->connectedPlatforms()
            ->where('provider', 'google')
            ->where('is_active', true)
            ->first();

        if ($googlePlatform) {
            try {
                $googleService->updateBusinessHours($googlePlatform, $validated['opening_hours']);
                return back()->with('success', 'Öffnungszeiten erfolgreich mit Google My Business synchronisiert! ✅');
            } catch (\Exception $e) {
                \Log::error('Fehler beim Synchronisieren der Öffnungszeiten mit Google: ' . $e->getMessage());
                return back()->with('error', 'Fehler beim Synchronisieren mit Google: ' . $e->getMessage());
            }
        }

        return back()->with('success', 'Öffnungszeiten gespeichert! ✅');
    }
}
