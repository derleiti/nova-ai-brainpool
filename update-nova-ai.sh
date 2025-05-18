#!/bin/bash
echo "🧠 Nova AI Brainpool Update gestartet..."

PLUGIN_DIR="$(dirname "$(realpath "$0")")"
BACKUP_DIR="$HOME/nova-ai-brainpool-backup-$(date +%Y%m%d_%H%M%S)"
TMP_DIR="/tmp/nova-ai-update-tmp"

echo "📦 Backup des aktuellen Projekts wird gespeichert unter: $BACKUP_DIR"
mkdir -p "$BACKUP_DIR"
cp -r "$PLUGIN_DIR"/* "$BACKUP_DIR"/

echo "⬇ Klone neueste Version aus dem Repo..."
rm -rf "$TMP_DIR"
git clone https://github.com/derleiti/nova-ai-brainpool.git "$TMP_DIR"

if [ ! -d "$TMP_DIR" ]; then
  echo "❌ Fehler beim Klonen! Abbruch."
  exit 1
fi

echo "🔄 Aktualisiere Plugin-Dateien..."
cp -r "$TMP_DIR/"* "$PLUGIN_DIR"/

echo "🧹 Bereinige temporäre Dateien..."
rm -rf "$TMP_DIR"

echo "✅ Update abgeschlossen. Bitte Plugin im WordPress Backend neu laden oder reaktivieren."
