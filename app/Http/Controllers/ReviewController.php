<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\ConnectedPlatform;
use App\Models\ReviewResponse;
use App\Services\GoogleMyBusinessService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * ReviewController
 *
 * Verwaltet alle Reviews von verschiedenen Plattformen (Google, Trustpilot, etc.)
 *
 * Hauptfunktionen:
 * 1. Reviews von APIs abrufen und in DB speichern
 * 2. Reviews anzeigen mit Filter- und Sortieroptionen
 * 3. Auf Reviews antworten
 * 4. Review-Status verwalten (pending, responded, archived)
 */
class ReviewController extends Controller
{
    /**
     * Zeigt alle Reviews des eingeloggten Users
     *
     * Flow:
     * 1. User hat verbundene Plattformen (ConnectedPlatforms)
     * 2. Jede Plattform kann mehrere Reviews haben
     * 3. Reviews werden gefiltert nach Status, Rating, Plattform
     * 4. Reviews werden mit Antworten und Plattform-Info geladen
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Hole alle verbundenen Plattformen des Users
        // Diese werden für den LocationSelector im Frontend gebraucht
        $connectedPlatforms = $user->connectedPlatforms()
            ->where('is_active', true)
            ->get();

        // Filter-Parameter aus Request
        $selectedLocationIds = $request->input('locations', []); // Filter nach Plattformen
        $status = $request->input('status'); // Filter nach Status (pending, responded, archived)
        $rating = $request->input('rating'); // Filter nach Rating (1-5)
        $problems = $request->input('problems'); // Filter nach Problem-Reviews (mit negativen Sentiments)
        $highlightReviewId = $request->input('highlight'); // Review-ID zum Hervorheben

        // Query Builder für Reviews
        $query = Review::query()
            ->where('user_id', $user->id)
            // Eager Loading: Lade Beziehungen mit, um N+1 Queries zu vermeiden
            // - connectedPlatform: Plattform-Info (Google, Trustpilot, etc.)
            // - responses: Alle Antworten auf diesen Review
            // - sentiments: Sentiment-Analyse Kategorien für erste 10 Reviews
            ->with(['connectedPlatform', 'responses', 'sentiments'])
            // Neueste zuerst (nach Review-Datum auf der Plattform)
            ->orderBy('review_date', 'desc');

        // Filter nach ausgewählten Locations (Plattformen)
        if (!empty($selectedLocationIds)) {
            $query->whereIn('connected_platform_id', $selectedLocationIds);
        }

        // Filter nach Status
        if ($status) {
            $query->where('status', $status);
        }

        // Filter nach Rating
        if ($rating) {
            $query->where('rating', $rating);
        }

        // Filter nach Problem-Reviews (nur Reviews mit negativen Sentiments)
        // Prüfe ob problems Parameter gesetzt ist (true, 1, 'true', '1')
        if ($problems && ($problems === 'true' || $problems === '1' || $problems === 1 || $problems === true)) {
            $query->whereHas('sentiments', function ($q) {
                $q->where('sentiment', 'negative');
            });
            // Optional: Nur unbeantwortete anzeigen wenn 'only_pending' gesetzt ist
            // ->whereNotIn('status', ['responded', 'archived']);
        }

        // Wenn ein Review hervorgehoben werden soll, finde die richtige Seite
        $currentPage = 1;
        if ($highlightReviewId) {
            // Klone Query um Position zu finden
            $positionQuery = clone $query;

            // Hole alle IDs in der richtigen Reihenfolge
            $allReviewIds = $positionQuery->pluck('id')->toArray();

            // Finde Position des highlight-Reviews (0-basiert)
            $position = array_search((int)$highlightReviewId, $allReviewIds);

            if ($position !== false) {
                // Berechne Seitennummer (20 Reviews pro Seite)
                $currentPage = floor($position / 20) + 1;
            }
        }

        // Pagination: 20 Reviews pro Seite
        $reviews = $query->paginate(20, ['*'], 'page', $currentPage);

        return Inertia::render('Reviews', [
            'reviews' => $reviews,
            'connectedPlatforms' => $connectedPlatforms,
            'selectedLocationIds' => $selectedLocationIds,
            // Filter-State für Frontend (um Filter-UI zu befüllen)
            'filters' => [
                'status' => $status,
                'rating' => $rating,
                'problems' => $problems,
            ],
        ]);
    }

    /**
     * Ruft Reviews von der API ab und speichert sie in der DB
     *
     * Flow:
     * 1. User wählt eine verbundene Plattform aus
     * 2. Backend ruft API auf (z.B. Google My Business API)
     * 3. Neue Reviews werden in DB gespeichert
     * 4. Bestehende Reviews werden aktualisiert
     *
     * WICHTIG: Dieser Endpoint sollte entweder:
     * - Manuell vom User getriggert werden (Button "Reviews synchronisieren")
     * - Oder automatisch via Scheduled Job laufen (z.B. alle 30 Minuten)
     */
    public function sync(Request $request)
    {
        $request->validate([
            'connected_platform_id' => 'required|exists:connected_platforms,id',
        ]);

        $user = auth()->user();
        $connectedPlatform = ConnectedPlatform::findOrFail($request->connected_platform_id);

        // Sicherheit: User darf nur eigene Plattformen synchronisieren
        if ($connectedPlatform->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        try {
            // Zähle Reviews vor dem Sync
            $reviewsBefore = Review::where('user_id', $user->id)
                ->where('connected_platform_id', $connectedPlatform->id)
                ->count();

            // API-Integration je nach Provider
            // Unterstützte Provider: google, trustpilot
            $newReviewsCount = 0;

            if ($connectedPlatform->provider === 'google') {
                // Google My Business API
                $service = app(GoogleMyBusinessService::class);
                $newReviewsCount = $service->fetchReviews($connectedPlatform);
            } elseif ($connectedPlatform->provider === 'trustpilot') {
                // TODO: Trustpilot API Integration
                // $service = app(TrustpilotService::class);
                // $newReviewsCount = $service->fetchReviews($connectedPlatform);
                throw new \Exception('Trustpilot Integration noch nicht verfügbar.');
            } else {
                throw new \Exception('Unbekannter Provider: ' . $connectedPlatform->provider);
            }

            // Zähle Reviews nach dem Sync
            $reviewsAfter = Review::where('user_id', $user->id)
                ->where('connected_platform_id', $connectedPlatform->id)
                ->count();

            $totalReviews = $reviewsAfter;

            // Erstelle aussagekräftige Nachricht
            if ($newReviewsCount > 0) {
                $message = "✅ {$newReviewsCount} neue " .
                          ($newReviewsCount === 1 ? 'Bewertung' : 'Bewertungen') .
                          " synchronisiert! (Gesamt: {$totalReviews})";
                return back()->with('success', $message);
            } else {
                $message = "ℹ️ Keine neuen Bewertungen gefunden. (Gesamt: {$totalReviews})";
                return back()->with('info', $message);
            }
        } catch (\Exception $e) {
            \Log::error('Review Sync fehlgeschlagen', [
                'user_id' => $user->id,
                'platform_id' => $connectedPlatform->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Fehler beim Synchronisieren: ' . $e->getMessage());
        }
    }

    /**
     * Aktualisiert den Status eines Reviews
     *
     * Status-Optionen:
     * - pending: Neu, noch nicht bearbeitet
     * - responded: Beantwortet
     * - archived: Archiviert (User will ihn nicht mehr sehen)
     */
    public function updateStatus(Request $request, Review $review)
    {
        $user = auth()->user();

        // Sicherheit: User darf nur eigene Reviews bearbeiten
        if ($review->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'status' => 'required|in:pending,responded,archived',
        ]);

        $review->update([
            'status' => $request->status,
        ]);

        return back()->with('success', 'Status wurde aktualisiert.');
    }

    /**
     * Erstellt eine Antwort auf einen Review
     *
     * Flow:
     * 1. User schreibt Antwort-Text
     * 2. Antwort wird in DB gespeichert (review_responses Tabelle)
     * 3. Antwort wird an die Plattform-API gesendet
     * 4. Review-Status wird auf "responded" gesetzt
     *
     * WICHTIG: Die Antwort muss auch an die Plattform-API gesendet werden!
     * - Google My Business: POST /v4/accounts/{accountId}/locations/{locationId}/reviews/{reviewId}/reply
     * - Trustpilot: POST /v1/private/business-units/{businessUnitId}/reviews/{reviewId}/reply
     */
    public function respond(Request $request, Review $review)
    {
        $user = auth()->user();

        // Sicherheit: User darf nur auf eigene Reviews antworten
        if ($review->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'response_text' => 'required|string|min:10|max:2000',
        ]);

        try {
            // 1. Speichere Antwort in DB
            $response = ReviewResponse::create([
                'review_id' => $review->id,
                'user_id' => $user->id,
                'text' => $request->response_text,
                'sent_at' => null, // Noch nicht an Plattform gesendet
            ]);

            // 2. Sende Antwort an Plattform-API
            $connectedPlatform = $review->connectedPlatform;

            if ($connectedPlatform->provider === 'google') {
                // Google My Business API
                $service = app(GoogleMyBusinessService::class);
                $service->replyToReview($review, $request->response_text);

                // Markiere als gesendet
                $response->update(['sent_at' => now()]);
            } elseif ($connectedPlatform->provider === 'trustpilot') {
                // TODO: Trustpilot API Integration
                throw new \Exception('Trustpilot Integration noch nicht verfügbar.');
            }

            // 3. Aktualisiere Review-Status
            $review->update([
                'status' => 'responded',
            ]);

            return back()->with('success', 'Deine Antwort wurde gesendet! ✅');
        } catch (\Exception $e) {
            \Log::error('Review Response fehlgeschlagen', [
                'user_id' => $user->id,
                'review_id' => $review->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Fehler beim Senden der Antwort: ' . $e->getMessage());
        }
    }

    /**
     * Löscht eine Antwort
     *
     * Flow:
     * 1. Prüfe ob Antwort bereits an Plattform gesendet wurde (sent_at)
     * 2. Falls ja: Versuche Antwort auch von Plattform zu löschen (Google)
     * 3. Lösche Antwort aus DB
     * 4. Aktualisiere Review-Status auf "pending"
     *
     * WICHTIG: Nicht alle Plattformen erlauben das Löschen von Antworten!
     */
    public function deleteResponse(ReviewResponse $response)
    {
        $user = auth()->user();

        // Sicherheit: User darf nur eigene Antworten löschen
        if ($response->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        try {
            // Wenn Antwort bereits gesendet wurde, versuche sie auch von Plattform zu löschen
            if ($response->sent_at) {
                $review = $response->review;
                $connectedPlatform = $review->connectedPlatform;

                if ($connectedPlatform->provider === 'google') {
                    // Versuche Antwort von Google zu löschen
                    $service = app(GoogleMyBusinessService::class);

                    try {
                        $service->deleteReply($review);
                    } catch (\Exception $e) {
                        // Google erlaubt nicht immer das Löschen - log it
                        \Log::warning('Google Reply konnte nicht gelöscht werden', [
                            'response_id' => $response->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            // Lösche Antwort aus DB
            $review = $response->review;
            $response->delete();

            // Aktualisiere Review-Status zurück auf "pending" (falls keine anderen Antworten)
            $hasOtherResponses = ReviewResponse::where('review_id', $review->id)->exists();
            if (!$hasOtherResponses) {
                $review->update(['status' => 'pending']);
            }

            return back()->with('success', 'Antwort wurde gelöscht.');
        } catch (\Exception $e) {
            \Log::error('Fehler beim Löschen der Antwort', [
                'response_id' => $response->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Fehler beim Löschen: ' . $e->getMessage());
        }
    }

    /**
     * Zeigt Statistiken über Reviews
     *
     * Hilfreich für Dashboard:
     * - Durchschnittliche Rating
     * - Anzahl Reviews pro Monat
     * - Antwort-Rate (% der beantworteten Reviews)
     */
    public function stats()
    {
        $user = auth()->user();

        $stats = [
            // Gesamt-Anzahl Reviews
            'total_reviews' => Review::where('user_id', $user->id)->count(),

            // Durchschnittliches Rating (1-5)
            'average_rating' => Review::where('user_id', $user->id)
                ->avg('rating'),

            // Anzahl unbeantworteter Reviews
            'pending_reviews' => Review::where('user_id', $user->id)
                ->where('status', 'pending')
                ->count(),

            // Antwort-Rate (wie viele % wurden beantwortet?)
            'response_rate' => $this->calculateResponseRate($user->id),

            // Reviews in letzten 30 Tagen
            'recent_reviews' => Review::where('user_id', $user->id)
                ->where('review_date', '>=', now()->subDays(30))
                ->count(),

            // Rating-Verteilung (wie viele 1*, 2*, 3*, 4*, 5*)
            'rating_distribution' => $this->getRatingDistribution($user->id),
        ];

        return response()->json($stats);
    }

    /**
     * Berechnet die Antwort-Rate
     *
     * Formel: (Anzahl responded) / (Anzahl gesamt) * 100
     */
    private function calculateResponseRate(int $userId): float
    {
        $total = Review::where('user_id', $userId)->count();

        if ($total === 0) {
            return 0;
        }

        $responded = Review::where('user_id', $userId)
            ->where('status', 'responded')
            ->count();

        return round(($responded / $total) * 100, 2);
    }

    /**
     * Holt die Rating-Verteilung
     *
     * Gibt zurück: [1 => 5, 2 => 3, 3 => 8, 4 => 12, 5 => 20]
     * Bedeutet: 5 Reviews mit 1*, 3 Reviews mit 2*, etc.
     */
    private function getRatingDistribution(int $userId): array
    {
        $distribution = Review::where('user_id', $userId)
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        // Stelle sicher dass alle Ratings (1-5) vorhanden sind
        return [
            1 => $distribution[1] ?? 0,
            2 => $distribution[2] ?? 0,
            3 => $distribution[3] ?? 0,
            4 => $distribution[4] ?? 0,
            5 => $distribution[5] ?? 0,
        ];
    }
}
