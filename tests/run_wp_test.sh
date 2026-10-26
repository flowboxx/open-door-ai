#!/bin/bash
# AI Door: lint (PHP 7.4 and 8.3) + live run in a local WordPress (Docker). Needs agent-card-starter for --strict.
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
docker cp "$HERE" aidoor-wp-1:/var/www/html/wp-content/plugins/ai-door >/dev/null && docker exec aidoor-wp-1 chown -R www-data:www-data /var/www/html/wp-content/plugins/ai-door
w option update blogdescription "Dachdecker in Musterstadt" >/dev/null
w post create --post_type=page --post_status=publish --post_title="Leistungen" --porcelain >/dev/null
w plugin activate ai-door | grep -q Success && ok activate || bad activate
[ "$(curl -s -o /dev/null -w %{http_code} localhost:8089/.well-known/agent-card.json)" != 200 ] && ok "vor Einrichtung nicht ausgeliefert" || bad "vor Einrichtung ausgeliefert"
w option update ai_door_settings '{"company":"Meier Dach GmbH","services":"Dachsanierung\nFlachdach abdichten","contact_mail":"info@example.com","footer":0}' --format=json >/dev/null
for p in .well-known/agent-card.json .well-known/agent.json .well-known/mcp/server-card.json llms.txt; do
  [ "$(curl -s -o "$OUT/$(basename $p)" -w %{http_code} localhost:8089/$p)" = 200 ] && ok "GET $p" || bad "GET $p"; done
for f in agent-card.json agent.json server-card.json; do (cd "$VALID" && python3 -m agent_card validate --strict "$OUT/$f") | grep -q "^OK" && ok "validate --strict $f" || bad "validate $f"; done
grep -q "Flachdach abdichten" "$OUT/llms.txt" && ok "llms.txt Inhalt" || bad "llms.txt Inhalt"
U='localhost:8089/index.php?rest_route=/ai-door/v1/mcp'
curl -s -X POST -d '{"jsonrpc":"2.0","id":1,"method":"tools/call","params":{"name":"list_services"}}' "$U" | grep -q "Dachsanierung" && ok "mcp tools/call" || bad "mcp tools/call"
curl -s -X POST -d '{"jsonrpc":"2.0","id":1,"method":"initialize"}' "$U" | grep -q 2025-06-18 && ok "mcp initialize" || bad "mcp initialize"
[ "$(curl -s -o /dev/null -w %{http_code} -X POST localhost:8089/.well-known/agent-card.json)" = 405 ] && ok "POST auf Karte 405" || bad "POST auf Karte"
PID=$(w post create --post_type=page --post_status=publish --post_title=Info --post_content='<!-- wp:ai-door/ask /--> [ai_door_ask]' --porcelain)
[ "$(curl -s "localhost:8089/?page_id=$PID" | grep -o 'class="ai-door-ask"' | wc -l | tr -d ' ')" = 2 ] && ok "Block und Shortcode rendern" || bad "Block/Shortcode"
w eval 'echo WP_Block_Type_Registry::get_instance()->is_registered("ai-door/ask") ? "REG" : "NOREG";' | grep -qx REG && ok "Block registriert" || bad "Block registriert"
w --user=a eval 'AI_Door_Admin::page();' | grep -q "Save and switch on" && ok "Admin-Seite rendert" || bad "Admin-Seite"
w eval 'print_r(AI_Door_Admin::sanitize(array("company"=>"<script>x</script>Firma","contact_mail"=>"nicht")));' | grep -q "script" && bad sanitize || ok sanitize
w plugin deactivate ai-door >/dev/null
[ "$(curl -s -o /dev/null -w %{http_code} localhost:8089/.well-known/agent-card.json)" != 200 ] && [ "$(curl -s -o /dev/null -w %{http_code} -X POST -d '{}' $U)" = 404 ] && ok "Deaktivierung: Adressen weg" || bad "Deaktivierung"
w option get ai_door_settings --format=json | grep -q '"company"' && ok "Einstellungen nach Deaktivierung noch da (gewollt)" || bad "Einstellungen weg"
w plugin activate ai-door >/dev/null; w plugin uninstall ai-door --deactivate >/dev/null
w option get ai_door_settings --format=json | grep -q '"company"' && bad "uninstall raeumt nicht auf" || ok "uninstall: Option geloescht"
w i18n make-pot wp-content/plugins/ai-door /tmp/x.pot --slug=ai-door >/dev/null 2>&1
$C down -v >/dev/null 2>&1; echo "ENDE fail=$FAIL"; exit $FAIL
