# RatingsHub - System Analyse & Dokumentation

> Vollständige Analyse der Anwendungsarchitektur, Funktionsweise und identifizierte Probleme
>
> **Erstellt:** 2025-11-06
> **Projekt:** RatingsHub SaaS Platform
> **Tech Stack:** Laravel 11 + Vue 3 + Inertia.js + Stripe Cashier

---

## Inhaltsverzeichnis

1. [Projektübersicht](#projektübersicht)
2. [Architektur-Übersicht](#architektur-übersicht)
3. [Hybrid Subscription-System](#hybrid-subscription-system)
4. [Authentifizierung & Autorisierung](#authentifizierung--autorisierung)
5. [Datenbankstruktur](#datenbankstruktur)
6. [Frontend-Architektur](#frontend-architektur)
7. [Kritische Probleme](#kritische-probleme)
8. [Moderate Probleme](#moderate-probleme)
9. [Kleinere Probleme & Verbesserungen](#kleinere-probleme--verbesserungen)
10. [Empfohlene Maßnahmen](#empfohlene-maßnahmen)

---

## Projektübersicht

### Hauptfeatures

**RatingsHub** ist eine SaaS-Plattform für Review-Management mit folgenden Kernfeatures:

1. **Subscription-Management**
   - 4 Pläne: Free, Basic (€9.99), Pro (€29.99), Enterprise (€99.99)
   - Hybrid-System: `plan_id` + Laravel Cashier
   - Promo-Codes (Prozentual/Fest, mit Limits)
   - 30-Tage Trial für neue Benutzer

2. **Platform-Integration (OAuth)**
   - Google My Business Integration
   - OAuth-Token-Verwaltung
   - Mehrere Plattformen pro User (plan-abhängig)

3. **Admin-Panel**
   - User-Management (CRUD + Admin-Toggle)
   - Subscription-Management (Plan-Wechsel, Cancel/Resume)
   - Promo-Code-Verwaltung
   - Dashboard mit Statistiken

4. **Authentifizierung**
   - Laravel Fortify (Login, Register, Password Reset)
   - 2-Faktor-Authentifizierung (TOTP)
   - Email-Verifikation

5. **Settings**
   - Profil-Verwaltung
   - Passwort-Änderung
   - 2FA-Konfiguration
   - Theme-Einstellungen (Dark/Light Mode)
   - Platform-Management

---

## Architektur-Übersicht

### Tech Stack

```
Frontend:
- Vue 3 (Composition API)
- Inertia.js (SSR-Ready)
- TailwindCSS 4.x
- Reka UI (Component Library)
- Lucide Icons

Backend:
- Laravel 11.x
- Laravel Fortify (Auth)
- Laravel Cashier (Stripe)
- Laravel Socialite (OAuth)
- MySQL 8.0

DevOps:
- Docker + Docker Compose
- Vite (Build Tool)
- Node.js 22.x (im Container)
```

### Ordnerstruktur

```
/app
  /Http/Controllers
    /Admin              - PromoCodeController, UserController, SubscriptionManagementController
    /Settings           - ProfileController, PasswordController, TwoFactorAuthenticationController
    - PlatformController
    - SubscriptionController
  /Http/Middleware
    - EnsureUserIsAdmin
    - HandleInertiaRequests
  /Models
    - User, Plan, PromoCode, PromoCodeUsage, ConnectedPlatform
  /Listeners
    - AssignFreePlanToNewUser

/database
  /migrations          - 16 Migrations
  /seeders            - PlanSeeder, PromoCodeSeeder

/resources/js
  /pages
    /Admin             - Dashboard, Users, Subscriptions, PromoCodes
    /auth              - Login, Register, 2FA, Password Reset
    /settings          - Profile, Password, TwoFactor, Platforms
    /Subscription      - Pricing, Checkout, Manage, Success
  /components
    /ui                - Button, Input, Card, Badge, etc.
  /layouts            - AppLayout, AuthLayout, SettingsLayout

/routes
  - web.php           - Main Routes + Admin Routes
  - settings.php      - Settings Routes
```

---

## Hybrid Subscription-System

### Das Konzept

Das System nutzt **zwei verschiedene Methoden** zur Verwaltung von Subscriptions:

#### 1. Plan-ID System (Kostenlose Pläne)

**Wann verwendet:**
- Free-Plan (€0)
- Bezahlte Pläne mit 100% Promo-Code (finalPrice = 0)
- Manuell zugewiesene Pläne

**Wie funktioniert:**
```php
// Nur plan_id auf User setzen
$user->update(['plan_id' => $plan->id]);

// Vorteile:
✓ Einfach und schnell
✓ Kein Stripe-Overhead
✓ Keine Zahlungsmethode erforderlich

// Nachteile:
✗ Keine automatischen Zahlungen
✗ Keine Rechnungen
✗ Kein Cancel/Resume möglich
✗ Keine Stripe-Tracking
```

#### 2. Cashier Subscription System (Bezahlte Pläne)

**Wann verwendet:**
- Alle bezahlten Pläne (finalPrice > 0)
- Wenn Zahlungsmethode vorhanden

**Wie funktioniert:**
```php
// 1. Cashier Subscription erstellen
$subscription = $user->newSubscription('default', $plan->stripe_plan_id);
$subscription->create($request->payment_method);

// 2. plan_id AUCH setzen (für schnellen Zugriff)
$user->update(['plan_id' => $plan->id]);

// Vorteile:
✓ Automatische Zahlungen
✓ Rechnungen verfügbar
✓ Cancel/Resume funktioniert
✓ Stripe-Dashboard-Tracking
✓ Webhooks für Updates

// Nachteile:
✗ Komplexer
✗ Stripe-Gebühren
✗ Zahlungsmethode erforderlich
```

### Datenbankstruktur

**Tabelle: users**
```sql
id
plan_id (FK → plans.id)          -- Schneller Zugriff auf Plan
trial_ends_at                     -- Trial-Ende-Datum
is_admin                          -- Admin-Flag
stripe_id                         -- Cashier: Stripe Customer ID
pm_type, pm_last_four            -- Cashier: Zahlungsmethode
trial_ends_at (Cashier)          -- Cashier: Trial
```

**Tabelle: plans**
```sql
id
name                              -- "Free", "Basic", "Pro", "Enterprise"
slug                              -- "free", "basic", "pro", "enterprise"
stripe_plan_id                    -- Stripe Price ID (z.B. "price_xxx")
price DECIMAL(8,2)               -- Preis in Euro
max_platforms INT                -- Max. Plattformen
is_active BOOLEAN                -- Aktiv/Inaktiv
description TEXT
features JSON                    -- ["Feature 1", "Feature 2"]
```

**Tabelle: subscriptions** (Cashier)
```sql
id
user_id (FK)
type VARCHAR                      -- "default"
stripe_id VARCHAR                 -- Stripe Subscription ID (unique)
stripe_status VARCHAR             -- "active", "canceled", etc.
stripe_price VARCHAR              -- Stripe Price ID
ends_at TIMESTAMP                -- Kündigungsdatum (null = aktiv)
trial_ends_at TIMESTAMP
```

### Subscription-Ablauf

#### Szenario A: User wählt Free-Plan

```
1. User → GET /subscription/checkout/free
2. Controller prüft: plan.isFree() == true
3. Direkte Zuweisung:
   $user->update(['plan_id' => free.id])
4. KEINE Cashier subscription
5. Redirect → /subscription/success

Ergebnis:
- user.plan_id = 1 (Free)
- subscriptions Tabelle: leer
```

#### Szenario B: User kauft Pro-Plan mit Promo-Code

```
1. User → POST /subscription/validate-promo-code
   Code: "WELCOME20" (20% Rabatt)

2. Response:
   {
     valid: true,
     original_price: 29.99,
     discount: 6.00,
     final_price: 23.99
   }

3. User → POST /subscription/subscribe/pro
   payment_method: "pm_xxxxx"
   promo_code: "WELCOME20"

4. Controller-Logik:
   a) finalPrice = 23.99€ berechnen
   b) Alte subscription kündigen (falls vorhanden)
   c) Neue Cashier subscription erstellen:
      $user->newSubscription('default', 'price_pro')
           ->create('pm_xxxxx')
   d) Promo-Code als verwendet markieren:
      - promo_codes.used_count++
      - promo_code_usages Eintrag
   e) plan_id setzen:
      $user->update(['plan_id' => pro.id])

Ergebnis:
- user.plan_id = 3 (Pro)
- subscriptions: 1 Eintrag (Stripe Subscription)
- Stripe charged: 23.99€
- Nächste Zahlung: Automatisch 29.99€ (voller Preis)
```

#### Szenario C: Admin ändert Plan

```
Admin → POST /admin/subscriptions/{user}/update-plan
plan_id: 4 (Enterprise)

Controller-Logik:
1. Plan laden
2. Falls User Cashier subscription hat:
   - swap() zu neuem Stripe Price
3. plan_id aktualisieren

PROBLEM ⚠️:
Wenn User nur plan_id hat (keine Cashier subscription),
wird KEINE Stripe subscription erstellt!
→ Siehe "Kritische Probleme" unten
```

---

## Authentifizierung & Autorisierung

### Laravel Fortify

**Features aktiviert:**
- ✓ Registration
- ✓ Login
- ✓ Password Reset
- ✓ Email Verification
- ✓ Two-Factor Authentication
- ✗ Update User Password (via Settings-Controller)
- ✗ Update User Profile Information (via Settings-Controller)

**Konfiguration:**
```php
// FortifyServiceProvider@boot
Fortify::createUsersUsing(CreateNewUser::class);
Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
```

### 2-Faktor-Authentifizierung

**Implementierung:**
- User-Model nutzt `TwoFactorAuthenticatable` Trait
- TOTP-basiert (Google Authenticator kompatibel)
- Recovery Codes (10 Stück, JSON encrypted)

**Spalten in users:**
```
two_factor_secret (encrypted)
two_factor_recovery_codes (encrypted JSON)
two_factor_confirmed_at (timestamp)
```

**Ablauf:**
```
1. User aktiviert 2FA → /settings/two-factor
2. Fortify generiert:
   - QR-Code (two_factor_secret)
   - 10 Recovery Codes
3. User scannt QR-Code
4. User gibt TOTP-Code ein (Bestätigung)
5. two_factor_confirmed_at gesetzt

Login mit 2FA:
1. Email + Password korrekt
2. Redirect → /auth/two-factor-challenge
3. User gibt TOTP oder Recovery Code ein
4. Validierung → Session authentifiziert
```

### Admin-System

**Middleware:** `EnsureUserIsAdmin`
```php
public function handle(Request $request, Closure $next)
{
    if (!$request->user() || !$request->user()->is_admin) {
        abort(403, 'Unauthorized action.');
    }
    return $next($request);
}
```

**Route-Schutz:**
```php
Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // Admin Routes
    });
```

**Admin-Toggle:**
```php
// Admin/UserController@toggleAdmin
public function toggleAdmin(User $user)
{
    // ⚠️ PROBLEM: Kein Self-Toggle-Schutz!
    // Admin kann sich selbst entfernen
    $user->update(['is_admin' => !$user->is_admin]);
}
```

### Inertia Props Sharing

**Middleware:** `HandleInertiaRequests`

Alle Vue-Pages erhalten automatisch:
```php
[
    'auth' => [
        'user' => $request->user(),
        'isAdmin' => $request->user()?->is_admin ?? false,
        'hasPlatformConnected' => /* Check */,
    ],
    'name' => config('app.name'),
    'sidebarOpen' => /* Cookie-basiert */,
]
```

---

## Datenbankstruktur

### Tabellen-Übersicht (8 Core Tables)

| Tabelle | Zweck | Wichtige Spalten |
|---------|-------|------------------|
| `users` | Benutzer | plan_id, is_admin, trial_ends_at, stripe_id |
| `plans` | Subscription-Pläne | name, slug, stripe_plan_id, price, max_platforms |
| `subscriptions` | Cashier Subscriptions | user_id, stripe_id, stripe_status, ends_at |
| `subscription_items` | Cashier Items | subscription_id, stripe_id, stripe_price |
| `promo_codes` | Rabattcodes | code, type, value, max_uses, used_count, expires_at |
| `promo_code_usages` | Usage-Tracking | user_id, promo_code_id, used_at |
| `connected_platforms` | OAuth Platforms | user_id, provider, provider_id, access_token, expires_at |
| `cache`, `jobs` | Laravel Built-in | - |

### Modell-Beziehungen

```
User
  ├─ belongsTo(Plan) via plan_id
  ├─ hasMany(ConnectedPlatform)
  ├─ hasMany(Subscription) [Cashier Trait]
  └─ hasMany(Invoice) [Cashier Trait]

Plan
  └─ hasMany(User)

PromoCode
  └─ hasMany(PromoCodeUsage)

PromoCodeUsage
  ├─ belongsTo(User)
  └─ belongsTo(PromoCode)

ConnectedPlatform
  └─ belongsTo(User)
```

### Wichtige Constraints

**Unique Constraints:**
- `users.email` - Email eindeutig
- `plans.slug` - Slug eindeutig
- `promo_codes.code` - Code eindeutig
- `subscriptions.stripe_id` - Stripe Subscription ID eindeutig
- `connected_platforms(user_id, provider)` - User kann Provider nur 1x verbinden

**Foreign Keys:**
- `users.plan_id` → `plans.id` (nullable)
- `subscriptions.user_id` → `users.id`
- `connected_platforms.user_id` → `users.id` (onDelete cascade)
- `promo_code_usages.user_id` → `users.id`
- `promo_code_usages.promo_code_id` → `promo_codes.id`

---

## Frontend-Architektur

### Vue 3 + Composition API

**Setup:**
```vue
<script setup>
// Composition API - kein Options API
import { ref, computed } from 'vue';

const props = defineProps({ ... });
const loading = ref(false);
const total = computed(() => ...);
</script>
```

### Inertia.js

**Vorteile:**
- SSR-Ready (kann aktiviert werden)
- Keine API-Layer nötig
- Laravel-Props direkt in Vue
- Form-Handling mit `useForm()`

**Example:**
```vue
<script setup>
import { router } from '@inertiajs/vue3';

// Navigate
router.visit('/dashboard');

// Form Submit
router.post('/subscription/subscribe', {
    payment_method: 'pm_xxx',
    promo_code: 'WELCOME20'
});
</script>
```

### UI-Komponenten (Reka UI Port)

**Bibliothek:** Reka UI (Vue Port von Radix UI)

**Komponenten:**
- Button, Input, Label, Textarea
- Card (Header, Title, Description, Content, Footer)
- Dialog (Trigger, Content, Header, Footer)
- Table (Header, Body, Row, Cell)
- Select, DropdownMenu, Tooltip
- Badge, Sheet, Sidebar

**Styling:**
- TailwindCSS 4.x
- `clsx` + `tailwind-merge` für Class-Merging
- Custom `cn()` Utility

### Haupt-Pages

| Route | Component | Beschreibung |
|-------|-----------|--------------|
| `/` | `Welcome.vue` | Landing Page |
| `/dashboard` | `Dashboard.vue` | Haupt-Dashboard mit Stats |
| `/subscription` | `Subscription/Pricing.vue` | Alle Pläne anzeigen |
| `/subscription/checkout/{plan}` | `Subscription/Checkout.vue` | Stripe-Zahlung |
| `/subscription/manage` | `Subscription/Manage.vue` | Subscription verwalten |
| `/admin` | `Admin/Dashboard.vue` | Admin-Dashboard |
| `/admin/users` | `Admin/Users/Index.vue` | User-Liste |
| `/admin/subscriptions` | `Admin/Subscriptions/Index.vue` | Subscription-Management |
| `/admin/promo-codes` | `Admin/PromoCodes/Index.vue` | Promo-Code-Verwaltung |
| `/settings/profile` | `settings/Profile.vue` | Profil bearbeiten |
| `/settings/two-factor` | `settings/TwoFactor.vue` | 2FA-Konfiguration |

---

## Kritische Probleme

### 🔴 1. Admin Plan-Wechsel erstellt keine Stripe Subscription

**Datei:** `app/Http/Controllers/Admin/SubscriptionManagementController.php:35-64`

**Problem:**
```php
public function updatePlan(Request $request, User $user)
{
    $plan = Plan::findOrFail($request->plan_id);

    if ($plan->isFree()) {
        // Cancel subscription - OK ✓
        if ($user->subscribed('default')) {
            $user->subscription('default')->cancelNow();
        }
    } else {
        // ❌ PROBLEM: Nur swap() wenn subscription existiert
        if ($user->subscribed('default')) {
            $user->subscription('default')->swap($plan->stripe_plan_id);
        }
        // Was wenn User nur plan_id hat, aber keine Cashier subscription?
        // → Dann passiert GAR NICHTS!
    }

    $user->update(['plan_id' => $plan->id]);
}
```

**Szenario:**
```
1. User hat Free-Plan (nur plan_id, keine subscription)
2. Admin wechselt zu Pro-Plan (€29.99)
3. Code setzt plan_id = Pro
4. ABER: Keine Cashier subscription erstellt
5. User hat jetzt:
   - plan_id = Pro (DB sagt "Pro")
   - subscription = null (keine Stripe subscription)
   - Zahlt NICHTS!
```

**Auswirkung:**
- ⚠️ **Finanzieller Verlust:** User bekommen bezahlte Features ohne zu zahlen
- ⚠️ **Daten-Inkonsistenz:** plan_id sagt "Pro", aber keine Subscription
- ⚠️ **Stripe nicht synchron:** Stripe Dashboard zeigt keine Subscription

**Lösung:**
```php
public function updatePlan(Request $request, User $user)
{
    $plan = Plan::findOrFail($request->plan_id);

    try {
        if ($plan->isFree()) {
            // Free: Cancel subscription
            if ($user->subscribed('default')) {
                $user->subscription('default')->cancelNow();
            }
        } else {
            // Bezahlter Plan
            if ($user->subscribed('default')) {
                // Hat subscription → swap
                $user->subscription('default')->swap($plan->stripe_plan_id);
            } else {
                // ✅ NEU: Keine subscription → erstellen
                // ABER: Braucht Zahlungsmethode!
                // Option A: Fehler werfen
                throw new \Exception('User hat keine Zahlungsmethode. Bitte über Checkout-Flow upgraden.');

                // Option B: Default Payment Method nutzen (falls vorhanden)
                if ($user->hasDefaultPaymentMethod()) {
                    $user->newSubscription('default', $plan->stripe_plan_id)
                         ->create($user->defaultPaymentMethod()->id);
                } else {
                    throw new \Exception('Keine Zahlungsmethode vorhanden.');
                }
            }
        }

        $user->update(['plan_id' => $plan->id]);

        return back()->with('success', "Plan wurde geändert.");
    } catch (\Exception $e) {
        return back()->with('error', 'Fehler: ' . $e->getMessage());
    }
}
```

---

### 🔴 2. Manage-Seite: `invoices()` kann Fehler werfen ohne subscription

**Datei:** `app/Http/Controllers/SubscriptionController.php:248`

**Problem:**
```php
public function manage()
{
    $user = auth()->user();

    return Inertia::render('Subscription/Manage', [
        'subscription' => $user->subscription('default'),  // kann null sein
        'currentPlan' => $user->plan,
        'invoices' => $user->invoices(),  // ❌ Fehler wenn kein Stripe Customer!
        // ...
    ]);
}
```

**Szenario:**
```
1. User hat Free-Plan (keine Stripe subscription)
2. User → GET /subscription/manage
3. $user->invoices() wird aufgerufen
4. Cashier prüft: Hat User stripe_id?
5. Nein → Exception!
```

**Fehler:**
```
Stripe\Exception\InvalidRequestException
No such customer: 'cus_xxx' or customer is null
```

**Lösung:**
```php
public function manage()
{
    $user = auth()->user();

    return Inertia::render('Subscription/Manage', [
        'subscription' => $user->subscription('default'),
        'currentPlan' => $user->plan,

        // ✅ Nur Invoices wenn Stripe Customer existiert
        'invoices' => $user->hasStripeId() ? $user->invoices() : [],

        'platformsConnected' => $user->connectedPlatforms()->count(),
        'maxPlatforms' => $user->plan->max_platforms ?? 1,
        'onTrial' => $user->onTrial(),
        'trialEndsAt' => $user->trial_ends_at,
    ]);
}
```

---

### 🔴 3. Admin kann sich selbst entfernen

**Datei:** `app/Http/Controllers/Admin/UserController.php:toggleAdmin()`

**Problem:**
```php
public function toggleAdmin(User $user)
{
    // ❌ Kein Schutz vor Self-Toggle!
    $user->update(['is_admin' => !$user->is_admin]);

    return back()->with('success', 'Admin-Status geändert.');
}
```

**Szenario:**
```
1. Admin A logged in
2. Admin A → Toggle eigenen Admin-Status
3. Admin A ist jetzt KEIN Admin mehr
4. Admin A kann nicht mehr auf /admin zugreifen
5. Admin A locked out (außer DB-Zugriff)
```

**Auswirkung:**
- ⚠️ **Selbst-Lockout:** Admin kann sich aussperren
- ⚠️ **Letzter Admin:** Wenn letzter Admin sich removed → Niemand hat mehr Zugriff

**Lösung:**
```php
public function toggleAdmin(User $user)
{
    // ✅ Schutz vor Self-Toggle
    if ($user->id === auth()->id()) {
        return back()->with('error', 'Du kannst deinen eigenen Admin-Status nicht ändern.');
    }

    // ✅ Optional: Schutz vor "Letzter Admin"
    if ($user->is_admin) {
        $adminCount = User::where('is_admin', true)->count();
        if ($adminCount <= 1) {
            return back()->with('error', 'Du kannst den letzten Admin nicht entfernen.');
        }
    }

    $user->update(['is_admin' => !$user->is_admin]);

    return back()->with('success', 'Admin-Status wurde geändert.');
}
```

---

### 🔴 4. Promo-Code kann Negativ-Preis erzeugen

**Datei:** `app/Models/PromoCode.php:applyDiscount()`

**Problem:**
```php
public function applyDiscount(float $amount): float
{
    if ($this->type === 'percentage') {
        return $amount - $this->calculateDiscount($amount);
    }

    // Fixed discount
    // ❌ Keine Prüfung ob Rabatt > Preis!
    return $amount - $this->value;
}
```

**Szenario:**
```
Promo-Code: "MEGA50" (50€ fester Rabatt)
Plan: Basic (9.99€)

Berechnung:
  applyDiscount(9.99) = 9.99 - 50 = -40.01€

Stripe:
  Kann keine negative Zahlung erstellen → Fehler
```

**Auswirkung:**
- ⚠️ **Checkout bricht ab:** User kann nicht kaufen
- ⚠️ **Schlechte UX:** Unklarer Fehler

**Lösung:**
```php
public function applyDiscount(float $amount): float
{
    if ($this->type === 'percentage') {
        return $amount - $this->calculateDiscount($amount);
    }

    // ✅ Fixed: Maximal den vollen Betrag abziehen
    return max(0, $amount - $this->value);
}

// Besser noch: Validation beim Erstellen
public static function rules(): array
{
    return [
        'type' => 'required|in:percentage,fixed',
        'value' => [
            'required',
            'numeric',
            'min:0',
            function ($attribute, $value, $fail) {
                if (request('type') === 'percentage' && $value > 100) {
                    $fail('Prozentsatz darf nicht über 100% sein.');
                }
            }
        ],
    ];
}
```

---

### 🔴 5. Platform-OAuth Token-Refresh fehlt

**Datei:** `app/Http/Controllers/PlatformController.php`

**Problem:**
```php
public function callback($provider)
{
    $socialiteUser = Socialite::driver($provider)->user();

    // Token speichern
    auth()->user()->connectedPlatforms()->updateOrCreate([
        'provider' => $provider,
    ], [
        'access_token' => $socialiteUser->token,
        'refresh_token' => $socialiteUser->refreshToken,
        'expires_at' => now()->addSeconds($socialiteUser->expiresIn ?? 3600),
        // ...
    ]);
}

// ❌ FEHLT: Token-Refresh-Logik!
```

**Problem:**
- Google OAuth Access Tokens expiren nach 1 Stunde
- `refresh_token` wird gespeichert, aber NIE verwendet
- Nach 1 Stunde: API-Calls schlagen fehl

**Auswirkung:**
- ⚠️ **Platform-Features brechen:** Nach 1 Stunde keine API-Calls mehr möglich
- ⚠️ **User muss reconnecten:** Schlechte UX

**Lösung:**
```php
// Neue Methode in ConnectedPlatform Model
public function refreshAccessToken(): void
{
    if (!$this->refresh_token) {
        throw new \Exception('Kein Refresh Token vorhanden.');
    }

    // Google OAuth Token Refresh
    $client = new \Google\Client();
    $client->setClientId(config('services.google.client_id'));
    $client->setClientSecret(config('services.google.client_secret'));
    $client->fetchAccessTokenWithRefreshToken($this->refresh_token);

    $token = $client->getAccessToken();

    $this->update([
        'access_token' => $token['access_token'],
        'expires_at' => now()->addSeconds($token['expires_in']),
    ]);
}

// Check vor API-Calls
public function getAccessToken(): string
{
    if ($this->expires_at && $this->expires_at->isPast()) {
        $this->refreshAccessToken();
    }

    return $this->access_token;
}
```

---

## Moderate Probleme

### 🟡 6. User-Model: plan_id kann null sein

**Datei:** `app/Models/User.php`

**Problem:**
```php
public function canAddPlatform(): bool
{
    $currentCount = $this->connectedPlatforms()->count();
    // ❌ Was wenn $this->plan null ist?
    $maxPlatforms = $this->plan->max_platforms ?? 1;

    return $currentCount < $maxPlatforms;
}
```

**Szenario:**
```
1. User registriert sich
2. AssignFreePlanToNewUser Listener schlägt fehl (Free-Plan fehlt)
3. User hat plan_id = null
4. User → Dashboard
5. ConnectPlatformModal → canAddPlatform()
6. $this->plan ist null
7. PHP Error: "Trying to get property of non-object"
```

**Lösung:**
```php
public function canAddPlatform(): bool
{
    // ✅ Null-Check
    if (!$this->plan) {
        return false; // oder: Default-Verhalten
    }

    $currentCount = $this->connectedPlatforms()->count();
    $maxPlatforms = $this->plan->max_platforms ?? 1;

    return $currentCount < $maxPlatforms;
}

// BESSER: Sicherstellen dass plan_id IMMER gesetzt ist
// In User Migration:
$table->foreignId('plan_id')
      ->nullable(false)  // ✅ NOT NULL
      ->default(1)       // ✅ Default zu Free-Plan
      ->constrained('plans');
```

---

### 🟡 7. Promo-Code Validation: Race Condition

**Datei:** `app/Models/PromoCode.php:markAsUsed()`

**Problem:**
```php
public function markAsUsed(User $user): void
{
    // ❌ Race Condition: Zwei Requests gleichzeitig

    // Check
    if ($this->max_uses && $this->used_count >= $this->max_uses) {
        throw new \Exception('Code-Limit erreicht.');
    }

    // Increment (nicht atomic!)
    if (!$user->is_admin) {
        $this->increment('used_count');
    }

    // Insert
    $this->usages()->create([
        'user_id' => $user->id,
        'used_at' => now(),
    ]);
}
```

**Szenario:**
```
Promo-Code: "LIMITED10" (max_uses = 10, used_count = 9)

Request A (User 1):                 Request B (User 2):
1. Check: 9 < 10 ✓                  1. Check: 9 < 10 ✓
2. Increment: used_count = 10       2. Increment: used_count = 11
3. Insert usage                     3. Insert usage

Ergebnis: 11 Nutzungen statt max 10!
```

**Lösung:**
```php
public function markAsUsed(User $user): void
{
    // ✅ DB-Level Lock + Atomic Increment
    DB::transaction(function () use ($user) {
        // Lock row for update
        $code = PromoCode::where('id', $this->id)
                         ->lockForUpdate()
                         ->first();

        // Re-check nach Lock
        if (!$user->is_admin) {
            if ($code->max_uses && $code->used_count >= $code->max_uses) {
                throw new \Exception('Code-Limit erreicht.');
            }

            // Atomic increment
            $code->increment('used_count');
        }

        // Insert usage (unique constraint verhindert Duplikate)
        $this->usages()->create([
            'user_id' => $user->id,
            'used_at' => now(),
        ]);
    });
}
```

---

### 🟡 8. Settings-Route hat keine Breadcrumbs

**Datei:** `routes/settings.php`

**Problem:**
```php
Route::middleware(['auth'])->prefix('settings')->name('settings.')->group(function () {
    Route::redirect('/', '/settings/profile');

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');
    // ... andere Routes
});
```

**Problem:**
- Settings-Pages haben keine Breadcrumbs in `AppLayout`
- User kann nicht sehen: "Dashboard > Settings > Profile"
- Inkonsistent zu anderen Pages

**Lösung:**
```php
// In Controllers Props übergeben:
public function edit()
{
    return Inertia::render('settings/Profile', [
        'breadcrumbs' => [
            ['label' => 'Dashboard', 'href' => '/dashboard'],
            ['label' => 'Einstellungen', 'href' => '/settings/profile'],
            ['label' => 'Profil', 'href' => null], // current page
        ],
    ]);
}

// Oder: Middleware die automatisch Breadcrumbs generiert
```

---

### 🟡 9. Subscription Cancel: Kein Downgrade zu Free

**Datei:** `app/Http/Controllers/SubscriptionController.php:cancel()`

**Problem:**
```php
public function cancel()
{
    $user = auth()->user();

    if ($user->subscribed('default')) {
        // ❌ Nur cancel(), aber plan_id bleibt!
        $user->subscription('default')->cancel();

        return back()->with('success', 'Subscription gekündigt.');
    }
}
```

**Szenario:**
```
1. User hat Pro-Plan mit Cashier subscription
2. User kündigt → cancel() (ends_at gesetzt)
3. Subscription läuft bis Period-End
4. Nach Period-End:
   - Cashier subscription: inaktiv
   - user.plan_id: NOCH IMMER "Pro"! ❌
5. User hat noch immer Pro-Features ohne zu zahlen
```

**Lösung:**

**Option A:** Webhook für Subscription-Updates
```php
// routes/api.php
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);

// StripeWebhookController
public function handleWebhook(Request $request)
{
    $payload = $request->all();

    switch ($payload['type']) {
        case 'customer.subscription.deleted':
            // Subscription ended
            $user = User::where('stripe_id', $payload['data']['object']['customer'])->first();

            // ✅ Downgrade zu Free
            $freePlan = Plan::where('slug', 'free')->first();
            $user->update(['plan_id' => $freePlan->id]);
            break;
    }
}
```

**Option B:** Scheduled Job
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Täglich prüfen: Abgelaufene Subscriptions
    $schedule->call(function () {
        $users = User::whereNotNull('plan_id')
                     ->whereHas('subscriptions', function ($q) {
                         $q->where('stripe_status', 'canceled')
                           ->where('ends_at', '<', now());
                     })
                     ->get();

        $freePlan = Plan::where('slug', 'free')->first();

        foreach ($users as $user) {
            $user->update(['plan_id' => $freePlan->id]);
        }
    })->daily();
}
```

---

### 🟡 10. Platform Disconnect: Keine Bestätigung

**Datei:** `resources/js/pages/settings/Platforms.vue`

**Problem:**
```vue
<Button
    variant="destructive"
    @click="disconnectPlatform(platform.id)"
>
    Trennen
</Button>
```

```javascript
const disconnectPlatform = (platformId) => {
    // ❌ Kein Confirm-Dialog!
    router.delete(`/platforms/${platformId}`);
};
```

**Problem:**
- User kann versehentlich Platform disconnecten
- OAuth muss komplett wiederholt werden
- Schlechte UX

**Lösung:**
```vue
<script setup>
import { ref } from 'vue';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';

const platformToDisconnect = ref(null);

const confirmDisconnect = (platform) => {
    platformToDisconnect.value = platform;
};

const disconnectPlatform = () => {
    if (!platformToDisconnect.value) return;

    router.delete(`/platforms/${platformToDisconnect.value.id}`, {
        onSuccess: () => {
            platformToDisconnect.value = null;
        },
    });
};
</script>

<template>
    <AlertDialog>
        <AlertDialogTrigger as-child>
            <Button variant="destructive">
                Trennen
            </Button>
        </AlertDialogTrigger>
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>Plattform trennen?</AlertDialogTitle>
                <AlertDialogDescription>
                    Möchtest du die Verbindung zu {{ platform.provider }} wirklich trennen?
                    Du musst dich erneut authentifizieren, um die Plattform wieder zu verbinden.
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>Abbrechen</AlertDialogCancel>
                <AlertDialogAction @click="disconnectPlatform">
                    Ja, trennen
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
```

---

## Kleinere Probleme & Verbesserungen

### 🟢 11. Fehlende Indexes in Datenbank

**Performance-Problem:**

Häufige Queries ohne Index:
```sql
-- 1. User by stripe_id (Cashier)
SELECT * FROM users WHERE stripe_id = 'cus_xxx';

-- 2. Subscription by stripe_id
SELECT * FROM subscriptions WHERE stripe_id = 'sub_xxx';

-- 3. PromoCode by code
SELECT * FROM promo_codes WHERE code = 'WELCOME20';

-- 4. ConnectedPlatform by user_id + provider
SELECT * FROM connected_platforms
WHERE user_id = 1 AND provider = 'google';
```

**Lösung:**
```php
// Migration: add_indexes_for_performance
Schema::table('users', function (Blueprint $table) {
    $table->index('stripe_id');
    $table->index('plan_id');
});

Schema::table('subscriptions', function (Blueprint $table) {
    $table->index('stripe_id');
    $table->index(['user_id', 'type']);
});

Schema::table('promo_codes', function (Blueprint $table) {
    $table->index('code');
    $table->index('is_active');
});

Schema::table('connected_platforms', function (Blueprint $table) {
    $table->index(['user_id', 'provider']);
});
```

---

### 🟢 12. Plan-Features nicht validiert

**Datei:** `database/seeders/PlanSeeder.php`

**Problem:**
```php
Plan::create([
    'features' => [
        'Bis zu 1 Plattform',
        'E-Mail Support',
    ],
]);
```

**Problem:**
- Features sind Plain-Text
- Keine Struktur
- Schwer zu erweitern (z.B. Icons, Limits)

**Bessere Struktur:**
```php
Plan::create([
    'features' => [
        [
            'key' => 'platforms',
            'label' => 'Verbundene Plattformen',
            'value' => '1',
            'type' => 'limit',
            'icon' => 'globe',
        ],
        [
            'key' => 'analytics',
            'label' => 'Erweiterte Analytics',
            'value' => false,
            'type' => 'boolean',
            'icon' => 'chart',
        ],
        [
            'key' => 'support',
            'label' => 'Support-Level',
            'value' => 'Email',
            'type' => 'text',
            'icon' => 'headset',
        ],
    ],
]);
```

**Vorteile:**
- Strukturiert
- Icons können in Frontend genutzt werden
- Einfach zu filtern (z.B. nur "boolean" Features)

---

### 🟢 13. ENV-Variablen nicht dokumentiert

**Problem:**
- Keine `.env.example` Dokumentation für neue Variablen
- Entwickler wissen nicht, welche Stripe-Keys nötig sind

**Lösung:**
```bash
# .env.example

# Stripe Configuration (Required for Subscriptions)
STRIPE_KEY=pk_test_xxx
STRIPE_SECRET=sk_test_xxx
STRIPE_WEBHOOK_SECRET=whsec_xxx

# Cashier Currency
CASHIER_CURRENCY=eur

# Google OAuth (Required for Platform Integration)
GOOGLE_CLIENT_ID=xxx.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=xxx
GOOGLE_REDIRECT_URI=${APP_URL}/platforms/callback/google

# Trial Period (Days)
TRIAL_DAYS=30
```

---

### 🟢 14. User-Delete fehlt Cascading

**Datei:** User-Delete im Admin

**Problem:**
```php
// Admin/UserController@destroy
public function destroy(User $user)
{
    $user->delete();

    // ❌ Was passiert mit:
    // - ConnectedPlatforms? (Cascade ✓ via Migration)
    // - Cashier Subscriptions? (Bleiben in Stripe!)
    // - PromoCodeUsages? (Foreign Key Constraint Error!)
}
```

**Lösung:**
```php
public function destroy(User $user)
{
    try {
        DB::transaction(function () use ($user) {
            // 1. Cancel Stripe Subscription
            if ($user->subscribed('default')) {
                $user->subscription('default')->cancelNow();
            }

            // 2. Delete Stripe Customer (optional)
            if ($user->hasStripeId()) {
                $user->deleteStripeAccount();
            }

            // 3. Delete related data
            $user->promoCodeUsages()->delete();
            $user->connectedPlatforms()->delete(); // Redundant, aber explizit

            // 4. Delete user
            $user->delete();
        });

        return redirect()->route('admin.users.index')
            ->with('success', 'Benutzer wurde gelöscht.');
    } catch (\Exception $e) {
        return back()->with('error', 'Fehler beim Löschen: ' . $e->getMessage());
    }
}
```

---

### 🟢 15. Checkout: Keine Ladeanzeige

**Datei:** `resources/js/pages/Subscription/Checkout.vue`

**Problem:**
```vue
<Button @click="handleSubmit">
    Jetzt kaufen
</Button>
```

**Problem:**
- Stripe-Zahlung kann 3-10 Sekunden dauern
- Kein Loading-State
- User klickt mehrfach → Mehrfache Subscriptions!

**Lösung:**
```vue
<script setup>
import { ref } from 'vue';
const submitting = ref(false);

const handleSubmit = async () => {
    if (submitting.value) return; // Prevent double-click

    submitting.value = true;

    try {
        // Stripe payment
        await submitPayment();
    } finally {
        submitting.value = false;
    }
};
</script>

<template>
    <Button
        @click="handleSubmit"
        :disabled="submitting"
    >
        <LoaderCircle v-if="submitting" class="mr-2 h-4 w-4 animate-spin" />
        {{ submitting ? 'Wird verarbeitet...' : 'Jetzt kaufen' }}
    </Button>
</template>
```

---

### 🟢 16. Keine Logging für kritische Aktionen

**Problem:**
- Keine Logs für:
  - Admin-Actions (User löschen, Plan ändern)
  - Subscription-Änderungen
  - Promo-Code-Nutzung

**Lösung:**
```php
// Admin/UserController@destroy
Log::info('Admin deleted user', [
    'admin_id' => auth()->id(),
    'admin_email' => auth()->user()->email,
    'deleted_user_id' => $user->id,
    'deleted_user_email' => $user->email,
    'timestamp' => now(),
]);

// SubscriptionController@subscribe
Log::info('Subscription created', [
    'user_id' => $user->id,
    'plan' => $plan->name,
    'price' => $finalPrice,
    'promo_code' => $promoCode?->code,
    'stripe_subscription_id' => $subscription->stripe_id ?? null,
]);
```

**Besser:** Audit-Log-Package
```bash
composer require owen-it/laravel-auditing
```

---

## Empfohlene Maßnahmen

### Priorität 1 (Sofort beheben)

1. **Admin Plan-Wechsel Fix**
   - Datei: `Admin/SubscriptionManagementController.php`
   - Stripe Subscription erstellen wenn nötig
   - Fallback: Zahlungsmethode erforderlich

2. **Manage-Seite Invoices-Fix**
   - Datei: `SubscriptionController.php:manage()`
   - Prüfen: `$user->hasStripeId()`

3. **Admin Self-Toggle-Schutz**
   - Datei: `Admin/UserController.php:toggleAdmin()`
   - Verhindere Self-Toggle

4. **Promo-Code Negativ-Preis-Fix**
   - Datei: `PromoCode.php:applyDiscount()`
   - `max(0, ...)` verwenden

### Priorität 2 (Wichtig, bald beheben)

5. **OAuth Token-Refresh implementieren**
   - Datei: `ConnectedPlatform.php`
   - Methode: `refreshAccessToken()`

6. **User.plan_id NOT NULL**
   - Migration: plan_id required mit Default
   - Null-Checks in Modell

7. **Promo-Code Race Condition**
   - `lockForUpdate()` + Transaction

8. **Subscription Downgrade zu Free**
   - Webhook oder Scheduled Job
   - `plan_id` aktualisieren

### Priorität 3 (Nice-to-have)

9. **Database Indexes**
   - Migration für Performance

10. **Platform Disconnect Confirm**
    - AlertDialog in Vue

11. **Checkout Loading State**
    - Button disabled + Spinner

12. **Logging für Admin-Actions**
    - `Log::info()` oder Audit-Package

---

## Zusammenfassung

### Was funktioniert gut ✅

- **Hybrid Subscription-System**: Clever designed, beide Wege funktionieren
- **Promo-Code-System**: Flexibel, mit Admin-Exceptions
- **2FA**: Sauber implementiert mit Fortify
- **Admin-Panel**: Übersichtlich, gute UX
- **Vue-Komponenten**: Modern, wiederverwendbar
- **OAuth-Integration**: Struktur gut, erweiterbar

### Was muss verbessert werden ⚠️

- **Kritische Bugs**: Admin Plan-Wechsel, Invoices, Negativ-Preise
- **Sicherheit**: Admin Self-Toggle, User-Delete Cascading
- **Performance**: Fehlende Indexes
- **UX**: Loading States, Confirmations
- **Monitoring**: Logging für kritische Actions

### Nächste Schritte

1. Kritische Bugs fixen (Priorität 1)
2. Tests schreiben (besonders für Subscription-Flow)
3. Stripe Webhooks implementieren
4. Database Indexes hinzufügen
5. Logging/Monitoring verbessern

---

## Kontakt & Support

Bei Fragen zur Analyse oder Implementierung der Fixes:
- Erstellt am: 2025-11-06
- Dokumentation erstellt durch: Claude Code Analysis

**Wichtig:** Diese Analyse basiert auf dem aktuellen Stand des Codes. Bei Änderungen sollte die Dokumentation aktualisiert werden.
