#!/usr/bin/env bash
# Fix Reverb spawn errors when supervisor still points at /var/www/quizsnap.
# Run on server: cd /srv/apps/quizsnap && sudo bash scripts/vps/fix-reverb-supervisor.sh
set -euo pipefail

APP_DIR="/srv/apps/quizsnap"
CONF="/etc/supervisor/conf.d/quizsnap.conf"

cd "$APP_DIR"

if [[ ! -f "$CONF" ]]; then
  echo "Installing $CONF from repo template..."
  cp "$APP_DIR/scripts/vps/supervisor-quizsnap.conf" "$CONF"
else
  echo "Patching paths in $CONF..."
  sed -i "s|/var/www/quizsnap|$APP_DIR|g" "$CONF"
fi

mkdir -p /var/log/supervisor
touch /var/log/supervisor/quizsnap-reverb.log /var/log/supervisor/quizsnap-worker.log
chown www-data:www-data /var/log/supervisor/quizsnap-*.log 2>/dev/null || true

echo "==> Checking required .env keys..."
grep -q '^BROADCAST_CONNECTION=reverb' .env || echo "WARN: set BROADCAST_CONNECTION=reverb in .env"
grep -q '^REVERB_APP_KEY=' .env || echo "WARN: set REVERB_APP_KEY in .env"
grep -q '^REVERB_APP_SECRET=' .env || echo "WARN: set REVERB_APP_SECRET in .env"

echo "==> Testing Reverb as www-data (5s)..."
if sudo -u www-data timeout 5 php "$APP_DIR/artisan" reverb:start --host=127.0.0.1 --port=8080; then
  echo "Reverb started cleanly."
else
  code=$?
  if [[ "$code" -eq 124 ]]; then
    echo "Reverb test OK (timed out after 5s as expected)."
  else
    echo "Reverb test failed — see log above. Also check: tail -50 /var/log/supervisor/quizsnap-reverb.log"
    exit "$code"
  fi
fi

supervisorctl reread
supervisorctl update
supervisorctl restart quizsnap-reverb quizsnap-worker 2>/dev/null || supervisorctl restart quizsnap-reverb 2>/dev/null || true

echo ""
supervisorctl status | grep quizsnap || true
