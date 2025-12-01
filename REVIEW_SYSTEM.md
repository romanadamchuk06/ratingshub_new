# Review-Management System

## Übersicht

Das Review-Management System ermöglicht es Benutzern, Bewertungen von verschiedenen Plattformen (Google My Business, Trustpilot, etc.) zentral zu verwalten und zu beantworten.

## Features

### Für Benutzer

- **Review-Import** - Reviews automatisch von Plattformen synchronisieren
- **Zentrales Dashboard** - Alle Reviews an einem Ort sehen
- **Filteroptionen** - Nach Status, Rating, Plattform filtern
- **Review-Antworten** - Direkt auf Reviews antworten
- **Status-Tracking** - Reviews als beantwortet/archiviert markieren
- **Multi-Location** - Mehrere Standorte/Plattformen verwalten
- **Statistiken** - Durchschnittsbewertung, Antwort-Rate, etc.

## Architektur

### Datenbank-Schema

```
users
  └── hasMany reviews
  └── hasMany review_responses
  └── hasMany connected_platforms

connected_platforms (Google, Trustpilot, etc.)
  └── hasMany reviews

reviews
  ├── belongsTo user
  ├── belongsTo connected_platform
  └── hasMany review_responses

review_responses
  ├── belongsTo review
  └── belongsTo user
```

### Tabellen-Details

#### `reviews` Tabelle

| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | bigint | Primary Key |
| `user_id` | bigint | Welcher User sieht diesen Review |
| `connected_platform_id` | bigint | Von welcher Plattform stammt der Review |
| `provider_review_id` | string | Plattform-spezifische Review-ID (z.B. Google Review ID) |
| `rating` | int | Rating 1-5 Sterne |
| `text` | text | Review-Text (kann leer sein) |
| `reviewer_name` | string | Name des Reviewers |
| `reviewer_photo_url` | string | Profilbild-URL |
| `review_date` | timestamp | Wann wurde der Review auf der Plattform erstellt |
| `status` | enum | `pending`, `responded`, `archived` |
| `metadata` | json | Zusätzliche Daten (Location Name, etc.) |

**Unique Constraint:** `(connected_platform_id, provider_review_id)`
→ Verhindert Duplikate beim erneuten Sync

#### `review_responses` Tabelle

| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | bigint | Primary Key |
| `review_id` | bigint | Zu welchem Review gehört die Antwort |
| `user_id` | bigint | Wer hat geantwortet |
| `response_text` | text | Antwort-Text |
| `is_published` | boolean | Wurde an Plattform gesendet? |
| `published_at` | timestamp | Wann wurde gesendet |

## API-Integration

### Google My Business API

#### Setup

1. **Google Cloud Console öffnen:** https://console.cloud.google.com/

2. **Projekt erstellen/auswählen**

3. **Google My Business API aktivieren:**
   - APIs & Services → Library
   - Suche: "Google My Business API"
   - Klicke "Enable"

4. **OAuth 2.0 Credentials:**
   - APIs & Services → Credentials
   - Create Credentials → OAuth 2.0 Client ID
   - Application Type: Web application
   - Authorized redirect URIs: `https://deine-domain.com/platforms/callback/google`
   - Kopiere Client ID und Secret

5. **.env konfigurieren:**
   ```env
   GOOGLE_CLIENT_ID=xxx.apps.googleusercontent.com
   GOOGLE_CLIENT_SECRET=xxx
   GOOGLE_REDIRECT_URI=https://deine-domain.com/platforms/callback/google
   ```

6. **OAuth Scopes (config/services.php):**
   ```php
   'google' => [
       'client_id' => env('GOOGLE_CLIENT_ID'),
       'client_secret' => env('GOOGLE_CLIENT_SECRET'),
       'redirect' => env('GOOGLE_REDIRECT_URI'),
       'scopes' => [
           'https://www.googleapis.com/auth/business.manage',
       ],
   ],
   ```

#### Workflow

```
1. User verbindet Google-Konto (OAuth)
   ↓
2. ConnectedPlatform wird erstellt (access_token, refresh_token)
   ↓
3. User klickt "Synchronisieren" im Frontend
   ↓
4. ReviewController@sync wird aufgerufen
   ↓
5. GoogleMyBusinessService holt Reviews von API
   ↓
6. Neue Reviews werden in DB gespeichert
   ↓
7. Bestehende Reviews werden aktualisiert
```

#### API-Endpoints

**Reviews abrufen:**
```
GET https://mybusiness.googleapis.com/v4/{locationName}/reviews
```

**Auf Review antworten:**
```
PUT https://mybusiness.googleapis.com/v4/{locationName}/reviews/{reviewId}/reply
Body: { "comment": "Vielen Dank!" }
```

**Antwort löschen:**
```
DELETE https://mybusiness.googleapis.com/v4/{locationName}/reviews/{reviewId}/reply
```

### Token-Refresh

Access Tokens expiren nach **1 Stunde**. Der `GoogleMyBusinessService` refreshed automatisch:

```php
// Automatischer Refresh bei jedem API-Call
$token = $service->getAccessToken($platform);

// Intern:
if ($platform->expires_at->isPast()) {
    // POST https://oauth2.googleapis.com/token
    // grant_type=refresh_token&refresh_token=xxx
    // → Neuer Access Token → DB aktualisieren
}
```

## Frontend

### Reviews.vue

**Hauptseite für Review-Verwaltung**

Features:
- Location Selector (Filter nach Plattformen)
- Status Filter (pending, responded, archived)
- Rating Filter (1-5 Sterne)
- Sync Button (Reviews von API abrufen)
- Pagination (20 Reviews pro Seite)

```vue
<template>
  <AppLayout>
    <LocationSelector :locations="connectedPlatforms" />
    <Button @click="syncReviews">Synchronisieren</Button>

    <!-- Filters -->
    <Select @update:modelValue="filterByStatus">...</Select>
    <Select @update:modelValue="filterByRating">...</Select>

    <!-- Reviews -->
    <ReviewCard v-for="review in reviews.data" :review="review" />

    <!-- Pagination -->
    <Button v-for="link in reviews.links" @click="router.get(link.url)" />
  </AppLayout>
</template>
```

### ReviewCard.vue

**Einzelner Review mit Antwort-Funktionalität**

Features:
- Star Rating (visuell mit Icons)
- Reviewer-Info (Name, Foto)
- Plattform-Badge
- Status-Badge
- Review-Text
- Antwort-Formular (wenn noch nicht beantwortet)
- Bestehende Antworten anzeigen
- Archivieren/Wiederherstellen

```vue
<template>
  <Card>
    <!-- Header: Platform + Status + Date -->
    <Badge>{{ review.connected_platform.provider }}</Badge>
    <Badge>{{ statusText }}</Badge>

    <!-- Rating -->
    <Star v-for="(filled, i) in stars" :class="filled ? 'filled' : 'empty'" />

    <!-- Reviewer -->
    <img :src="review.reviewer_photo_url" />
    <p>{{ review.reviewer_name }}</p>

    <!-- Review Text -->
    <p>{{ review.text }}</p>

    <!-- Existing Responses -->
    <div v-if="review.responses.length">
      <p v-for="response in review.responses">{{ response.response_text }}</p>
    </div>

    <!-- Reply Form -->
    <Textarea v-model="replyText" v-if="isReplying" />
    <Button @click="submitReply">Antwort senden</Button>

    <!-- Actions -->
    <Button @click="toggleReply">Antworten</Button>
    <Button @click="updateStatus('archived')">Archivieren</Button>
  </Card>
</template>
```

## Backend

### ReviewController

**Hauptfunktionen:**

```php
// 1. Reviews anzeigen (mit Filtern)
public function index(Request $request)
{
    $query = Review::where('user_id', auth()->id())
        ->with(['connectedPlatform', 'responses']);

    // Filter nach Status
    if ($request->status) {
        $query->where('status', $request->status);
    }

    // Filter nach Rating
    if ($request->rating) {
        $query->where('rating', $request->rating);
    }

    $reviews = $query->paginate(20);

    return Inertia::render('Reviews', ['reviews' => $reviews]);
}

// 2. Reviews synchronisieren
public function sync(Request $request)
{
    $platform = ConnectedPlatform::find($request->connected_platform_id);

    // Google My Business API aufrufen
    $service = app(GoogleMyBusinessService::class);
    $newReviews = $service->fetchReviews($platform);

    return back()->with('success', "{$newReviews} neue Reviews importiert!");
}

// 3. Auf Review antworten
public function respond(Request $request, Review $review)
{
    // 1. Antwort in DB speichern
    $response = ReviewResponse::create([
        'review_id' => $review->id,
        'user_id' => auth()->id(),
        'response_text' => $request->response_text,
        'is_published' => false,
    ]);

    // 2. An Plattform-API senden
    $service = app(GoogleMyBusinessService::class);
    $service->replyToReview($review, $request->response_text);

    // 3. Als published markieren
    $response->update([
        'is_published' => true,
        'published_at' => now(),
    ]);

    // 4. Review-Status aktualisieren
    $review->update(['status' => 'responded']);

    return back()->with('success', 'Antwort gesendet!');
}

// 4. Statistiken
public function stats()
{
    return response()->json([
        'total_reviews' => Review::where('user_id', auth()->id())->count(),
        'average_rating' => Review::where('user_id', auth()->id())->avg('rating'),
        'pending_reviews' => Review::where('user_id', auth()->id())
            ->where('status', 'pending')->count(),
        'response_rate' => $this->calculateResponseRate(auth()->id()),
    ]);
}
```

### GoogleMyBusinessService

**Hauptfunktionen:**

```php
class GoogleMyBusinessService
{
    // 1. Access Token holen (mit Auto-Refresh)
    public function getAccessToken(ConnectedPlatform $platform): string
    {
        if ($platform->expires_at->isPast()) {
            $this->refreshAccessToken($platform);
        }
        return $platform->access_token;
    }

    // 2. Reviews abrufen
    public function fetchReviews(ConnectedPlatform $platform): int
    {
        $token = $this->getAccessToken($platform);
        $locationName = $platform->metadata['location_name'];

        $response = Http::withToken($token)
            ->get("https://mybusiness.googleapis.com/v4/{$locationName}/reviews");

        $reviews = $response->json('reviews', []);

        foreach ($reviews as $reviewData) {
            $this->storeReview($platform, $reviewData);
        }

        return count($reviews);
    }

    // 3. Auf Review antworten
    public function replyToReview(Review $review, string $text): bool
    {
        $token = $this->getAccessToken($review->connectedPlatform);
        $locationName = $review->connectedPlatform->metadata['location_name'];

        $response = Http::withToken($token)
            ->put("{$locationName}/reviews/{$review->provider_review_id}/reply", [
                'comment' => $text,
            ]);

        return $response->successful();
    }
}
```

## Routes

```php
// Review Management (nur für subscribed User)
Route::middleware(['auth', 'verified', 'subscribed'])
    ->prefix('reviews')
    ->name('reviews.')
    ->group(function () {
        // Reviews-Übersicht
        Route::get('/', [ReviewController::class, 'index'])->name('index');

        // Reviews synchronisieren
        Route::post('/sync', [ReviewController::class, 'sync'])->name('sync');

        // Status ändern
        Route::patch('/{review}/status', [ReviewController::class, 'updateStatus'])
            ->name('update-status');

        // Antworten
        Route::post('/{review}/respond', [ReviewController::class, 'respond'])
            ->name('respond');

        // Antwort löschen
        Route::delete('/responses/{response}', [ReviewController::class, 'deleteResponse'])
            ->name('delete-response');

        // Statistiken
        Route::get('/stats', [ReviewController::class, 'stats'])->name('stats');
    });
```

## Subscription & Features

### Plan-Features

Reviews sind ein **Premium-Feature**. Free-User haben kein Zugriff.

```php
// In PlanSeeder:
Plan::create([
    'name' => 'Pro',
    'features' => [
        'max_platforms' => 5,
        'review_limit' => 1000,        // Max 1000 Reviews importieren
        'ai_responses' => true,         // KI-generierte Antworten (zukünftig)
        'review_analytics' => true,     // Erweiterte Statistiken
    ],
]);
```

### Feature-Checks

```php
// Im User Model:
public function getReviewLimit(): int
{
    if ($this->is_admin) return PHP_INT_MAX;
    if (!$this->subscribed('default')) return 0;

    return $this->plan->features['review_limit'] ?? 0;
}

public function remainingReviews(): int
{
    $limit = $this->getReviewLimit();
    $current = $this->reviews()->count();

    return max(0, $limit - $current);
}

// Im Controller:
if ($user->remainingReviews() === 0) {
    return back()->with('error', 'Review-Limit erreicht! Upgrade deinen Plan.');
}
```

## Scheduled Jobs (Optional)

Automatische Review-Synchronisation alle 30 Minuten:

```php
// app/Console/Kernel.php

protected function schedule(Schedule $schedule)
{
    // Alle 30 Minuten: Reviews synchronisieren
    $schedule->call(function () {
        $platforms = ConnectedPlatform::where('is_active', true)
            ->where('provider', 'google')
            ->get();

        $service = app(GoogleMyBusinessService::class);

        foreach ($platforms as $platform) {
            try {
                $service->fetchReviews($platform);
            } catch (\Exception $e) {
                Log::error('Review Sync fehlgeschlagen', [
                    'platform_id' => $platform->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    })->everyThirtyMinutes();
}
```

**Cron-Job starten:**
```bash
# In Docker
docker-compose exec app php artisan schedule:work

# Lokal
php artisan schedule:work
```

## Statistiken

### Review-Dashboard (zukünftig)

```php
public function getDashboardStats()
{
    $userId = auth()->id();

    return [
        // Gesamt-Anzahl
        'total_reviews' => Review::where('user_id', $userId)->count(),

        // Durchschnitt
        'average_rating' => round(Review::where('user_id', $userId)->avg('rating'), 2),

        // Unbeantwortete
        'pending_reviews' => Review::where('user_id', $userId)
            ->where('status', 'pending')->count(),

        // Antwort-Rate (% beantwortet)
        'response_rate' => $this->calculateResponseRate($userId),

        // Letzte 30 Tage
        'recent_reviews' => Review::where('user_id', $userId)
            ->where('review_date', '>=', now()->subDays(30))->count(),

        // Rating-Verteilung
        'rating_distribution' => [
            5 => Review::where('user_id', $userId)->where('rating', 5)->count(),
            4 => Review::where('user_id', $userId)->where('rating', 4)->count(),
            3 => Review::where('user_id', $userId)->where('rating', 3)->count(),
            2 => Review::where('user_id', $userId)->where('rating', 2)->count(),
            1 => Review::where('user_id', $userId)->where('rating', 1)->count(),
        ],
    ];
}
```

## Troubleshooting

### Keine Reviews werden importiert

**1. Prüfe OAuth-Scopes:**
```php
// config/services.php
'scopes' => [
    'https://www.googleapis.com/auth/business.manage',
],
```

**2. Prüfe Access Token:**
```bash
php artisan tinker
>>> $platform = ConnectedPlatform::find(1);
>>> $platform->access_token
>>> $platform->expires_at  // Abgelaufen?
```

**3. Prüfe Google API Console:**
- Ist die API aktiviert?
- Quota nicht überschritten?
- OAuth-Redirect-URI korrekt?

**4. Logs prüfen:**
```bash
tail -f storage/logs/laravel.log
```

### Token Refresh schlägt fehl

**Fehler:** "invalid_grant" oder "Token has been expired or revoked"

**Lösung:**
```php
// User muss Plattform neu verbinden
$platform->delete();
// → User zu /settings/platforms schicken → Neu verbinden
```

### Review-Antwort wird nicht gesendet

**Prüfe:**
1. Hat User Berechtigung auf der Plattform?
2. Ist Review schon beantwortet? (Google: nur 1 Antwort!)
3. API-Quota überschritten?

## Zukünftige Features

- [ ] **KI-Antworten** - GPT-4 generiert Antwort-Vorschläge
- [ ] **Bulk-Actions** - Mehrere Reviews auf einmal archivieren
- [ ] **Email-Benachrichtigungen** - Bei neuen negativen Reviews
- [ ] **Sentiment-Analyse** - Erkennung positiv/negativ/neutral
- [ ] **Templates** - Vorgefertigte Antwort-Vorlagen
- [ ] **Trustpilot Integration** - Zusätzlich zu Google
- [ ] **Facebook Reviews** - Integration

## Zusammenfassung

✅ **Review-Import** von Google My Business
✅ **Zentrale Verwaltung** aller Reviews
✅ **Antwort-Funktionalität** direkt in der App
✅ **Filter & Suche** nach Status, Rating, Plattform
✅ **Token-Refresh** automatisch
✅ **Subscription-basiert** (nur Pro/Enterprise User)
✅ **Multi-Location** Support
✅ **Statistiken** & Analytics

**Nächste Schritte:**
1. Google My Business API Setup abschließen
2. Erste Reviews importieren
3. Antwort-Funktionalität testen
4. Scheduled Job für Auto-Sync aktivieren
5. KI-Antworten implementieren (optional)
