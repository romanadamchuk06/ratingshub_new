# GitHub Secrets Setup für Automatisches Deployment

Um das automatische Deployment via GitHub Actions zu nutzen, müssen folgende Secrets konfiguriert werden:

## Erforderliche Secrets

Gehe zu: **Repository → Settings → Secrets and variables → Actions → New repository secret**

### 1. PRODUCTION_HOST
- **Beschreibung:** IP-Adresse oder Domain deines Production Servers
- **Beispiel:** `123.456.789.10` oder `server.example.com`

### 2. PRODUCTION_USER
- **Beschreibung:** SSH Username für den Server
- **Beispiel:** `root` oder `ubuntu` oder `deployer`

### 3. PRODUCTION_SSH_KEY
- **Beschreibung:** Private SSH Key für die Authentifizierung
- **Generieren:**
  ```bash
  # Auf deinem lokalen Rechner:
  ssh-keygen -t ed25519 -C "github-actions-deploy"
  
  # Private Key (für GitHub Secret):
  cat ~/.ssh/id_ed25519
  
  # Public Key (auf Server installieren):
  cat ~/.ssh/id_ed25519.pub
  ```
- **Auf Server installieren:**
  ```bash
  # SSH in deinen Server
  ssh user@your-server.com
  
  # Public Key hinzufügen
  echo "dein-public-key" >> ~/.ssh/authorized_keys
  chmod 600 ~/.ssh/authorized_keys
  ```

### 4. PRODUCTION_PORT (Optional)
- **Beschreibung:** SSH Port (Standard: 22)
- **Beispiel:** `22` oder `2222`

### 5. SLACK_WEBHOOK (Optional)
- **Beschreibung:** Slack Webhook URL für Deployment-Benachrichtigungen
- **Setup:** https://api.slack.com/messaging/webhooks

## Testen

Nach dem Setup der Secrets:

1. Push einen Commit auf `master` Branch
2. Gehe zu **Actions** Tab im Repository
3. Schaue dir den Workflow-Run an
4. Bei Erfolg sollte deine App deployed sein!

## Manuelles Triggern

Du kannst das Deployment auch manuell triggern:

1. Gehe zu **Actions** Tab
2. Wähle "Deploy to Production"
3. Klicke auf "Run workflow"
4. Wähle Branch und klicke "Run workflow"
