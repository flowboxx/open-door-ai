#!/bin/bash
# Open Door: lint (PHP 7.4 and 8.3) + live run in a local WordPress (Docker). Needs agent-card-starter for --strict.
# Usage: bash tests/run_wp_test.sh [path to agent-card-starter checkout]
set -u
HERE="$(cd "$(dirname "$0")/.." && pwd)"; C="docker compose -p aidoor -f $HERE/tests/docker-compose.yml"
VALID="${1:-$HOME/flowboxx-agent-karte-oss}"; OUT="$(mktemp -d)"; FAIL=0
ok(){ echo "OK   $1"; }; bad(){ echo "FAIL $1"; FAIL=1; }
w(){ $C run --rm cli "$@" 2>&1 | grep -v '^ Container\|level=warning'; }
for v in 7.4 8.3; do docker run --rm -v "$HERE":/p:ro php:$v-cli sh -c 'for f in $(find /p -name "*.php"); do php -l $f; done' | grep -v "No syntax" && bad "lint $v" || ok "lint php $v"; done
$C up -d >/dev/null 2>&1
until curl -s -o /dev/null localhost:8089/; do sleep 2; done
until w core install --url=https://localhost:8089 --title="Meier Dach" --admin_user=a --admin_password=a1b2c3d4e5 --admin_email=a@example.com --skip-email | grep -q "Success\|already"; do sleep 3; done
docker cp "$HERE" aidoor-wp-1:/var/www/html/wp-content/plugins/open-door-ai >/dev/null && docker exec aidoor-wp-1 chown -R www-data:www-data /var/www/html/wp-content/plugins/open-door-ai
w option update blogdescription "Dachdecker in Musterstadt" >/dev/null
w post create --post_type=page --post_status=publish --post_title="Leistungen" --porcelain >/dev/null
w plugin activate open-door-ai | grep -q Success && ok activate || bad activate
[ "$(curl -s -o /dev/null -w %{http_code} localhost:8089/.well-known/agent-card.json)" != 200 ] && ok "vor Einrichtung nicht ausgeliefert" || bad "vor Einrichtung ausgeliefert"
w option update open_door_settings '{"company":"Meier Dach GmbH","services":"Dachsanierung\nFlachdach abdichten","contact_mail":"info@example.com","footer":0}' --format=json >/dev/null
for p in .well-known/agent-card.json .well-known/agent.json .well-known/mcp/server-card.json llms.txt; do
  [ "$(curl -s -o "$OUT/$(basename $p)" -w %{http_code} localhost:8089/$p)" = 200 ] && ok "GET $p" || bad "GET $p"; done
for f in agent-card.json agent.json server-card.json; do (cd "$VALID" && python3 -m agent_card validate --strict "$OUT/$f") | grep -q "^OK" && ok "validate --strict $f" || bad "validate $f"; done
grep -q "Flachdach abdichten" "$OUT/llms.txt" && ok "llms.txt Inhalt" || bad "llms.txt Inhalt"
U='localhost:8089/index.php?rest_route=/open-door-ai/v1/mcp'
curl -s -X POST -d '{"jsonrpc":"2.0","id":1,"method":"tools/call","params":{"name":"list_services"}}' "$U" | grep -q "Dachsanierung" && ok "mcp tools/call" || bad "mcp tools/call"
curl -s -X POST -d '{"jsonrpc":"2.0","id":1,"method":"initialize"}' "$U" | grep -q 2025-06-18 && ok "mcp initialize" || bad "mcp initialize"
[ "$(curl -s -o /dev/null -w %{http_code} -X POST localhost:8089/.well-known/agent-card.json)" = 405 ] && ok "POST auf Karte 405" || bad "POST auf Karte"
PID=$(w post create --post_type=page --post_status=publish --post_title=Info --post_content='<!-- wp:open-door-ai/ask /--> [open_door_ask]' --porcelain)
[ "$(curl -s "localhost:8089/?page_id=$PID" | grep -o 'class="open-door-ai-ask"' | wc -l | tr -d ' ')" = 2 ] && ok "Block und Shortcode rendern" || bad "Block/Shortcode"
w eval 'echo WP_Block_Type_Registry::get_instance()->is_registered("open-door-ai/ask") ? "REG" : "NOREG";' | grep -qx REG && ok "Block registriert" || bad "Block registriert"
w --user=a eval 'Open_Door_Admin::page();' | grep -q "Save and switch on" && ok "Admin-Seite rendert" || bad "Admin-Seite"
w eval 'print_r(Open_Door_Admin::sanitize(array("company"=>"<script>x</script>Firma","contact_mail"=>"nicht")));' | grep -q "script" && bad sanitize || ok sanitize
# Anfragen-Dienst (0.3.0): gegen einen Stand-in-Dienst auf dem Host, nichts geht ins Netz
ST="$OUT/stub_state"; SL="$OUT/stub_log"; echo ausstehend > "$ST"; : > "$SL"
docker run -d --rm --name aidoor-stub --network aidoor_default -v "$HERE/tests":/t:ro -v "$OUT":/o python:3.12-alpine python /t/service_stub.py 8765 /o/stub_state /o/stub_log >/dev/null
docker exec aidoor-wp-1 mkdir -p /var/www/html/wp-content/mu-plugins
docker exec aidoor-wp-1 sh -c "echo \"<?php add_filter('open_door_service_url', function(){ return 'http://aidoor-stub:8765/open-door'; });\" > /var/www/html/wp-content/mu-plugins/stub.php"
sleep 1
w --user=a eval 'Open_Door_Admin::page();' | grep -q "Register for the inquiry service" && ok "Dienst-Box: Anmelden-Knopf" || bad "Dienst-Box"
w --user=a eval 'Open_Door_Admin::page();' | grep -q 'name="open_door_avv"' && ok "Dienst-Box: AVV-Haekchen" || bad "AVV-Haekchen"
w eval 'echo Open_Door_Service::is_registered() ? "JA" : "NEIN";' | grep -qx NEIN && ok "vor Anmeldung nicht registriert" || bad "vor Anmeldung"
[ ! -s "$SL" ] && ok "vor Anmeldung nichts gesendet" || bad "vor Anmeldung gesendet"
w eval 'var_dump(Open_Door_Service::register());' | grep -q "bool(true)" && ok "Anmeldung" || bad "Anmeldung"
grep -q '"mail":"info@example.com"' "$SL" && grep -q 'site_url' "$SL" && grep -q '"avv":"2026-10-31-1"' "$SL" && ok "Anmeldung sendet nur Adresse und Mail" || bad "Anmelde-Inhalt"
curl -s -X POST -d '{"jsonrpc":"2.0","id":1,"method":"tools/list"}' "$U" | grep -q leave_inquiry && bad "Werkzeug vor Aktivierung sichtbar" || ok "Werkzeug erst mit aktivem Abo"
echo aktiv > "$ST"; w transient delete open_door_service_state >/dev/null 2>&1
curl -s -X POST -d '{"jsonrpc":"2.0","id":1,"method":"tools/list"}' "$U" | grep -q leave_inquiry && ok "Werkzeug mit aktivem Abo sichtbar" || bad "Werkzeug nicht sichtbar"
curl -s -X POST -d '{"jsonrpc":"2.0","id":2,"method":"tools/call","params":{"name":"leave_inquiry","arguments":{"request":"Dach undicht, bitte Rueckruf","contact":"kunde@example.com"}}}' "$U" | grep -q "handed to a person" && ok "Anfrage weitergegeben" || bad "Anfrage"
grep -q 'Dach undicht' "$SL" && grep -q '"quelle":"AI assistant"' "$SL" && ok "Dienst hat die Anfrage" || bad "Dienst ohne Anfrage"
echo pausiert > "$ST"; w transient delete open_door_service_state >/dev/null 2>&1
curl -s -X POST -d '{"jsonrpc":"2.0","id":3,"method":"tools/call","params":{"name":"leave_inquiry","arguments":{"request":"x","contact":"y"}}}' "$U" | grep -q "Unknown tool" && ok "pausiert: Werkzeug gesperrt" || bad "pausiert"
w eval 'Open_Door_Service::forget(); echo Open_Door_Service::is_registered() ? "JA" : "NEIN";' | grep -qx NEIN && ok "Verbindung entfernen" || bad "Verbindung entfernen"
docker rm -f aidoor-stub >/dev/null 2>&1
w plugin deactivate open-door-ai >/dev/null
[ "$(curl -s -o /dev/null -w %{http_code} localhost:8089/.well-known/agent-card.json)" != 200 ] && [ "$(curl -s -o /dev/null -w %{http_code} -X POST -d '{}' $U)" = 404 ] && ok "Deaktivierung: Adressen weg" || bad "Deaktivierung"
w option get open_door_settings --format=json | grep -q '"company"' && ok "Einstellungen nach Deaktivierung noch da (gewollt)" || bad "Einstellungen weg"
w plugin activate open-door-ai >/dev/null; w plugin uninstall open-door-ai --deactivate >/dev/null
w option get open_door_settings --format=json | grep -q '"company"' && bad "uninstall raeumt nicht auf" || ok "uninstall: Option geloescht"
w i18n make-pot wp-content/plugins/open-door-ai /tmp/x.pot --slug=open-door-ai >/dev/null 2>&1
$C down -v >/dev/null 2>&1; echo "ENDE fail=$FAIL"; exit $FAIL
