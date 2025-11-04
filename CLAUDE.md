- ich möchte sowas bauen  1. Kern-Features

  ✅ Multi-Tenant System (Hauptkonto + Unterkonten)
  - Laravel unterstützt das sehr gut mit Relationships (User -> Company -> SubUsers)
  - Rollen & Permissions (z.B. mit Spatie Permission Package)

  ✅ API Integrationen (Google, Trustpilot, etc.)
  - Google My Business API
  - Trustpilot API
  - Eventuell Webhooks für Echtzeit-Updates

  ✅ Bewertungs-Management
  - Antworten auf Reviews
  - Interne Notizen
  - Status-Tracking (beantwortet/offen/archiviert)

  ✅ Statistiken & Analytics
  - Dashboard mit Charts (z.B. Chart.js, ApexCharts)
  - Durchschnittsbewertungen
  - Trends über Zeit

  ✅ Abo-System & Zahlungen
  - Laravel Cashier (für Stripe oder Paddle)
  - Verschiedene Pläne (Basic, Pro, Enterprise)
  - Limits pro Plan (z.B. 3 Unterkonten im Basic, 10 im Pro)

  2. Technische Architektur

  User (Cheff/Owner)
    └── Company/Organization
         ├── Subscription (Abo-Plan)
         ├── SubUsers (Mitarbeiter mit Rollen)
         ├── Connected Platforms (Google, Trustpilot, etc.)
         └── Reviews
              ├── Responses
              └── Notes

  3. Empfohlene Laravel Packages

  - Spatie Permission - Rollen & Rechte
  - Laravel Cashier - Stripe/Paddle Integration
  - Laravel Horizon - Queue Management (für API Calls)
  - Laravel Sanctum - API Authentication
  - Spatie Laravel Analytics - für eigene Statistiken

  4. Herausforderungen

  ⚠️ API Rate Limits - Google & Co haben Limits
  ⚠️ OAuth/API Keys - Jeder Service hat eigene Auth
  ⚠️ Webhook Handling - Für Echtzeit-Updates
  ⚠️ Compliance - DSGVO, da du Kundendaten verarbeitest
- schreibe mit js statt ts