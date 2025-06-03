# Nova AI Brainpool

**Nova AI Brainpool** ist ein leichtgewichtiges WordPress-Plugin für einen modernen KI-Chatbot, der direkt mit [Ollama](https://ollama.com/) und lokalen Modellen wie Zephyr kommuniziert.  
Das Plugin nutzt eine einfache .env-Konfiguration, bietet ein cleanes Dark-Theme-Chat-Frontend und ist für schnelle Live-Interaktion auf deiner Webseite gemacht.

---

## Features

- **KI-Chat mit Ollama:** Kommuniziert mit jedem Ollama-kompatiblen Modell (z.B. Zephyr, Mistral, LLaVA)
- **.env Unterstützung:** Flexibles Backend-Setup für API-URL und Modellname
- **Einfacher Shortcode:** Chat-Widget überall per `[nova_ai_chat]` einbindbar
- **Responsive & Modernes Design:** Sieht auf Desktop und Mobile elegant aus
- **WordPress-sicher:** Nonce-Validierung, Sanitization und AJAX-Integration
- **Admin-Interface:** Vollständige Verwaltung über WordPress-Backend
- **Keine Cloud-Abhängigkeit:** Komplett offline-fähig mit lokalem Ollama-Server
- **Docker- und Reverse-Proxy-tauglich:** Optimal für Container-Setups

---

## Installation

### 1. Plugin installieren

**Option A: Upload über WordPress-Backend**
1. Lade das gesamte Plugin als ZIP herunter
2. Gehe zu **Plugins → Installieren → Plugin hochladen**
3. Wähle die ZIP-Datei aus und klicke "Jetzt installieren"
4. Aktiviere das Plugin

**Option B: Manueller Upload**
1. Lade den Plugin-Ordner `nova-ai-brainpool` ins WordPress-Plugin-Verzeichnis hoch (`wp-content/plugins/nova-ai-brainpool`)
2. Gehe zu **Plugins** und aktiviere "Nova AI Brainpool"

### 2. .env-Datei konfigurieren

Im Plugin-Ordner eine Datei `.env` anlegen:

```env
# Ollama API Endpoint
OLLAMA_URL=http://127.0.0.1:11434/api/chat

# Modellname (z.B. zephyr, mistral, llava)
OLLAMA_MODEL=zephyr
```

> **Docker-Setup Hinweis:**  
> Nutze bei Docker-Setups die Docker-Bridge-IP (z.B. `172.17.0.1`) und nicht `localhost`!

### 3. Admin-Konfiguration

1. Gehe zu **Nova AI** im WordPress-Backend
2. Überprüfe die .env-Konfiguration
3. Passe bei Bedarf den System-Prompt unter **Zephyr Admin → Prompts** an
4. Teste die Verbindung

### 4. Chat einbinden

Füge `[nova_ai_chat]` in einen beliebigen Beitrag oder eine Seite ein, um das Chat-Widget zu nutzen.

**Shortcode-Optionen:**
```
[nova_ai_chat height="500px" placeholder="Deine Nachricht..."]
```

---

## Konfiguration

### .env-Datei Beispiele

**Standard Ollama (lokal):**
```env
OLLAMA_URL=http://127.0.0.1:11434/api/chat
OLLAMA_MODEL=zephyr
```

**Docker-Setup:**
```env
OLLAMA_URL=http://172.17.0.1:11434/api/chat
OLLAMA_MODEL=mistral
```

**Remote Ollama:**
```env
OLLAMA_URL=http://dein-server.de:11434/api/chat
OLLAMA_MODEL=llama2
```

### Admin-Interface

Das Plugin bietet ein vollständiges Admin-Interface mit folgenden Bereichen:

#### Nova AI → Haupteinstellungen
- .env-Status überprüfen
- Crawler-URLs verwalten
- Knowledge Base Import/Export

#### Nova AI → Zephyr Admin Console
- **KI Provider & Modell:** API-URL und Modell konfigurieren
- **Prompts:** System-Prompt für die KI anpassen
- **Crawler:** URLs für automatisches Crawling verwalten
- **Import/Export:** Konfiguration sichern und übertragen
- **Debug:** System-Informationen und Fehlerdiagnose

### System-Prompt anpassen

Standardprompt: `Du bist Nova, ein freundlicher KI-Assistent für Markus und Gäste.`

Du kannst den Prompt über **Zephyr Admin → Prompts** anpassen, z.B.:
```
Du bist Nova, ein hilfreicher KI-Assistent für unsere Website. 
Beantworte Fragen höflich und kompetent. 
Falls du etwas nicht weißt, sage es ehrlich.
```

---

## Sicherheit

Das Plugin implementiert WordPress-Sicherheitsstandards:

- **Nonce-Validierung:** Schutz vor CSRF-Angriffen
- **Sanitization:** Alle Eingaben werden bereinigt
- **Capability-Checks:** Admin-Funktionen nur für berechtigte Benutzer
- **Escape-Output:** Sichere Ausgabe von Daten
- **.env-Schutz:** Sensible Daten außerhalb des Web-Roots

### .env-Datei schützen

Füge folgende Zeile zu deiner `.htaccess` hinzu:
```apache
<Files ".env">
    Order allow,deny
    Deny from all
</Files>
```

Oder für Nginx:
```nginx
location ~ /\.env {
    deny all;
    return 404;
}
```

---

## Fehlerbehebung

### Häufige Probleme

**Chat funktioniert nicht:**
1. Prüfe .env-Konfiguration unter **Nova AI → Einstellungen**
2. Überprüfe Ollama-Server-Status: `ollama list`
3. Teste API-Verbindung mit curl:
   ```bash
   curl -X POST http://127.0.0.1:11434/api/chat \
        -H "Content-Type: application/json" \
        -d '{"model":"zephyr","messages":[{"role":"user","content":"test"}]}'
   ```

**Docker-Verbindungsprobleme:**
- Verwende `172.17.0.1` statt `localhost`
- Prüfe Docker-Netzwerk: `docker network ls`
- Stelle sicher, dass Ollama-Container läuft

**Plugin-Fehler:**
1. Aktiviere WordPress-Debug: `define('WP_DEBUG', true);`
2. Prüfe Error-Logs in `/wp-content/debug.log`
3. Verwende **Debug-Tab** im Admin-Interface

### Debug-Modus

Aktiviere WordPress-Debug in `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

---

## API-Kompatibilität

Das Plugin ist kompatibel mit:

- **Ollama 0.7+** (empfohlen)
- **OpenAI-kompatible APIs** (mit Anpassungen)
- **Lokale Modelle:** Zephyr, Mistral, LLaMA, CodeLlama, etc.
- **Custom Endpoints** über .env-Konfiguration

---

## FAQ

**Funktioniert das Plugin mit Ollama 0.7+ und Zephyr?**  
Ja! Das Plugin kann mit jedem Ollama-Streaming-Modell genutzt werden.

**Kann ich mehrere Chats gleichzeitig führen?**  
Pro Seite wird ein Chat-Fenster angezeigt. Parallele Sessions laufen unabhängig in verschiedenen Browser-Tabs.

**Mein Docker-Setup spricht nicht mit dem Host?**  
Stelle sicher, dass du die Bridge-IP (z.B. `172.17.0.1`) statt `localhost` nutzt.

**Kann ich eigene CSS-Styles verwenden?**  
Ja, du kannst die Chat-Styles über dein Theme überschreiben. Alle CSS-Klassen beginnen mit `nova-ai-`.

**Werden Chat-Verläufe gespeichert?**  
Nein, das Plugin speichert keine Chat-Verläufe. Jede Session ist isoliert.

---

## Entwicklung

### Dateistruktur
```
nova-ai-brainpool/
├── nova-ai-brainpool.php     # Haupt-Plugin-Datei
├── admin/
│   ├── env-loader.php        # .env-Datei-Handler
│   └── settings.php          # Admin-Interface (veraltet)
├── assets/
│   ├── chat-frontend.css     # Chat-Styles
│   └── chat-frontend.js      # Chat-JavaScript
├── .env                      # Konfigurationsdatei
├── .gitignore               # Git-Ignores
└── README.md                # Diese Datei
```

### Hooks & Filter

**JavaScript-Konfiguration überschreiben:**
```php
add_filter('nova_ai_chat_script_config', function($config) {
    $config['custom_option'] = 'value';
    return $config;
});
```

**CSS-Klassen erweitern:**
```php
add_filter('nova_ai_chat_css_classes', function($classes) {
    $classes[] = 'my-custom-class';
    return $classes;
});
```

---

## Support & Kontakt

**Fragen, Feature-Requests oder Bugreports?**

- 📧 E-Mail: [admin@derleiti.de](mailto:admin@derleiti.de)
- 🌐 Website: [https://derleiti.de](https://derleiti.de)
- 📋 Issues: Erstelle ein GitHub-Issue

---

## Lizenz

**GPLv2 oder später** – Feel free to use, fork, hack, verbessern und eigene Themes bauen!

---

## Changelog

### Version 1.0.4-fixed
- ✅ WordPress-Sicherheitsstandards implementiert
- ✅ Vollständiges AJAX-System mit Nonce-Validierung
- ✅ Verbesserte Asset-Einbindung
- ✅ Responsive Design optimiert
- ✅ Admin-Interface erweitert
- ✅ Debug-Funktionen hinzugefügt
- ✅ Fehlerbehandlung verbessert

### Version 1.0.3-zpadmin
- Zephyr Admin Console hinzugefügt
- Basis-Funktionalität implementiert

### Version 1.0.0
- Erste Version mit Ollama-Integration
- Basic Chat-Interface
