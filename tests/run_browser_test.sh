#!/bin/bash
# Open Door: browser test (headless Chromium, Playwright) against a local WordPress behind HTTPS (Caddy).
# Needs Docker and python3 with playwright (macOS: /usr/bin/python3). Plugin is copied in with docker cp.
set -u
HERE="$(cd "$(dirname "$0")/.." && pwd)"; C="docker compose -p aidoorb -f $HERE/tests/docker-compose.browser.yml"
w(){ $C run --rm cli "$@" 2>&1 | grep -v '^ Container\|level=warning'; }
$C up -d wp db proxy >/dev/null 2>&1
until docker exec aidoorb-wp-1 sh -c 'test -f /var/www/html/wp-config.php' 2>/dev/null; do sleep 2; done
until w core install --url=https://localhost:8443 --title="Meier Dach" --admin_user=a --admin_password=a1b2c3d4e5 --admin_email=a@example.com --skip-email | grep -q "Success\|already"; do sleep 3; done
docker cp "$HERE" aidoorb-wp-1:/var/www/html/wp-content/plugins/open-door-ai >/dev/null && docker exec aidoorb-wp-1 chown -R www-data:www-data /var/www/html/wp-content/plugins/open-door-ai
w option update blogdescription "Dachdecker in Musterstadt" >/dev/null
w plugin activate open-door-ai | grep -q Success || { echo "FAIL activate"; $C down -v >/dev/null 2>&1; exit 1; }
until curl -sk -o /dev/null https://localhost:8443/; do sleep 2; done
SHOT_DIR="${SHOT_DIR:-/tmp}" /usr/bin/python3 "$HERE/tests/browser_test.py"; RC=$?
$C down -v >/dev/null 2>&1
exit $RC
