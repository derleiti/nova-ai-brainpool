# Nova AI Brainpool - Installations-Anleitung

## 🚀 Schritt-für-Schritt Installation

### 1. Plugin-Ordner erstellen

Erstelle den Plugin-Ordner auf deinem Server:
```bash
mkdir /path/to/wordpress/wp-content/plugins/nova-ai-brainpool
cd /path/to/wordpress/wp-content/plugins/nova-ai-brainpool
```

### 2. Dateistruktur anlegen

Erstelle die folgenden Ordner:
```bash
mkdir admin
mkdir assets
```

### 3. Dateien aus den Artifacts herunterladen

**Aus den oben bereitgestellten Artifacts lade herunter und platziere:**

```
nova-ai-brainpool/
├── nova-ai-brainpool.php          # Hauptdatei
├── admin/
│   └── env-loader.php             # .env Handler
├── assets/
│   ├── chat-frontend.css          # CSS Styles
│   └── chat-frontend.js           # JavaScript
├── .env                           # Konfiguration (von .env Beispiel)
├── .gitignore                     # Git Ignores
└── README.md                      # Dokumentation
```

### 4. Konfiguration anpassen

**a) .env-Datei erstellen:**
- Lade die ".env (Beispiel-Konfiguration)" herunter
- Benenne sie in `.env` um (ohne "Beispiel")
- Passe die Werte an deine Ollama-Installation an:

```env
OLLAMA_URL=http://127.0.0.1:11434/api/chat
OLLAMA_MODEL=zephyr
```

**b) Berechtigungen setzen:**
```bash
chmod 644 nova-ai-brainpool.php
chmod 644 admin/env-loader.php
chmod 644 assets/*
chmod 600 .env  # Nur Owner kann lesen/schreiben
```

### 5. Ollama vorbereiten

**Ollama installieren (falls noch nicht geschehen):**
```bash
# Linux/macOS
curl -fsSL https://ollama.com/install.sh | sh

# Windows: Downloade von https://ollama.com/download
```

**Modell herunterladen:**
```bash
ollama pull zephyr
# oder ein anderes Modell:
# ollama pull mistral
# ollama pull llama2
```

**Ollama starten:**
```bash
ollama serve
```

### 6. WordPress-Plugin aktivieren

1. Gehe zu deinem WordPress-Backend
2. Navigiere zu **Plugins**
3. Finde "Nova AI Brainpool" in der Liste
4. Klicke **Aktivieren**

### 7. Plugin konfigurieren

1. Gehe zu **Nova AI** im WordPress-Backend
2. Überprüfe den .env-Status (sollte "Gefunden!" anzeigen)
3. Optional: Gehe zu **Zephyr Admin** für erweiterte Einstellungen
4. Passe den System-Prompt unter **Prompts** an

### 8. Chat testen

**Shortcode in Seite/Beitrag einfügen:**
```
[nova_ai_chat]
```

**Mit Optionen:**
```
[nova_ai_chat height="600px" placeholder="Frag mich etwas..."]
```

---

## 🔧 Spezielle Setups

### Docker-Installation

**Wenn Ollama in Docker läuft:**
```env
# In .env verwende die Docker-Bridge-IP:
OLLAMA_URL=http://172.17.0.1:11434/api/chat
OLLAMA_MODEL=zephyr
```

**Docker Bridge IP finden:**
```bash
docker network inspect bridge | grep Gateway
```

### Remote Ollama-Server

**Für externen Ollama-Server:**
```env
OLLAMA_URL=http://dein-server.de:11434/api/chat
OLLAMA_MODEL=zephyr
```

### Reverse Proxy (Nginx/Apache)

**Nginx Konfiguration:**
```nginx
location /ollama/ {
    proxy_pass http://127.0.0.1:11434/;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
}
```

**Dann in .env:**
```env
OLLAMA_URL=https://deine-domain.de/ollama/api/chat
```

---

## 🛡️ Sicherheit

### .htaccess Schutz (Apache)

Erstelle `.htaccess` im Plugin-Ordner:
```apache
# Schütze sensible Dateien
<Files ".env">
    Order allow,deny
    Deny from all
</Files>

<Files "*.log">
    Order allow,deny
    Deny from all
</Files>
```

### Nginx Schutz

Füge zu deiner Nginx-Konfiguration hinzu:
```nginx
location ~ /nova-ai-brainpool/\.env {
    deny all;
    return 404;
}

location ~ /nova-ai-brainpool/.*\.log$ {
    deny all;
    return 404;
}
```

---

## ✅ Installation überprüfen

### 1. Plugin-Status
- WordPress Backend → **Nova AI** → Status sollte grün sein
- .env-Status: "Gefunden!"

### 2. Ollama-Verbindung testen
```bash
curl -X POST http://127.0.0.1:11434/api/chat \
     -H "Content-Type: application/json" \
     -d '{
       "model": "zephyr",
       "messages": [{"role": "user", "content": "Hallo"}],
       "stream": false
     }'
```

### 3. WordPress-Chat testen
- Erstelle eine Testseite mit `[nova_ai_chat]`
- Sende eine Testnachricht
- KI sollte antworten

### 4. Debug bei Problemen
- Gehe zu **Nova AI → Zephyr Admin → Debug**
- Aktiviere WordPress Debug: `define('WP_DEBUG', true);`
- Prüfe `/wp-content/debug.log`

---

## 📞 Support

Bei Problemen:
1. Prüfe die Debug-Informationen im Admin
2. Teste die Ollama-Verbindung manuell
3. Kontaktiere: admin@derleiti.de

**Häufige Fehler:**
- "Verbindungsfehler" → Ollama läuft nicht oder falsche URL
- "Modell nicht gefunden" → `ollama pull model-name`
- "Sicherheitsfehler" → Browser-Cache leeren
