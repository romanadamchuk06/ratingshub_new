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
 * Verwaltet das Unternehmensprofil des Users:
 * - Firmendaten (Name, Beschreibung, Branche)
 * - Kontaktdaten
 * - Adresse
 * - Öffnungszeiten
 * - Logo
 * - Social Media Links
 */
class BusinessProfileController extends Controller
{
    /**
     * Zeigt das Edit-Formular für das Business Profile
     *
     * Lädt die Öffnungszeiten von der verbundenen Google-Plattform (falls vorhanden)
     */
    public function edit(GoogleMyBusinessService $googleService)
    {
        $user = auth()->user();

        // Hole oder erstelle Business Profile (ohne Default-Öffnungszeiten)
        $businessProfile = $user->businessProfile()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'business_name' => $user->name,
                'opening_hours' => null, // Keine Default-Werte, nur von Google laden
                'country' => 'Deutschland',
            ]
        );

        // Google-Plattform finden
        $googlePlatform = ConnectedPlatform::where('user_id', $user->id)
            ->where('provider', 'google')
            ->where('is_active', true)
            ->first();

        // Versuche Öffnungszeiten von Google zu laden
        if ($googlePlatform) {
            try {
                $googleHours = $googleService->getBusinessHours($googlePlatform);
                // Überschreibe lokale Öffnungszeiten mit Google-Daten
                $businessProfile->opening_hours = $googleHours;
            } catch (\Exception $e) {
                \Log::warning('Konnte Google Öffnungszeiten nicht laden', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return Inertia::render('settings/BusinessProfile', [
            'businessProfile' => $businessProfile,
            'hasGooglePlatform' => $googlePlatform !== null,
        ]);
    }

    /**
     * Aktualisiert das Business Profile
     *
     * Synchronisiert Öffnungszeiten mit Google (falls verbunden)
     */
    public function update(Request $request, GoogleMyBusinessService $googleService)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'business_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:5000',
            'industry' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'street' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:255',
            'opening_hours' => 'nullable|array',
            'opening_hours.*.open' => 'nullable|string',
            'opening_hours.*.close' => 'nullable|string',
            'opening_hours.*.closed' => 'nullable|boolean',
            'social_links' => 'nullable|array',
            'social_links.facebook' => 'nullable|url|max:255',
            'social_links.instagram' => 'nullable|url|max:255',
            'social_links.twitter' => 'nullable|url|max:255',
            'social_links.linkedin' => 'nullable|url|max:255',
        ]);

        $businessProfile = $user->businessProfile()->firstOrCreate(['user_id' => $user->id]);
        $businessProfile->update($validated);

        // Synchronisiere Öffnungszeiten mit Google (falls verbunden)
        $googlePlatform = ConnectedPlatform::where('user_id', $user->id)
            ->where('provider', 'google')
            ->where('is_active', true)
            ->first();

        if ($googlePlatform && isset($validated['opening_hours'])) {
            try {
                $googleService->updateBusinessHours($googlePlatform, $validated['opening_hours']);
                return back()->with('success', 'Öffnungszeiten wurden erfolgreich in Google aktualisiert! ✅');
            } catch (\Exception $e) {
                \Log::error('Konnte Google Öffnungszeiten nicht aktualisieren', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
                return back()->with('error', 'Öffnungszeiten wurden lokal gespeichert, aber Google-Sync fehlgeschlagen: ' . $e->getMessage());
            }
        }

        return back()->with('success', 'Öffnungszeiten wurden erfolgreich gespeichert! ✅');
    }
}
