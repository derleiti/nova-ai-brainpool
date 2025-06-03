#!/bin/bash

# Nova AI Brainpool - File Update Script
# Kopiert Dateien aus dem Hauptverzeichnis in die entsprechenden Unterordner

echo "Nova AI Brainpool - File Update Script"
echo "======================================"
echo ""

# Farben für die Ausgabe
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Funktion zum Kopieren mit Statusmeldung
copy_file() {
    local source=$1
    local dest=$2
    
    if [ -f "$source" ]; then
        cp -f "$source" "$dest"
        if [ $? -eq 0 ]; then
            echo -e "${GREEN}✓${NC} Kopiert: $source → $dest"
        else
            echo -e "${RED}✗${NC} Fehler beim Kopieren: $source → $dest"
        fi
    else
        echo -e "${RED}✗${NC} Datei nicht gefunden: $source"
    fi
}

# Admin-Dateien kopieren
echo "Kopiere Admin-Dateien..."
echo "-----------------------"
copy_file "nova-admin-console.php" "admin/class-nova-ai-admin-console.php"
copy_file "nova-env-loader.php" "admin/env-loader.php"
# settings.php scheint keine Entsprechung im Hauptverzeichnis zu haben

echo ""

# Assets-Dateien kopieren
echo "Kopiere Asset-Dateien..."
echo "-----------------------"
copy_file "nova-frontend-css.css" "assets/chat-frontend.css"
copy_file "nova-frontend-js.js" "assets/chat-frontend.js"
copy_file "nova-extended-css.css" "assets/nova-ai-extended.css"
copy_file "nova-extended-js.js" "assets/nova-ai-extended.js"

echo ""

# Includes-Dateien kopieren
echo "Kopiere Include-Dateien..."
echo "-------------------------"
copy_file "nova-core-class.php" "includes/class-nova-ai-core.php"
copy_file "nova-crawler-class.php" "includes/class-nova-ai-crawler.php"
copy_file "nova-novanet-class.php" "includes/class-nova-ai-novanet.php"
copy_file "nova-providers-class.php" "includes/class-nova-ai-providers.php"
copy_file "nova-stable-diffusion-class.php" "includes/class-nova-ai-stable-diffusion.php"

echo ""

# Backup erstellen (optional)
echo "Erstelle Backup..."
echo "-----------------"
timestamp=$(date +%Y%m%d_%H%M%S)
backup_dir="backup_$timestamp"

if [ ! -d "$backup_dir" ]; then
    mkdir "$backup_dir"
    echo -e "${GREEN}✓${NC} Backup-Verzeichnis erstellt: $backup_dir"
    
    # Kopiere die alten Dateien ins Backup
    cp -r admin "$backup_dir/" 2>/dev/null
    cp -r assets "$backup_dir/" 2>/dev/null
    cp -r includes "$backup_dir/" 2>/dev/null
    echo -e "${GREEN}✓${NC} Alte Dateien gesichert in: $backup_dir"
fi

echo ""
echo "======================================"
echo "Update abgeschlossen!"
echo ""

# Optional: Alte Dateien aus dem Hauptverzeichnis löschen
read -p "Möchten Sie die kopierten Dateien aus dem Hauptverzeichnis löschen? (j/n): " delete_choice
if [[ $delete_choice == "j" || $delete_choice == "J" ]]; then
    echo ""
    echo "Lösche kopierte Dateien aus dem Hauptverzeichnis..."
    rm -f nova-admin-console.php
    rm -f nova-env-loader.php
    rm -f nova-frontend-css.css
    rm -f nova-frontend-js.js
    rm -f nova-extended-css.css
    rm -f nova-extended-js.js
    rm -f nova-core-class.php
    rm -f nova-crawler-class.php
    rm -f nova-novanet-class.php
    rm -f nova-providers-class.php
    rm -f nova-stable-diffusion-class.php
    echo -e "${GREEN}✓${NC} Dateien gelöscht"
fi

echo ""
echo "Fertig!"
