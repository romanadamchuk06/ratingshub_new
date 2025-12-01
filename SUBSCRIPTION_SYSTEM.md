# Subscription System - Automatische Abbuchung & Feature-Blocking

## 1. Wie funktioniert die automatische Abbuchung?

### Stripe übernimmt alles automatisch! 🎉

```
User abonniert Plan
    ↓
Stripe speichert Zahlungsmethode (Kreditkarte)
    ↓
Jeden Monat am gleichen Tag:
    ↓
Stripe bucht automatisch ab
    ↓
Stripe sendet Webhook an deine App
    ↓
Laravel Cashier aktualisiert `subscriptions` Tabelle
```

**Du musst nichts manuell machen!** Stripe kümmert sich um:
- Monatliche Abbuchung
- Failed Payments (Retry Logic)
- Email-Benachrichtigungen an Kunden
- Rechnungs-PDFs

## 2. Webhooks - So bleibt deine App synchron

### Stripe Webhook Events

Stripe sendet diese Events automatisch an: `https://deine-app.com/stripe/webhook`

| Event | Was passiert | Cashier Action |
|-------|--------------|----------------|
| `invoice.payment_succeeded` | Zahlung erfolgreich | Subscription bleibt aktiv |
| `invoice.payment_failed` | Zahlung fehlgeschlagen | Subscription → `past_due` |
| `customer.subscription.updated` | Abo geändert (Plan-Wechsel) | Update `subscriptions` |
| `customer.subscription.deleted` | Abo gekündigt | Subscription → `canceled` |

### Webhook in Stripe Dashboard einrichten:

1. Gehe zu: https://dashboard.stripe.com/webhooks
2. Klicke "Add endpoint"
3. URL: `https://deine-app.com/stripe/webhook`
4. Events auswählen:
   - `invoice.payment_succeeded`
   - `invoice.payment_failed`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`
5. Webhook Secret kopieren
6. In `.env` einfügen:
   ```bash
   STRIPE_WEBHOOK_SECRET=whsec_...
   ```

### Grace Period (Kulanzfrist)

Cashier gibt dem User automatisch **5 Tage** Grace Period nach gescheiterter Zahlung:

```php
// .env
CASHIER_PAYMENT_GRACE_PERIOD=5
```

**Was heißt das?**
- User kann 5 Tage weiter nutzen nach Failed Payment
- Stripe versucht automatisch erneut abzubuchen
- Nach 5 Tagen: Subscription wird `canceled`

## 3. Features blockieren - 3 Methoden

### Methode 1: Middleware (Ganze Routes schützen)

```php
// routes/web.php

// Dashboard NUR für zahlende User
Route::middleware(['auth', 'subscribed'])->group(function () {
    Route::get('dashboard', ...)->name('dashboard');
    Route::get('reviews', ...)->name('reviews');
    Route::post('reviews/{review}/reply', ...)->name('reviews.reply');
});

// Admin hat IMMER Zugriff (auch ohne Abo)
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('admin/dashboard', ...);
});
```

**Was passiert wenn User nicht subscribed ist?**
→ Redirect zu `/subscription` mit Fehlermeldung

### Methode 2: Controller-Check (Einzelne Actions)

```php
// In einem Controller

public function reply(Review $review)
{
    $user = auth()->user();

    // Prüfe ob User ein aktives Abo hat
    if (!$user->subscribed('default')) {
        return redirect()->route('subscription.index')
            ->with('error', 'Du benötigst ein Abo um auf Reviews zu antworten.');
    }

    // Feature-Code...
}
```

### Methode 3: Plan-Features prüfen (JSON-Features)

```php
// User Model - Helper Methods

public function canUseAI(): bool
{
    // Admin kann immer
    if ($this->is_admin) {
        return true;
    }

    // Kein Abo → Nein
    if (!$this->subscribed('default')) {
        return false;
    }

    // Prüfe ob Plan das Feature hat
    $plan = $this->plan;
    return $plan && $plan->features['ai_responses'] ?? false;
}

public function canAddMultipleUsers(): bool
{
    if ($this->is_admin) return true;
    if (!$this->subscribed('default')) return false;

    $plan = $this->plan;
    return $plan && $plan->features['multi_user'] ?? false;
}

public function getReviewLimit(): int
{
    if ($this->is_admin) return PHP_INT_MAX;
    if (!$this->subscribed('default')) return 0;

    $plan = $this->plan;
    return $plan ? $plan->features['review_limit'] : 0;
}
```

**Verwendung im Controller:**

```php
public function store(Request $request)
{
    $user = auth()->user();

    // Prüfe Limit
    if ($user->reviews()->count() >= $user->getReviewLimit()) {
        return back()->with('error', 'Du hast dein Review-Limit erreicht. Upgrade deinen Plan!');
    }

    // Erstelle Review...
}
```

### Methode 4: Frontend-Blocking (Vue)

```vue
<!-- Dashboard.vue -->
<script setup>
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const user = page.props.auth.user;
const plan = user.plan;

const canUseAI = () => {
    return user.subscribed && plan?.features?.ai_responses;
};

const reviewsLeft = () => {
    const limit = plan?.features?.review_limit || 0;
    const current = user.reviews_count || 0;
    return Math.max(0, limit - current);
};
</script>

<template>
    <!-- AI-Antworten Button -->
    <Button
        v-if="canUseAI()"
        @click="generateAIResponse"
    >
        AI-Antwort generieren
    </Button>
    <div v-else class="text-muted-foreground">
        🔒 AI-Antworten ab Professional-Plan
    </div>

    <!-- Review Limit Warnung -->
    <div v-if="reviewsLeft() < 10" class="text-warning">
        ⚠️ Nur noch {{ reviewsLeft() }} Reviews verfügbar!
        <Link href="/subscription">Upgrade</Link>
    </div>
</template>
```

## 4. Subscription-Status prüfen

### Laravel Cashier Helper Methods

```php
$user = auth()->user();

// Hat User ein aktives Abo? (inkl. Grace Period!)
$user->subscribed('default'); // true/false

// Ist User im Trial?
$user->onTrial('default');

// Ist das Abo gekündigt aber noch aktiv?
$user->subscription('default')->canceled();

// Endet das Abo bald?
$user->subscription('default')->onGracePeriod();

// Ist Zahlung fehlgeschlagen?
$user->subscription('default')->pastDue();

// Wann endet das Abo?
$user->subscription('default')->ends_at;
```

## 5. Was passiert bei gescheiterter Zahlung?

### Timeline bei Failed Payment:

```
Tag 0: Zahlung schlägt fehl
    ↓
Stripe Webhook: invoice.payment_failed
    ↓
Subscription Status → `past_due`
    ↓
Grace Period beginnt (5 Tage)
    ↓
User KANN WEITER NUTZEN (subscribed() = true)
    ↓
Stripe versucht automatisch erneut (Tag 3, Tag 5, Tag 7)
    ↓
Tag 5: Grace Period endet
    ↓
subscribed() = false
    ↓
User wird blockiert (Middleware greift)
    ↓
Redirect zu /subscription mit Nachricht
```

### Email-Benachrichtigungen

Stripe sendet automatisch Emails:
- "Zahlung fehlgeschlagen"
- "Bitte Zahlungsmethode aktualisieren"
- "Dein Abo wurde gekündigt"

## 6. Abo-Kündigung durch User

```php
// SubscriptionController.php

public function cancel()
{
    $user = auth()->user();

    // Kündige Abo (aber bleibt bis Ende der Periode aktiv!)
    $user->subscription('default')->cancel();

    // Activity Log
    SubscriptionActivityLog::log(
        performedBy: $user,
        targetUser: $user,
        plan: $user->plan,
        action: 'canceled',
        description: "User hat Subscription gekündigt"
    );

    return redirect()->route('subscription.manage')
        ->with('success', 'Dein Abo wurde gekündigt. Du kannst es bis zum '
            . $user->subscription('default')->ends_at->format('d.m.Y') . ' weiter nutzen.');
}
```

**Wichtig:** Nach Kündigung kann User weiter nutzen bis `ends_at`!

## 7. Abo fortsetzen

```php
public function resume()
{
    $user = auth()->user();

    // Fortsetzen (wenn noch nicht abgelaufen)
    $user->subscription('default')->resume();

    return redirect()->route('subscription.manage')
        ->with('success', 'Dein Abo wurde fortgesetzt!');
}
```

## 8. Testing ohne echte Zahlungen

### Stripe Test Mode

In `.env`:
```bash
STRIPE_KEY=pk_test_...  # Test Public Key
STRIPE_SECRET=sk_test_...  # Test Secret Key
```

### Test-Kreditkarten:

| Karte | Verhalten |
|-------|-----------|
| `4242 4242 4242 4242` | Erfolgreiche Zahlung |
| `4000 0000 0000 0341` | Zahlung wird abgelehnt |
| `4000 0027 6000 3184` | Erfordert 3D Secure |

Alle: Ablaufdatum = Zukunft, CVC = beliebig

## 9. Subscription-Flow Beispiel

```php
// User-Registrierung → Plan wählen → Checkout

// 1. User wählt Plan
Route::get('/pricing', function () {
    $plans = Plan::active()->get();
    return Inertia::render('Pricing', ['plans' => $plans]);
});

// 2. Checkout
Route::get('/subscription/checkout/{plan}', function (Plan $plan) {
    return auth()->user()
        ->newSubscription('default', $plan->stripe_price_id)
        ->checkout([
            'success_url' => route('subscription.success'),
            'cancel_url' => route('subscription.index'),
        ]);
})->middleware('auth');

// 3. Success
Route::get('/subscription/success', function () {
    // User wird zurück geleitet nach erfolgreicher Zahlung
    // Subscription ist bereits aktiv (Webhook war schneller!)

    return Inertia::render('Subscription/Success');
})->middleware('auth');

// 4. Dashboard - NUR für zahlende User
Route::get('/dashboard', function () {
    // ...Dashboard Code
})->middleware(['auth', 'subscribed']);
```

## 10. Monitoring & Alerts

### Wichtige Checks:

```php
// Command: php artisan subscriptions:check

// Finde alle Subscriptions die bald enden
$endingSoon = Subscription::where('ends_at', '>', now())
    ->where('ends_at', '<', now()->addDays(7))
    ->get();

// Email an User: "Dein Abo endet in 7 Tagen"

// Finde alle Failed Payments
$pastDue = Subscription::where('stripe_status', 'past_due')->get();

// Email an Admin: "10 User haben Failed Payments"
```

## Zusammenfassung

✅ **Automatische Abbuchung**: Stripe macht das alles
✅ **Webhooks**: Automatische Synchronisation
✅ **Grace Period**: 5 Tage Kulanz bei Failed Payment
✅ **Feature-Blocking**: Middleware + Controller-Checks
✅ **Plan-Features**: JSON-basiert & flexibel
✅ **Admin-Zugriff**: Immer, auch ohne Abo
✅ **Kündigung**: User kann bis `ends_at` weiter nutzen

**Du musst nur:**
1. Webhook in Stripe einrichten
2. `subscribed` Middleware auf Routes anwenden
3. Features in Vue/Controller prüfen
4. Fertig! 🎉
