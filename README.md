# Nova AI Brainpool

**Nova AI Brainpool** ist ein leichtgewichtiges WordPress-Plugin für einen modernen KI-Chatbot, der direkt mit [Ollama](https://ollama.com/) und lokalen Modellen wie Zephyr kommuniziert.  
Das Plugin nutzt eine einfache .env-Konfiguration, bietet ein cleanes Dark-Theme-Chat-Frontend und ist für schnelle Live-Interaktion auf deiner Webseite gemacht.

---

## Features

- **KI-Chat mit Ollama:** Kommuniziert mit jedem Ollama-kompatiblen Modell (z.B. Zephyr, Mistral, LLaVA)
- **.env Unterstützung:** Flexibles Backend-Setup für API-URL und Modellname
- **Einfacher Shortcode:** Chat-Widget überall per `[nova_ai_chat]` einbindbar
- **Responsive & Modernes Design:** Sieht auf Desktop und Mobile elegant aus
- **Keine Cloud-Abhängigkeit:** Komplett offline-fähig mit lokalem Ollama-Server
- **Docker- und Reverse-Proxy-tauglich:** Optimal für Container-Setups

---

## Installation

1. **Plugin-Ordner kopieren:**  
   Lege den Inhalt dieses Repos als `nova-ai-brainpool` im WordPress-Plugin-Verzeichnis ab (`wp-content/plugins/nova-ai-brainpool`).

2. **.env-Datei anlegen:**  
   Im Plugin-Ordner eine Datei `.env` anlegen, z.B.:


mit Inhalt:
OLLAMA_URL=http://127.0.0.1:11434/api/chat
OLLAMA_MODEL=zephyr


> **Hinweis:**  
> Nutze bei Docker-Setups die Docker-Bridge-IP (`172.17.0.1`) und nicht `localhost`!

3. **Plugin aktivieren:**  
Gehe in dein WordPress-Backend zu **Plugins** und aktiviere "Nova AI Brainpool".

4. **Shortcode verwenden:**  
Füge `[nova_ai_chat]` in einen beliebigen Beitrag oder eine Seite ein, um das Chat-Widget zu nutzen.

---

## Beispiel-Konfiguration (.env)

```env
# Ollama API Endpoint
OLLAMA_URL=http://172.17.0.1:11434/api/chat

# Modellname (z.B. zephyr, mistral, llava)
OLLAMA_MODEL=zephyr

FAQ
Funktioniert das Plugin mit Ollama 0.7+ und Zephyr?
Ja! Das Plugin kann mit jedem Ollama-Streaming-Modell genutzt werden.

Kann ich mehrere Chats gleichzeitig führen?
Pro Seite wird ein Chat-Fenster angezeigt. Parallele Sessions laufen unabhängig in verschiedenen Browser-Tabs.

Mein Docker-Setup spricht nicht mit dem Host?
Stelle sicher, dass du die Bridge-IP (z.B. 172.17.0.1) statt localhost nutzt, damit der Docker-Container den Host erreicht.

Support & Kontakt
Fragen, Feature-Requests oder Bugreports?

admin@derleiti.de
https://derleiti.de

Lizenz
GPLv2 oder später – Feel free to use, fork, hack, verbessern, eigene Themes bauen!


