# 🤖 Ollama Integration - Lokale AI ohne Kosten

RatingsHub unterstützt jetzt **Ollama** - eine kostenlose, lokale AI-Lösung als Alternative zu OpenAI.

## 🎯 Warum Ollama?

### Vorteile:
- ✅ **100% kostenlos** - Keine API-Kosten
- ✅ **Keine Rate Limits** - Unbegrenzte Anfragen
- ✅ **Datenschutz** - Daten verlassen niemals deinen Server
- ✅ **Offline-fähig** - Funktioniert ohne Internetverbindung
- ✅ **Schnell** - Direkte Antworten ohne Netzwerk-Latenz

### Nachteile:
- ⚠️ **CPU/RAM** - Benötigt mehr Server-Ressourcen
- ⚠️ **Qualität** - Etwas weniger akkurat als GPT-4 (aber gut genug!)
- ⚠️ **Download** - Modell-Download einmalig ~2-3GB

---

## 🚀 Setup

### 1. Ollama Container starten

Ollama ist bereits in `docker-compose.yml` konfiguriert:

```bash
docker-compose up -d ollama
```

### 2. Modell herunterladen

Nach dem Start musst du einmalig das Sprachmodell herunterladen:

```bash
# Empfohlen: Llama 3.2 (3GB, sehr gut für Deutsch)
docker-compose exec ollama ollama pull llama3.2

# Alternative: Mistral (4GB, auch sehr gut)
docker-compose exec ollama ollama pull mistral

# Alternative: Phi-3 (2.3GB, kleiner, etwas schwächer)
docker-compose exec ollama ollama pull phi3
```

**Achtung:** Der Download dauert 5-10 Minuten je nach Internetgeschwindigkeit.

### 3. AI Provider in .env setzen

```bash
# .env
AI_PROVIDER=ollama
OLLAMA_HOST=http://ollama:11434
OLLAMA_MODEL=llama3.2
```

### 4. Config Cache leeren

```bash
docker-compose exec app php artisan config:clear
```

**Das war's!** Die AI-Integration funktioniert jetzt mit Ollama statt OpenAI.

---

## 🔧 Verwendung

Die Verwendung ist **identisch** zu OpenAI:

1. Gehe zu einem Review im Dashboard
2. Klicke auf **"Antworten"**
3. Wähle deinen Stil (Professional, Friendly, etc.)
4. Klicke auf **"Antwort mit AI generieren"**
5. Die Antwort wird lokal mit Ollama generiert

---

## 🔄 Wechsel zwischen OpenAI und Ollama

Du kannst jederzeit zwischen den Providern wechseln:

### Zu OpenAI wechseln:

```bash
# .env
AI_PROVIDER=openai
OPENAI_API_KEY=sk-proj-...
```

```bash
docker-compose exec app php artisan config:clear
```

### Zu Ollama wechseln:

```bash
# .env
AI_PROVIDER=ollama
```

```bash
docker-compose exec app php artisan config:clear
```

---

## 📊 Verfügbare Modelle

### Llama 3.2 (Empfohlen)
- **Größe:** 3GB
- **Sprachen:** Deutsch, Englisch, viele mehr
- **Qualität:** ⭐⭐⭐⭐⭐ (sehr gut)
- **Geschwindigkeit:** ⭐⭐⭐⭐ (schnell)
```bash
docker-compose exec ollama ollama pull llama3.2
```

### Mistral
- **Größe:** 4GB
- **Sprachen:** Deutsch, Englisch, Französisch, viele mehr
- **Qualität:** ⭐⭐⭐⭐⭐ (sehr gut)
- **Geschwindigkeit:** ⭐⭐⭐ (mittel)
```bash
docker-compose exec ollama ollama pull mistral
```

### Phi-3
- **Größe:** 2.3GB
- **Sprachen:** Englisch, Deutsch (okay)
- **Qualität:** ⭐⭐⭐ (gut)
- **Geschwindigkeit:** ⭐⭐⭐⭐⭐ (sehr schnell)
```bash
docker-compose exec ollama ollama pull phi3
```

### Gemma 2
- **Größe:** 5GB
- **Sprachen:** Viele Sprachen
- **Qualität:** ⭐⭐⭐⭐⭐ (sehr gut)
- **Geschwindigkeit:** ⭐⭐⭐ (mittel)
```bash
docker-compose exec ollama ollama pull gemma2
```

---

## 🛠️ Verwaltung

### Modell wechseln

```bash
# .env
OLLAMA_MODEL=mistral  # oder llama3.2, phi3, gemma2, etc.
```

```bash
docker-compose exec app php artisan config:clear
```

### Verfügbare Modelle anzeigen

```bash
docker-compose exec ollama ollama list
```

### Modell löschen (Speicherplatz freigeben)

```bash
docker-compose exec ollama ollama rm mistral
```

### Ollama-Status prüfen

```bash
# Container-Status
docker-compose ps ollama

# Ollama-Logs
docker-compose logs -f ollama
```

---

## 💡 Tipps & Tricks

### Performance-Optimierung

**1. Model-Größe vs. Qualität:**
- Kleine Modelle (phi3): Schnell, weniger RAM, etwas schwächere Qualität
- Große Modelle (gemma2): Langsamer, mehr RAM, bessere Qualität
- **Sweet Spot:** llama3.2 (3GB) - Beste Balance

**2. Hardware-Anforderungen:**

| Modell | RAM (min) | CPU | Geschwindigkeit |
|--------|-----------|-----|-----------------|
| phi3 | 4GB | 2 Cores | 2-3 Sek. |
| llama3.2 | 6GB | 2 Cores | 3-5 Sek. |
| mistral | 8GB | 4 Cores | 5-8 Sek. |
| gemma2 | 10GB | 4 Cores | 8-12 Sek. |

**3. GPU-Beschleunigung (Optional):**

Falls du eine NVIDIA GPU hast, kann Ollama diese nutzen:

```yaml
# docker-compose.yml
services:
  ollama:
    image: ollama/ollama:latest
    deploy:
      resources:
        reservations:
          devices:
            - driver: nvidia
              count: 1
              capabilities: [gpu]
```

---

## 🐛 Troubleshooting

### Fehler: "Ollama API Fehler: Connection refused"

**Lösung:**

```bash
# Prüfe ob Container läuft
docker-compose ps ollama

# Wenn nicht, starte ihn
docker-compose up -d ollama
```

### Fehler: "model 'llama3.2' not found"

**Lösung:**

```bash
# Modell herunterladen
docker-compose exec ollama ollama pull llama3.2
```

### Langsame Antworten (>30 Sekunden)

**Ursachen:**
- Zu wenig RAM (Server swapped)
- CPU überlastet
- Modell zu groß für deine Hardware

**Lösungen:**
1. Verwende kleineres Modell (phi3 statt llama3.2)
2. Erhöhe Server-RAM
3. Reduziere andere Container/Prozesse

### Antworten auf Englisch statt Deutsch

**Lösung:**

Das liegt am Modell. Llama 3.2 und Mistral unterstützen Deutsch sehr gut.

Stelle sicher:
- Modell ist llama3.2 oder mistral
- System-Prompt enthält "Sprache: Deutsch" (ist bereits so konfiguriert)

```bash
# Richtiges Modell verwenden
docker-compose exec ollama ollama pull llama3.2
```

```bash
# .env
OLLAMA_MODEL=llama3.2
```

---

## 💰 Kosten-Vergleich

### OpenAI (GPT-4o-mini)
- ✅ Beste Qualität
- ✅ Keine Server-Last
- ❌ **~0.001€ pro Antwort**
- ❌ **~10€/Monat** bei 10.000 Antworten
- ❌ Rate Limits (Free Tier: 3/Minute)

### Ollama (llama3.2)
- ✅ **100% kostenlos**
- ✅ Keine Rate Limits
- ✅ Datenschutz
- ❌ Server-Ressourcen (RAM/CPU)
- ❌ Etwas schwächere Qualität (~90% von GPT-4)

### Empfehlung:

- **Entwicklung:** Ollama (kostenlos, keine Limits)
- **Production (wenig Traffic):** Ollama (kostenlos)
- **Production (viel Traffic):** OpenAI (besser skalierbar, weniger Server-Last)

---

## 📚 Weitere Infos

- [Ollama Dokumentation](https://ollama.com/library)
- [Verfügbare Modelle](https://ollama.com/library)
- [Ollama GitHub](https://github.com/ollama/ollama)
- [Llama 3.2 Info](https://ollama.com/library/llama3.2)

---

## 🎯 Zusammenfassung

**Setup in 3 Schritten:**

```bash
# 1. Container starten
docker-compose up -d ollama

# 2. Modell herunterladen
docker-compose exec ollama ollama pull llama3.2

# 3. Provider setzen
echo "AI_PROVIDER=ollama" >> .env
docker-compose exec app php artisan config:clear
```

**Fertig!** Jetzt kannst du unbegrenzt kostenlose AI-Antworten generieren! 🚀
