<?php

namespace App\Http\Controllers;

use App\Models\BusinessProfile;
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
     * Wenn User noch kein Business Profile hat, wird automatisch eins erstellt
     */
    public function edit()
    {
        $user = auth()->user();

        // Hole oder erstelle Business Profile
        $businessProfile = $user->businessProfile()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'business_name' => $user->name,
                'opening_hours' => BusinessProfile::getDefaultOpeningHours(),
                'country' => 'Deutschland',
            ]
        );

        return Inertia::render('Settings/BusinessProfile', [
            'businessProfile' => $businessProfile,
        ]);
    }

    /**
     * Aktualisiert das Business Profile
     */
    public function update(Request $request)
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

        return back()->with('success', 'Unternehmensprofil wurde erfolgreich aktualisiert! ✅');
    }
}
