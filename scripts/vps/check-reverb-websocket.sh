#!/usr/bin/env bash
# Check Laravel Reverb + nginx WebSocket proxy on production.
# Run: cd /srv/apps/quizsnap && sudo bash scripts/vps/check-reverb-websocket.sh
set -euo pipefail

APP_DIR="/srv/apps/quizsnap"
REVERB_PORT="${REVERB_PORT:-8080}"
cd "$APP_DIR"

echo "==> Reverb / WebSocket diagnostics"
echo "App: $APP_DIR"
echo ""

echo "==> .env (Reverb)"
grep -E '^(BROADCAST_CONNECTION|REVERB_HOST|REVERB_PORT|REVERB_SCHEME|REVERB_APP_KEY|REVERB_SERVER_PORT)=' .env 2>/dev/null | sed 's/REVERB_APP_KEY=.*/REVERB_APP_KEY=***redacted***/' || echo "WARN: .env not readable"
echo ""

echo "==> Laravel config (cached)"
php artisan quizsnap:reverb-status || true
echo ""

echo "==> Supervisor Reverb"
supervisorctl status quizsnap-reverb 2>/dev/null || echo "WARN: quizsnap-reverb not in supervisor"
echo ""

echo "==> Port ${REVERB_PORT} listener"
if ss -ltnp 2>/dev/null | grep ":${REVERB_PORT} "; then
  ss -ltnp | grep ":${REVERB_PORT} " || true
else
  echo "ERROR: nothing listening on 127.0.0.1:${REVERB_PORT}"
  echo "Fix: sudo bash scripts/vps/consolidate-reverb.sh"
fi
echo ""

echo "==> Local WebSocket upgrade probe (127.0.0.1:${REVERB_PORT})"
KEY="$(grep '^REVERB_APP_KEY=' .env | cut -d= -f2- | tr -d '"')"
if [[ -z "$KEY" ]]; then
  echo "WARN: REVERB_APP_KEY missing in .env"
else
  HTTP_CODE="$(curl -s -o /dev/null -w '%{http_code}' --max-time 3 \
    -H 'Connection: Upgrade' \
    -H 'Upgrade: websocket' \
    -H 'Sec-WebSocket-Version: 13' \
    -H 'Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==' \
    "http://127.0.0.1:${REVERB_PORT}/app/${KEY}?protocol=7&client=js&version=8.2.0" 2>/dev/null)" || HTTP_CODE="000"
  if [[ "$HTTP_CODE" == "101" ]]; then
    echo "HTTP status from Reverb: 101 (Switching Protocols) — OK"
  elif [[ "$HTTP_CODE" == "000" ]]; then
    echo "WARN: probe timed out or failed (Reverb may still be OK if port 8080 is listening)"
  else
    echo "HTTP status from Reverb: ${HTTP_CODE} (expect 101 Switching Protocols)"
  fi
fi
echo ""

echo "==> nginx /app proxy (required for wss://your-domain/app/...)"
NGINX_SITE=""
for f in /etc/nginx/sites-enabled/*; do
  if [[ -f "$f" ]] && grep -q 'location /app' "$f" 2>/dev/null; then
    NGINX_SITE="$f"
    echo "Found in: $f"
    grep -A12 'location /app' "$f" | head -13
    break
  fi
done
if [[ -z "$NGINX_SITE" ]]; then
  echo "ERROR: no 'location /app' block in /etc/nginx/sites-enabled/*"
  echo "Fix: sudo bash scripts/vps/install-nginx-reverb-proxy.sh"
  echo "Then: sudo bash scripts/vps/check-reverb-websocket.sh"
fi
echo ""

echo "==> nginx WebSocket probe (local nginx → Reverb)"
HOST="$(grep '^REVERB_HOST=' .env | cut -d= -f2- | tr -d '"')"
if [[ -n "$HOST" && -n "${KEY:-}" ]]; then
  WS_HEADERS=(
    -H 'Connection: Upgrade'
    -H 'Upgrade: websocket'
    -H 'Sec-WebSocket-Version: 13'
    -H 'Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ=='
  )
  LOCAL_NGINX_CODE="$(curl -s -o /dev/null -w '%{http_code}' --max-time 4 \
    --resolve "${HOST}:443:127.0.0.1" \
    "${WS_HEADERS[@]}" \
    "https://${HOST}/app/${KEY}?protocol=7&client=js&version=8.2.0" 2>/dev/null)" || LOCAL_NGINX_CODE="000"
  if [[ "$LOCAL_NGINX_CODE" == "101" ]]; then
    echo "Local nginx (127.0.0.1:443): 101 — WebSocket proxy OK"
  elif [[ "$LOCAL_NGINX_CODE" == "502" || "$LOCAL_NGINX_CODE" == "504" ]]; then
    echo "ERROR: local nginx returned ${LOCAL_NGINX_CODE} — cannot reach Reverb on 127.0.0.1:${REVERB_PORT}"
  elif [[ "$LOCAL_NGINX_CODE" == "404" ]]; then
    echo "ERROR: local nginx returned 404 — location /app missing or wrong server block"
  else
    echo "Local nginx (127.0.0.1:443): ${LOCAL_NGINX_CODE} (expect 101)"
  fi

  echo ""
  echo "==> Public HTTPS probe (optional; 000 often means Cloudflare/DNS from the server itself)"
  PUBLIC_CODE="$(curl -s -o /dev/null -w '%{http_code}' --max-time 5 \
    "${WS_HEADERS[@]}" \
    "https://${HOST}/app/${KEY}?protocol=7&client=js&version=8.2.0" 2>/dev/null)" || PUBLIC_CODE="000"
  if [[ "$PUBLIC_CODE" == "101" ]]; then
    echo "Public HTTPS: 101 — OK"
  elif [[ "$PUBLIC_CODE" == "000" && "$LOCAL_NGINX_CODE" == "101" ]]; then
    echo "Public HTTPS: 000 (ignored — local nginx is OK; test wss:// in your browser)"
  elif [[ "$PUBLIC_CODE" == "404" ]]; then
    echo "ERROR: public HTTPS returned 404"
  else
    echo "Public HTTPS: ${PUBLIC_CODE} (expect 101; use browser DevTools if local nginx is 101)"
  fi
fi
