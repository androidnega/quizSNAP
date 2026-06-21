#!/usr/bin/env bash
# Fix Reverb spawn errors when supervisor still points at /var/www/quizsnap.
# Run on server: cd /srv/apps/quizsnap && sudo bash scripts/vps/fix-reverb-supervisor.sh
set -euo pipefail

APP_DIR="/srv/apps/quizsnap"
CONF="/etc/supervisor/conf.d/quizsnap.conf"
REVERB_PORT="${REVERB_PORT:-8080}"

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
if grep -q '^REVERB_APP_KEY=CHANGE_ME' .env || grep -q '^REVERB_APP_SECRET=CHANGE_ME' .env; then
  echo "WARN: REVERB_APP_KEY / REVERB_APP_SECRET are still placeholders — generate real keys:"
  echo "  REVERB_APP_KEY=$(openssl rand -hex 16)"
  echo "  REVERB_APP_SECRET=$(openssl rand -hex 32)"
fi

port_listener() {
  ss -ltnp 2>/dev/null | grep ":${REVERB_PORT} " || lsof -iTCP:"${REVERB_PORT}" -sTCP:LISTEN 2>/dev/null || true
}

echo "==> Checking port ${REVERB_PORT}..."
LISTENER="$(port_listener)"
if [[ -n "$LISTENER" ]]; then
  echo "Port ${REVERB_PORT} is already in use:"
  echo "$LISTENER"
  if echo "$LISTENER" | grep -qiE 'reverb|artisan|php'; then
    echo "Reverb (or PHP) is already listening — skipping startup test."
  else
    echo "WARN: another process owns port ${REVERB_PORT}. Stop it or change REVERB_SERVER_PORT."
  fi
else
  echo "==> Testing Reverb as www-data (5s)..."
  if sudo -u www-data timeout 5 php "$APP_DIR/artisan" reverb:start --host=127.0.0.1 --port="${REVERB_PORT}"; then
    echo "Reverb started cleanly."
  else
    code=$?
    if [[ "$code" -eq 124 ]]; then
      echo "Reverb test OK (timed out after 5s as expected)."
    else
      echo "Reverb test failed — see output above and: tail -50 /var/log/supervisor/quizsnap-reverb.log"
      exit "$code"
    fi
  fi
fi

supervisorctl reread
supervisorctl update

# If port is free, supervisor should start Reverb. If something else already runs Reverb, avoid crash loop.
if [[ -n "$LISTENER" ]] && echo "$LISTENER" | grep -qiE 'reverb|artisan|php'; then
  supervisorctl stop quizsnap-reverb 2>/dev/null || true
  echo "Left existing Reverb process running on port ${REVERB_PORT}."
else
  supervisorctl restart quizsnap-reverb 2>/dev/null || true
fi

supervisorctl restart quizsnap-worker 2>/dev/null || true

echo ""
supervisorctl status | grep quizsnap || true
