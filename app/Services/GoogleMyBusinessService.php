<?php

namespace App\Services;

use App\Models\ConnectedPlatform;
use App\Models\Review;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google My Business API Service
 *
 * Integriert die Google My Business API (v4.9) für Review-Management
 *
 * Hauptfunktionen:
 * 1. Access Token holen (mit automatischem Refresh)
 * 2. Reviews von Google-Locations abrufen
 * 3. Auf Reviews antworten
 * 4. Location-Informationen abrufen
 *
 * WICHTIG: Vor der Nutzung muss die Google My Business API aktiviert werden!
 *
 * Setup-Anleitung:
 * ================
 *
 * 1. Google Cloud Console öffnen: https://console.cloud.google.com/
 *
 * 2. Neues Projekt erstellen (oder bestehendes auswählen)
 *
 * 3. Google My Business API aktivieren:
 *    - APIs & Services → Library
 *    - Suche nach "Google My Business API"
 *    - Klicke "Enable"
 *
 * 4. OAuth 2.0 Credentials erstellen:
 *    - APIs & Services → Credentials
 *    - Create Credentials → OAuth 2.0 Client ID
 *    - Application Type: Web application
 *    - Authorized redirect URIs: https://deine-domain.com/platforms/callback/google
 *    - Kopiere Client ID und Client Secret
 *
 * 5. .env konfigurieren:
 *    GOOGLE_CLIENT_ID=xxx.apps.googleusercontent.com
 *    GOOGLE_CLIENT_SECRET=xxx
 *    GOOGLE_REDIRECT_URI=https://deine-domain.com/platforms/callback/google
 *
 * 6. OAuth Scopes (in PlatformController):
 *    - https://www.googleapis.com/auth/business.manage (für alles)
 *    - https://www.googleapis.com/auth/plus.business.manage (alternativ)
 *
 * API-Dokumentation:
 * ==================
 * - Google My Business API: https://developers.google.com/my-business/reference/rest
 * - Reviews Endpoint: https://developers.google.com/my-business/reference/rest/v4/accounts.locations.reviews
 */
class GoogleMyBusinessService
{
    /**
     * API Base URLs (neue separate APIs ab 2021)
     */
    private const ACCOUNT_MANAGEMENT_API = 'https://mybusinessaccountmanagement.googleapis.com/v1';
    private const BUSINESS_INFO_API = 'https://mybusinessbusinessinformation.googleapis.com/v1';
    private const REVIEWS_API = 'https://mybusiness.googleapis.com/v4'; // Reviews nutzt noch v4

    /**
     * Holt Access Token und refreshed ihn falls nötig
     *
     * Flow:
     * 1. Prüfe ob Token abgelaufen ist (expires_at)
     * 2. Falls ja: Nutze Refresh Token um neuen Access Token zu holen
     * 3. Aktualisiere DB mit neuem Access Token
     * 4. Gib Access Token zurück
     *
     * @param ConnectedPlatform $platform
     * @return string Access Token
     * @throws \Exception wenn Refresh fehlschlägt
     */
    public function getAccessToken(ConnectedPlatform $platform): string
    {
        // Prüfe ob Token noch gültig ist (mit 5 Min Puffer)
        if ($platform->expires_at && $platform->expires_at->subMinutes(5)->isPast()) {
            // Token ist abgelaufen → Refresh
            $this->refreshAccessToken($platform);
            // Reload Platform um neuen Token zu haben
            $platform->refresh();
        }

        return $platform->access_token;
    }

    /**
     * Refreshed den Access Token mit dem Refresh Token
     *
     * Google OAuth Token Refresh:
     * POST https://oauth2.googleapis.com/token
     * grant_type=refresh_token&refresh_token=xxx&client_id=xxx&client_secret=xxx
     *
     * Response: { access_token, expires_in, scope, token_type }
     *
     * @param ConnectedPlatform $platform
     * @throws \Exception
     */
    private function refreshAccessToken(ConnectedPlatform $platform): void
    {
        if (!$platform->refresh_token) {
            // Plattform als inaktiv markieren, damit User sieht dass Neuverbindung nötig ist
            $platform->update(['is_active' => false]);

            Log::warning('Kein Refresh Token vorhanden - Plattform deaktiviert', [
                'platform_id' => $platform->id,
                'user_id' => $platform->user_id,
            ]);

            throw new \Exception('Die Verbindung zu Google ist abgelaufen. Bitte verbinde dein Google-Konto unter Einstellungen → Plattformen neu.');
        }

        try {
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $platform->refresh_token,
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
            ]);

            if (!$response->successful()) {
                Log::error('Google Token Refresh fehlgeschlagen', [
                    'platform_id' => $platform->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new \Exception('Token Refresh fehlgeschlagen: ' . $response->body());
            }

            $data = $response->json();

            // Aktualisiere Access Token in DB
            $platform->update([
                'access_token' => $data['access_token'],
                'expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
            ]);

            Log::info('Google Access Token erfolgreich refreshed', [
                'platform_id' => $platform->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Exception beim Token Refresh', [
                'platform_id' => $platform->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Ruft alle Accounts des Users ab
     *
     * Google My Business Accounts = "Businesses" oder "Organisationen"
     * Ein Account kann mehrere Locations haben (z.B. Filialen)
     *
     * GET https://mybusiness.googleapis.com/v4/accounts
     *
     * Response:
     * {
     *   "accounts": [
     *     {
     *       "name": "accounts/123456789",
     *       "accountName": "Mein Business",
     *       "type": "PERSONAL",
     *       "state": { "status": "VERIFIED" }
     *     }
     *   ]
     * }
     *
     * @param ConnectedPlatform $platform
     * @return array Liste von Accounts
     */
    public function getAccounts(ConnectedPlatform $platform): array
    {
        $token = $this->getAccessToken($platform);

        // Neue Account Management API verwenden
        $response = Http::withToken($token)
            ->get(self::ACCOUNT_MANAGEMENT_API . '/accounts');

        if (!$response->successful()) {
            Log::error('Google Accounts abrufen fehlgeschlagen', [
                'platform_id' => $platform->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [];
        }

        return $response->json('accounts', []);
    }

    /**
     * Ruft alle Locations eines Accounts ab
     *
     * Location = Standort (z.B. Restaurant, Geschäft, Filiale)
     * Jede Location hat eigene Reviews
     *
     * GET https://mybusiness.googleapis.com/v4/{accountName}/locations
     *
     * Response:
     * {
     *   "locations": [
     *     {
     *       "name": "accounts/123/locations/456",
     *       "locationName": "Mein Restaurant",
     *       "primaryPhone": "+49123456789",
     *       "address": { ... },
     *       "websiteUrl": "https://..."
     *     }
     *   ]
     * }
     *
     * @param ConnectedPlatform $platform
     * @param string $accountName (z.B. "accounts/123456789")
     * @return array Liste von Locations
     */
    public function getLocations(ConnectedPlatform $platform, string $accountName): array
    {
        $token = $this->getAccessToken($platform);

        // Neue Business Information API (v1) benötigt read_mask Parameter
        // read_mask gibt an welche Felder wir lesen wollen
        $response = Http::withToken($token)
            ->get(self::BUSINESS_INFO_API . "/{$accountName}/locations", [
                'readMask' => 'name,title,storefrontAddress',
                'pageSize' => 100, // Maximal 100 Locations pro Request
            ]);

        if (!$response->successful()) {
            Log::error('Google Locations abrufen fehlgeschlagen', [
                'platform_id' => $platform->id,
                'account_name' => $accountName,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [];
        }

        return $response->json('locations', []);
    }

    /**
     * Ruft Reviews einer Location ab und speichert sie in der DB
     *
     * GET https://mybusiness.googleapis.com/v4/{locationName}/reviews
     *
     * Response:
     * {
     *   "reviews": [
     *     {
     *       "reviewId": "xyz",
     *       "reviewer": {
     *         "displayName": "Max Mustermann",
     *         "profilePhotoUrl": "https://..."
     *       },
     *       "starRating": "FIVE",  // ONE, TWO, THREE, FOUR, FIVE
     *       "comment": "Sehr gut!",
     *       "createTime": "2024-01-15T10:30:00Z",
     *       "updateTime": "2024-01-15T10:30:00Z",
     *       "reviewReply": {
     *         "comment": "Vielen Dank!",
     *         "updateTime": "2024-01-16T09:00:00Z"
     *       }
     *     }
     *   ],
     *   "nextPageToken": "abc123"  // für Pagination
     * }
     *
     * @param ConnectedPlatform $platform
     * @param string|null $locationName (optional, falls in metadata gespeichert)
     * @return int Anzahl neuer Reviews
     */
    public function fetchReviews(ConnectedPlatform $platform, ?string $locationName = null): int
    {
        // Location Name aus metadata holen (falls nicht übergeben)
        if (!$locationName) {
            $locationName = $platform->metadata['location_name'] ?? null;
        }

        if (!$locationName) {
            throw new \Exception('Location Name nicht gefunden. Bitte in metadata speichern.');
        }

        // WICHTIG: Reviews API benötigt vollständigen Pfad mit Account
        // Neue Business Information API gibt nur "locations/XXX" zurück
        // Reviews API braucht aber "accounts/YYY/locations/XXX"
        $accountName = $platform->metadata['account_name'] ?? null;

        if (!$accountName) {
            throw new \Exception('Account Name nicht gefunden. Bitte Location neu auswählen.');
        }

        // Vollständigen Location-Pfad bauen
        $fullLocationPath = $accountName . '/' . $locationName;

        $token = $this->getAccessToken($platform);
        $newReviewsCount = 0;

        // Pagination: Google gibt maximal 50 Reviews pro Request
        $pageToken = null;

        do {
            $url = self::REVIEWS_API . "/{$fullLocationPath}/reviews";

            if ($pageToken) {
                $url .= "?pageToken={$pageToken}";
            }

            $response = Http::withToken($token)->get($url);

            if (!$response->successful()) {
                Log::error('Google Reviews abrufen fehlgeschlagen', [
                    'platform_id' => $platform->id,
                    'location_name' => $locationName,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                break;
            }

            $data = $response->json();
            $reviews = $data['reviews'] ?? [];

            // Reviews in DB speichern
            foreach ($reviews as $reviewData) {
                $created = $this->storeReview($platform, $reviewData);
                if ($created) {
                    $newReviewsCount++;
                }
            }

            // Nächste Seite
            $pageToken = $data['nextPageToken'] ?? null;
        } while ($pageToken);

        Log::info('Google Reviews synchronisiert', [
            'platform_id' => $platform->id,
            'location_name' => $locationName,
            'new_reviews' => $newReviewsCount,
        ]);

        return $newReviewsCount;
    }

    /**
     * Speichert einen einzelnen Review in der DB
     *
     * @param ConnectedPlatform $platform
     * @param array $reviewData Google API Response Data
     * @return bool true wenn neu erstellt, false wenn bereits vorhanden
     */
    private function storeReview(ConnectedPlatform $platform, array $reviewData): bool
    {
        // Star Rating konvertieren: "FIVE" → 5
        $ratingMap = [
            'ONE' => 1,
            'TWO' => 2,
            'THREE' => 3,
            'FOUR' => 4,
            'FIVE' => 5,
        ];

        $rating = $ratingMap[$reviewData['starRating']] ?? 0;

        // Review in DB speichern (updateOrCreate verhindert Duplikate)
        $review = Review::updateOrCreate(
            [
                // Unique Constraint: Plattform + Provider Review ID
                'connected_platform_id' => $platform->id,
                'provider_review_id' => $reviewData['reviewId'],
            ],
            [
                'user_id' => $platform->user_id,
                'rating' => $rating,
                'text' => $reviewData['comment'] ?? null,
                'reviewer_name' => $reviewData['reviewer']['displayName'] ?? 'Anonym',
                'reviewer_photo_url' => $reviewData['reviewer']['profilePhotoUrl'] ?? null,
                'review_date' => $reviewData['createTime'] ?? now(),
                'status' => isset($reviewData['reviewReply']) ? 'responded' : 'pending',
                'metadata' => [
                    'location_name' => $platform->metadata['location_name'] ?? null,
                    'google_update_time' => $reviewData['updateTime'] ?? null,
                ],
            ]
        );

        return $review->wasRecentlyCreated;
    }

    /**
     * Sendet eine Antwort auf einen Review an Google
     *
     * PUT https://mybusiness.googleapis.com/v4/{locationName}/reviews/{reviewId}/reply
     *
     * Body:
     * {
     *   "comment": "Vielen Dank für dein Feedback!"
     * }
     *
     * WICHTIG: Ein Review kann nur EINMAL beantwortet werden!
     * Um die Antwort zu ändern, muss man eine neue PUT-Request mit neuem Text senden.
     *
     * @param Review $review
     * @param string $responseText
     * @return bool true wenn erfolgreich
     * @throws \Exception
     */
    public function replyToReview(Review $review, string $responseText): bool
    {
        $platform = $review->connectedPlatform;
        $token = $this->getAccessToken($platform);

        // Location Name aus metadata
        $locationName = $platform->metadata['location_name'] ?? null;
        $accountName = $platform->metadata['account_name'] ?? null;

        if (!$locationName || !$accountName) {
            throw new \Exception('Location oder Account Name nicht gefunden.');
        }

        // Vollständigen Pfad bauen
        $fullLocationPath = $accountName . '/' . $locationName;

        $url = self::REVIEWS_API . "/{$fullLocationPath}/reviews/{$review->provider_review_id}/reply";

        $response = Http::withToken($token)
            ->put($url, [
                'comment' => $responseText,
            ]);

        if (!$response->successful()) {
            Log::error('Google Review Reply fehlgeschlagen', [
                'review_id' => $review->id,
                'provider_review_id' => $review->provider_review_id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \Exception('Antwort konnte nicht gesendet werden: ' . $response->body());
        }

        Log::info('Google Review Reply erfolgreich', [
            'review_id' => $review->id,
            'provider_review_id' => $review->provider_review_id,
        ]);

        return true;
    }

    /**
     * Löscht eine Antwort von einem Review
     *
     * DELETE https://mybusiness.googleapis.com/v4/{locationName}/reviews/{reviewId}/reply
     *
     * WICHTIG: Nicht alle Reviews erlauben das Löschen von Antworten!
     * Google entscheidet das basierend auf verschiedenen Faktoren.
     *
     * @param Review $review
     * @return bool true wenn erfolgreich
     * @throws \Exception
     */
    public function deleteReply(Review $review): bool
    {
        $platform = $review->connectedPlatform;
        $token = $this->getAccessToken($platform);

        $locationName = $platform->metadata['location_name'] ?? null;
        $accountName = $platform->metadata['account_name'] ?? null;

        if (!$locationName || !$accountName) {
            throw new \Exception('Location oder Account Name nicht gefunden.');
        }

        // Vollständigen Pfad bauen
        $fullLocationPath = $accountName . '/' . $locationName;

        $url = self::REVIEWS_API . "/{$fullLocationPath}/reviews/{$review->provider_review_id}/reply";

        $response = Http::withToken($token)->delete($url);

        if (!$response->successful()) {
            Log::error('Google Review Reply löschen fehlgeschlagen', [
                'review_id' => $review->id,
                'provider_review_id' => $review->provider_review_id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \Exception('Antwort konnte nicht gelöscht werden: ' . $response->body());
        }

        Log::info('Google Review Reply gelöscht', [
            'review_id' => $review->id,
        ]);

        return true;
    }
}
