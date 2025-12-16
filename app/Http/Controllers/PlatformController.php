<?php

namespace App\Http\Controllers;

use App\Models\ConnectedPlatform;
use App\Services\GoogleMyBusinessService;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class PlatformController extends Controller
{
    /**
     * Redirect to Google OAuth
     */
    public function connect(string $provider)
    {
        if ($provider !== 'google') {
            return redirect()->route('dashboard')->with('error', 'Provider not supported yet.');
        }

        // User authenticated with their OWN Google account
        // We use a central OAuth app, but each user connects their own Google account
        return Socialite::driver('google')
            ->scopes([
                'https://www.googleapis.com/auth/business.manage',
            ])
            ->redirect();
    }

    /**
     * Handle OAuth callback
     */
    public function callback(string $provider)
    {
        if ($provider !== 'google') {
            return redirect()->route('dashboard')->with('error', 'Provider not supported yet.');
        }

        try {
            $socialiteUser = Socialite::driver('google')->user();

            // Save or update connected platform
            ConnectedPlatform::updateOrCreate(
                [
                    'user_id' => auth()->id(),
                    'provider' => 'google',
                ],
                [
                    'provider_id' => $socialiteUser->getId(),
                    'access_token' => $socialiteUser->token,
                    'refresh_token' => $socialiteUser->refreshToken,
                    'expires_at' => now()->addSeconds($socialiteUser->expiresIn ?? 3600),
                    'is_active' => true,
                    'metadata' => [
                        'email' => $socialiteUser->getEmail(),
                        'name' => $socialiteUser->getName(),
                    ],
                ]
            );

            return redirect()->route('dashboard')->with('success', 'Google My Business erfolgreich verbunden!');
        } catch (\Exception $e) {
            return redirect()->route('dashboard')->with('error', 'Fehler beim Verbinden: ' . $e->getMessage());
        }
    }

    /**
     * Holt verfügbare Locations für eine verbundene Plattform
     *
     * Flow (Google):
     * 1. Hole Accounts des Users von Google API
     * 2. Für jeden Account: Hole Locations
     * 3. Gib alle Locations zurück
     *
     * Response: JSON Array von Locations
     */
    public function getLocations(ConnectedPlatform $platform)
    {
        if ($platform->user_id !== auth()->id()) {
            abort(403);
        }

        try {
            if ($platform->provider === 'google') {
                $service = app(GoogleMyBusinessService::class);

                // Hole alle Accounts
                $accounts = $service->getAccounts($platform);
                $allLocations = [];

                // Für jeden Account: Hole Locations
                foreach ($accounts as $account) {
                    $locations = $service->getLocations($platform, $account['name']);

                    // Füge Account-Info zu jeder Location hinzu
                    // Business Information API v1 nutzt 'title' statt 'locationName'
                    foreach ($locations as $location) {
                        $allLocations[] = [
                            'account_name' => $account['name'],
                            'location_name' => $location['name'],
                            'location_display_name' => $location['title'] ?? $location['locationName'] ?? $location['name'],
                            'address' => $location['storefrontAddress'] ?? $location['address'] ?? null,
                        ];
                    }
                }

                return response()->json($allLocations);
            }

            return response()->json(['error' => 'Provider not supported'], 400);
        } catch (\Exception $e) {
            \Log::error('Fehler beim Abrufen der Locations', [
                'platform_id' => $platform->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Speichert die ausgewählte Location in metadata
     *
     * Body:
     * {
     *   "account_name": "accounts/123456789",
     *   "location_name": "accounts/123/locations/456",
     *   "location_display_name": "Mein Restaurant"
     * }
     */
    public function selectLocation(ConnectedPlatform $platform, Request $request)
    {
        if ($platform->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'account_name' => 'required|string',
            'location_name' => 'required|string',
            'location_display_name' => 'nullable|string',
        ]);

        // Aktualisiere metadata
        $platform->update([
            'metadata' => array_merge($platform->metadata ?? [], [
                'account_name' => $request->account_name,
                'location_name' => $request->location_name,
                'location_display_name' => $request->location_display_name,
            ]),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Location erfolgreich ausgewählt!',
        ]);
    }

    /**
     * Disconnect a platform
     */
    public function disconnect(ConnectedPlatform $platform)
    {
        if ($platform->user_id !== auth()->id()) {
            abort(403);
        }

        $platform->delete();

        return redirect()->route('dashboard')->with('success', 'Plattform getrennt.');
    }
}
