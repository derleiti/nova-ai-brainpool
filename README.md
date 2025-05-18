<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Nova AI Brainpool – WordPress Plugin</title>
  <style>
    body { font-family: sans-serif; line-height: 1.6; max-width: 800px; margin: 2rem auto; padding: 0 1rem; }
    code, pre { background: #f4f4f4; padding: 4px 6px; border-radius: 3px; display: inline-block; }
    pre { display: block; padding: 1rem; overflow-x: auto; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
    h1, h2, h3 { color: #2c3e50; }
    ul { margin-bottom: 1rem; }
  </style>
</head>
<body>

<h1>🧠 Nova AI Brainpool</h1>
<p><strong>Nova AI Brainpool</strong> ist ein modernes WordPress-Plugin, das eine lokale oder API-basierte KI-Chat-Konsole direkt in deine Website integriert – voll anpassbar, datenschutzfreundlich, und mit Fokus auf AILinux-Integration.</p>

<h2>🚀 Features</h2>
<ul>
  <li>🗨️ <code>[nova_ai_chat]</code> Shortcode für die Chat-Konsole</li>
  <li>💬 Vollbild- & Floating-Chatbox (optional aktivierbar)</li>
  <li>🔐 OpenAI- oder Ollama-API-Unterstützung (z. B. <code>zephyr</code>, <code>mistral</code>)</li>
  <li>📚 Wissensdatenbank mit statischen und dynamischen Inhalten</li>
  <li>🌐 REST-API mit Endpunkten für Chat, Stats & Export</li>
  <li>🎨 Dunkel-/Hell-/Terminal-Designs + eigenes CSS</li>
  <li>📈 Statistiktracking (<code>total_chats</code>, <code>today_chats</code>)</li>
  <li>🔧 Admin-Menü für Einstellungen, Logging und Theme</li>
  <li>✅ Barrierefrei (ARIA, responsive, Tastaturfähig)</li>
</ul>

<h2>🛠️ Installation</h2>
<ol>
  <li>ZIP-Datei herunterladen und in das Plugin-Verzeichnis entpacken:<br>
  <code>wp-content/plugins/nova-ai-brainpool/</code></li>
  <li>Plugin unter <strong>Plugins &gt; Nova AI Brainpool</strong> aktivieren</li>
  <li>Gehe zu <strong>Einstellungen &gt; Nova AI</strong> und wähle den API-Typ (ollama oder openai)</li>
  <li>Setze deine API-URL und ggf. Schlüssel</li>
  <li>Füge folgenden Shortcode auf einer Seite ein:<br><code>[nova_ai_chat]</code></li>
</ol>

<h2>⚙️ REST API-Endpunkte</h2>
<table>
  <thead>
    <tr><th>Route</th><th>Methode</th><th>Beschreibung</th></tr>
  </thead>
  <tbody>
    <tr><td><code>/wp-json/nova-ai/v1/chat</code></td><td>POST</td><td>Chat mit Nova (Prompt senden)</td></tr>
    <tr><td><code>/wp-json/nova-ai/v1/chat/stats</code></td><td>POST</td><td>Statistik übermitteln</td></tr>
    <tr><td><code>/wp-json/nova-ai/v1/knowledge.json</code></td><td>GET</td><td>Wissensdatenbank exportieren</td></tr>
  </tbody>
</table>

<h2>🔧 Plugin-Optionen</h2>
<p>Folgende Werte werden in der WordPress-Datenbank gespeichert:</p>
<ul>
  <li><code>nova_ai_api_url</code>, <code>nova_ai_model</code>, <code>nova_ai_api_key</code></li>
  <li><code>nova_ai_system_prompt</code>, <code>nova_ai_temperature</code>, <code>nova_ai_max_tokens</code></li>
  <li><code>nova_ai_enable_fullsite_chat</code>, <code>nova_ai_theme_style</code>, <code>nova_ai_custom_css</code></li>
  <li><code>nova_ai_today_chats</code>, <code>nova_ai_total_chats</code>, <code>nova_ai_version</code></li>
</ul>

<h2>🧪 Beispiel: Lokale Ollama Integration</h2>
<pre><code>ollama run zephyr</code></pre>

<pre><code>
POST http://localhost:11434/api/generate
{
  "model": "zephyr",
  "prompt": "Was ist AILinux?"
}
</code></pre>

<h2>📜 Lizenz</h2>
<p>MIT License – © 2025 <a href="https://derleiti.de">derleiti.de</a><br>
Nova AI Brainpool ist unabhängig und nicht mit OpenAI oder Ollama verbunden.</p>

<h2>💡 AILinux</h2>
<p>Dieses Plugin ist Teil des AILinux-Projekts:<br>
<a href="https://ailinux.me">https://ailinux.me</a></p>

</body>
</html>
