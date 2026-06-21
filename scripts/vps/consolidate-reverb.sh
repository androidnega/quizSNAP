#!/usr/bin/env bash
# Stop orphan Reverb, free port 8080, and run a single Supervisor-managed Reverb.
# Run on server: cd /srv/apps/quizsnap && sudo bash scripts/vps/consolidate-reverb.sh
set -euo pipefail

APP_DIR="/srv/apps/quizsnap"
CONF="/etc/supervisor/conf.d/quizsnap.conf"
REVERB_PORT="${REVERB_PORT:-8080}"

cd "$APP_DIR"

echo "==> Stopping Supervisor Reverb (avoid restart loop)..."
supervisorctl stop quizsnap-reverb 2>/dev/null || true
sleep 1

echo "==> Killing all artisan reverb:start processes..."
pkill -9 -f "artisan reverb:start" 2>/dev/null || true
sleep 2

if ss -ltnp 2>/dev/null | grep -q ":${REVERB_PORT} "; then
  echo "==> Port ${REVERB_PORT} still in use — killing listener PIDs..."
  if command -v lsof >/dev/null 2>&1; then
    lsof -t -iTCP:"${REVERB_PORT}" -sTCP:LISTEN 2>/dev/null | xargs -r kill -9
  fi
  sleep 2
fi

if ss -ltnp 2>/dev/null | grep -q ":${REVERB_PORT} "; then
  echo "ERROR: port ${REVERB_PORT} is still in use:"
  ss -ltnp | grep ":${REVERB_PORT} " || true
  echo "Check for systemd/screen/tmux jobs for user manuel: systemctl --user list-units | grep -i reverb"
  exit 1
fi

echo "Port ${REVERB_PORT} is free."

echo "==> Ensuring supervisor config uses ${APP_DIR}..."
if [[ -f "$CONF" ]]; then
  sed -i "s|/var/www/quizsnap|${APP_DIR}|g" "$CONF"
else
  cp "$APP_DIR/scripts/vps/supervisor-quizsnap.conf" "$CONF"
fi

# Prefer binding localhost; nginx proxies /app to 127.0.0.1:8080
if grep -q 'reverb:start --host=0.0.0.0' "$CONF" 2>/dev/null; then
  sed -i 's|reverb:start --host=0.0.0.0|reverb:start --host=127.0.0.1|g' "$CONF"
fi

echo "==> Optional: stop duplicate queue group (quizsnap-queue) if quizsnap-worker is already running..."
if supervisorctl status quizsnap-worker 2>/dev/null | grep -q RUNNING; then
  supervisorctl stop 'quizsnap-queue:*' 2>/dev/null || true
  echo "Stopped quizsnap-queue (keeping quizsnap-worker)."
fi

supervisorctl reread
supervisorctl update
supervisorctl start quizsnap-reverb
sleep 2

echo ""
supervisorctl status quizsnap-reverb || true
echo ""
ss -ltnp | grep ":${REVERB_PORT} " || echo "WARN: nothing listening on ${REVERB_PORT}"
echo ""
echo "If RUNNING, Reverb is OK. Also fix .env placeholders:"
echo "  REVERB_APP_KEY / REVERB_APP_SECRET (not CHANGE_ME_...)"
echo "  REVERB_HOST=your-real-domain.com"
echo "Then: php artisan config:cache && supervisorctl restart quizsnap-reverb"
