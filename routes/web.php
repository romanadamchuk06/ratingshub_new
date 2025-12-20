<?php

use App\Http\Controllers\PlatformController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

/**
 * STRIPE WEBHOOKS
 * ===============
 *
 * Custom Webhook Handler: App\Http\Controllers\StripeWebhookController
 * POST /stripe/webhook (automatisch von Cashier registriert)
 *
 * Stripe sendet Events an diese Route:
 * - invoice.payment_succeeded → Zahlung erfolgreich
 * - invoice.payment_failed → Zahlung fehlgeschlagen (Grace Period starten)
 * - customer.subscription.updated → Abo geändert
 * - customer.subscription.deleted → Abo gekündigt (Benachrichtigung senden)
 *
 * Die Route ist automatisch von CSRF-Protection ausgenommen.
 * Cashier aktualisiert automatisch die `subscriptions` Tabelle.
 */
Route::post('/stripe/webhook', [App\Http\Controllers\StripeWebhookController::class, 'handleWebhook'])
    ->name('cashier.webhook')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

Route::get('/', function () {
    // Aktive Pläne aus der Datenbank laden, sortiert nach sort_order
    $plans = \App\Models\Plan::where('is_active', true)
        ->orderBy('sort_order')
        ->get(['id', 'name', 'slug', 'price', 'description', 'features', 'is_popular']);

    return Inertia::render('WelcomeNew', [
        'canRegister' => Features::enabled(Features::registration()),
        'plans' => $plans,
    ]);
})->name('home');

// Test routes for error pages (remove in production)
Route::get('/404', function () {
    return Inertia::render('errors/404');
});

Route::get('/500', function () {
    return Inertia::render('errors/500');
});

Route::get('/503', function () {
    return Inertia::render('errors/503');
});

/**
 * GESCHÜTZTE ROUTEN - NUR FÜR ZAHLENDE USER
 * ===========================================
 *
 * Diese Routes sind mit 'subscribed' Middleware geschützt.
 * User ohne aktives Abo werden zu /subscription umgeleitet.
 *
 * Admins haben IMMER Zugriff (auch ohne Abo).
 */
Route::middleware(['auth', 'verified', 'subscribed'])->group(function () {
    Route::get('dashboard', function () {
        $user = auth()->user();

        // Connected Platforms laden (für Location Selector)
        $connectedPlatforms = $user->connectedPlatforms()
            ->where('is_active', true)
            ->get(['id', 'provider', 'metadata']);

        // Selected Location IDs aus URL holen (Komma-separiert)
        $selectedLocationIds = [];
        if (request()->has('locations')) {
            $selectedLocationIds = array_map('intval', explode(',', request('locations')));
        }

        // Review-Statistiken berechnen
        // Query Builder für Reviews (mit optionalem Location-Filter)
        $reviewsQuery = \App\Models\Review::where('user_id', $user->id);

        // Filter nach ausgewählten Locations (falls vorhanden)
        if (!empty($selectedLocationIds)) {
            $reviewsQuery->whereIn('connected_platform_id', $selectedLocationIds);
        }

        // Gesamtbewertungen
        $totalReviews = (clone $reviewsQuery)->count();

        // Durchschnittsbewertung (z.B. 4.5)
        $averageRating = (clone $reviewsQuery)->avg('rating');
        $averageRating = $averageRating ? round($averageRating, 1) : null;

        // Neue Reviews diese Woche (letzte 7 Tage)
        $newThisWeek = (clone $reviewsQuery)
            ->where('review_date', '>=', now()->subDays(7))
            ->count();

        // Reviews die noch beantwortet werden müssen (Status: pending)
        $pendingReviews = (clone $reviewsQuery)
            ->where('status', 'pending')
            ->count();

        // Neueste 5 Reviews für die Liste
        $recentReviews = (clone $reviewsQuery)
            ->with(['connectedPlatform'])
            ->orderBy('review_date', 'desc')
            ->limit(5)
            ->get();

        // Reviews mit Problemen (negative Sentiments) - für Problembereich
        // Zeigt die neuesten Reviews mit negativen Sentiments, damit User sofort Handlungsbedarf sieht
        // Nur unbeantwortete/nicht archivierte Reviews
        $problemReviews = (clone $reviewsQuery)
            ->whereHas('sentiments', function ($query) {
                $query->where('sentiment', 'negative');
            })
            ->whereNotIn('status', ['answered', 'archived']) // Nur offene Reviews mit Problemen
            ->with(['connectedPlatform', 'sentiments' => function ($query) {
                // Nur negative Sentiments laden
                $query->where('sentiment', 'negative');
            }])
            ->orderBy('review_date', 'desc')
            ->limit(3) // Nur 3 Reviews anzeigen
            ->get();

        // Zähle Reviews mit Problemen (für Stats) - auch nur unbeantwortete/nicht archivierte
        $reviewsWithProblems = (clone $reviewsQuery)
            ->whereHas('sentiments', function ($query) {
                $query->where('sentiment', 'negative');
            })
            ->whereNotIn('status', ['answered', 'archived'])
            ->count();

        return Inertia::render('Dashboard', [
            'connectedPlatforms' => $connectedPlatforms,
            'selectedLocationIds' => $selectedLocationIds,
            'stats' => [
                'totalReviews' => $totalReviews,
                'averageRating' => $averageRating,
                'newThisWeek' => $newThisWeek,
                'pendingReviews' => $pendingReviews,
                'reviewsWithProblems' => $reviewsWithProblems, // Neue Statistik
            ],
            'recentReviews' => $recentReviews,
            'problemReviews' => $problemReviews, // Reviews mit negativen Sentiments
        ]);
    })->name('dashboard');

});

/**
 * REVIEW MANAGEMENT ROUTES
 * =========================
 *
 * Verwaltet Reviews von verschiedenen Plattformen (Google, Trustpilot, etc.)
 *
 * Features:
 * - Reviews von APIs abrufen und speichern
 * - Reviews anzeigen mit Filtern (Status, Rating, Plattform)
 * - Auf Reviews antworten
 * - Review-Status verwalten (pending, responded, archived)
 * - Statistiken über Reviews
 */
Route::middleware(['auth', 'verified', 'subscribed'])->prefix('reviews')->name('reviews.')->group(function () {
    // Reviews-Übersicht (Index mit Filtern)
    Route::get('/', [\App\Http\Controllers\ReviewController::class, 'index'])->name('index');

    // Reviews von API synchronisieren
    Route::post('/sync', [\App\Http\Controllers\ReviewController::class, 'sync'])->name('sync');

    // Review-Status aktualisieren (pending → responded → archived)
    Route::patch('/{review}/status', [\App\Http\Controllers\ReviewController::class, 'updateStatus'])->name('update-status');

    // Auf Review antworten
    Route::post('/{review}/respond', [\App\Http\Controllers\ReviewController::class, 'respond'])->name('respond');

    // Antwort löschen
    Route::delete('/responses/{response}', [\App\Http\Controllers\ReviewController::class, 'deleteResponse'])->name('delete-response');

    // Review-Statistiken (für Dashboard)
    Route::get('/stats', [\App\Http\Controllers\ReviewController::class, 'stats'])->name('stats');

    // AI-generierte Antworten
    Route::post('/{review}/ai-response', [\App\Http\Controllers\AIResponseController::class, 'generate'])->name('ai-response');
});

// AI Styles Route (außerhalb reviews, da global)
Route::middleware(['auth', 'verified'])->get('/ai/styles', [\App\Http\Controllers\AIResponseController::class, 'styles'])->name('ai.styles');

// Platform OAuth Routes
Route::middleware(['auth', 'verified'])->prefix('platforms')->name('platforms.')->group(function () {
    Route::get('/connect/{provider}', [PlatformController::class, 'connect'])->name('connect');
    Route::get('/callback/{provider}', [PlatformController::class, 'callback'])->name('callback');
    Route::get('/{platform}/locations', [PlatformController::class, 'getLocations'])->name('locations');
    Route::post('/{platform}/select-location', [PlatformController::class, 'selectLocation'])->name('select-location');
    Route::delete('/{platform}', [PlatformController::class, 'disconnect'])->name('disconnect');
});

// Subscription Routes
Route::middleware(['auth', 'verified'])->prefix('subscription')->name('subscription.')->group(function () {
    Route::get('/', [App\Http\Controllers\SubscriptionController::class, 'index'])->name('index');
    Route::get('/checkout/{plan}', [App\Http\Controllers\SubscriptionController::class, 'checkout'])->name('checkout');
    Route::post('/subscribe/{plan}', [App\Http\Controllers\SubscriptionController::class, 'subscribe'])->name('subscribe');
    Route::post('/validate-promo-code', [App\Http\Controllers\SubscriptionController::class, 'validatePromoCode'])->name('validate-promo-code');
    Route::get('/success', [App\Http\Controllers\SubscriptionController::class, 'success'])->name('success');
    Route::get('/manage', [App\Http\Controllers\SubscriptionController::class, 'manage'])->name('manage');
    Route::post('/cancel', [App\Http\Controllers\SubscriptionController::class, 'cancel'])->name('cancel');
    Route::post('/resume', [App\Http\Controllers\SubscriptionController::class, 'resume'])->name('resume');
    Route::post('/payment-method', [App\Http\Controllers\SubscriptionController::class, 'updatePaymentMethod'])->name('payment-method.update');
    Route::get('/invoice/{invoice}', [App\Http\Controllers\SubscriptionController::class, 'invoice'])->name('invoice');
});

// Bug Report Routes (für User)
Route::middleware(['auth', 'verified'])->prefix('bug-reports')->name('bug-reports.')->group(function () {
    Route::get('/create', [\App\Http\Controllers\BugReportController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\BugReportController::class, 'store'])->name('store');
    Route::get('/my-reports', [\App\Http\Controllers\BugReportController::class, 'myReports'])->name('my-reports');
});

// Admin Routes
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                // Gesamte Benutzer (registriert)
                'totalUsers' => \App\Models\User::count(),

                // Verbundene Plattformen (OAuth-Connections)
                'totalPlatforms' => \App\Models\ConnectedPlatform::count(),

                // Aktive Subscriptions (User mit plan_id)
                'activeSubscriptions' => \App\Models\User::whereNotNull('plan_id')->count(),

                // Aktive Promo Codes (is_active = true)
                'activePromoCodes' => \App\Models\PromoCode::where('is_active', true)->count(),

                // Gesamt Subscription-Pläne (aktiv + inaktiv)
                'totalPlans' => \App\Models\Plan::count(),

                // Administratoren (is_admin = true)
                'totalAdmins' => \App\Models\User::where('is_admin', true)->count(),
            ],
        ]);
    })->name('dashboard');

    // User Management
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
    Route::post('users/{user}/toggle-admin', [\App\Http\Controllers\Admin\UserController::class, 'toggleAdmin'])->name('users.toggle-admin');

    // Subscription Management
    Route::get('subscriptions', [\App\Http\Controllers\Admin\SubscriptionManagementController::class, 'index'])->name('subscriptions.index');
    Route::post('subscriptions/{user}/update-plan', [\App\Http\Controllers\Admin\SubscriptionManagementController::class, 'updatePlan'])->name('subscriptions.update-plan');
    Route::post('subscriptions/{user}/cancel', [\App\Http\Controllers\Admin\SubscriptionManagementController::class, 'cancelSubscription'])->name('subscriptions.cancel');
    Route::post('subscriptions/{user}/cancel-now', [\App\Http\Controllers\Admin\SubscriptionManagementController::class, 'cancelSubscriptionNow'])->name('subscriptions.cancel-now');
    Route::post('subscriptions/{user}/resume', [\App\Http\Controllers\Admin\SubscriptionManagementController::class, 'resumeSubscription'])->name('subscriptions.resume');

    // Promo Code Management
    Route::get('promo-codes', [\App\Http\Controllers\Admin\PromoCodeController::class, 'index'])->name('promo-codes.index');
    Route::post('promo-codes', [\App\Http\Controllers\Admin\PromoCodeController::class, 'store'])->name('promo-codes.store');
    Route::patch('promo-codes/{promoCode}', [\App\Http\Controllers\Admin\PromoCodeController::class, 'update'])->name('promo-codes.update');
    Route::delete('promo-codes/{promoCode}', [\App\Http\Controllers\Admin\PromoCodeController::class, 'destroy'])->name('promo-codes.destroy');

    // Plan Management
    Route::get('plans', [\App\Http\Controllers\Admin\PlanController::class, 'index'])->name('plans.index');
    Route::get('plans/create', [\App\Http\Controllers\Admin\PlanController::class, 'create'])->name('plans.create');
    Route::post('plans', [\App\Http\Controllers\Admin\PlanController::class, 'store'])->name('plans.store');
    Route::get('plans/{plan}/edit', [\App\Http\Controllers\Admin\PlanController::class, 'edit'])->name('plans.edit');
    Route::patch('plans/{plan}', [\App\Http\Controllers\Admin\PlanController::class, 'update'])->name('plans.update');
    Route::post('plans/{plan}/toggle-active', [\App\Http\Controllers\Admin\PlanController::class, 'toggleActive'])->name('plans.toggle-active');
    Route::post('plans/{plan}/toggle-popular', [\App\Http\Controllers\Admin\PlanController::class, 'togglePopular'])->name('plans.toggle-popular');
    Route::delete('plans/{plan}', [\App\Http\Controllers\Admin\PlanController::class, 'destroy'])->name('plans.destroy');

    // Activity Logs (READ-ONLY - keine Edit/Delete!)
    Route::get('activity-logs', [\App\Http\Controllers\Admin\ActivityLogController::class, 'index'])->name('activity-logs.index');

    // Bug Reports Management
    Route::get('bug-reports', [\App\Http\Controllers\BugReportController::class, 'index'])->name('bug-reports.index');
    Route::get('bug-reports/{bugReport}', [\App\Http\Controllers\BugReportController::class, 'show'])->name('bug-reports.show');
    Route::patch('bug-reports/{bugReport}', [\App\Http\Controllers\BugReportController::class, 'update'])->name('bug-reports.update');
    Route::delete('bug-reports/{bugReport}', [\App\Http\Controllers\BugReportController::class, 'destroy'])->name('bug-reports.destroy');
});

require __DIR__.'/settings.php';
