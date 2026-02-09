# local_heartbeat Plugin

## Beschreibung
Dieses Plugin sendet alle 5 Minuten ein Heartbeat-Signal an BetterStack, um die Verfügbarkeit von Moodle zu überwachen.

## Installation
1. Entpacke die ZIP-Datei in den Ordner `moodle/local/`.
2. Stelle sicher, dass der Ordner `local_heartbeat` heißt.
3. Gehe zu Moodle und aktualisiere die Datenbank (Admin-Bereich).
4. Stelle sicher, dass der Moodle-Cron auf deinem Server läuft:
   - Füge in die Crontab folgende Zeile ein:
     */1 * * * * php /pfad/zu/moodle/admin/cli/cron.php

## Funktionsweise
- Das Plugin registriert einen geplanten Task, der alle 5 Minuten ausgeführt wird.
- Der Task sendet einen GET-Request an die BetterStack-Heartbeat-URL.

## Support
Bei Fragen oder Problemen bitte die Moodle-Dokumentation zu Cron-Jobs und lokalen Plugins konsultieren.
