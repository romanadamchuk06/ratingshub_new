<?php

namespace App\Http\Controllers;

use App\Models\ConnectedPlatform;
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
