#!/usr/bin/env bash
# Add Laravel Reverb WebSocket proxy (location /app) to the active nginx site.
# Run: cd /srv/apps/quizsnap && sudo bash scripts/vps/install-nginx-reverb-proxy.sh
set -euo pipefail

APP_DIR="/srv/apps/quizsnap"
REVERB_PORT="${REVERB_PORT:-8080}"
SNIPPET="${APP_DIR}/scripts/vps/nginx-reverb-proxy.conf"
BACKUP_DIR="/etc/nginx/quizsnap-backups"

find_site() {
  local f
  for f in /etc/nginx/sites-enabled/*; do
    [[ -f "$f" ]] || continue
    if grep -q 'quizsnap\.online\|/srv/apps/quizsnap\|server_name.*quizsnap' "$f" 2>/dev/null; then
      echo "$f"
      return 0
    fi
  done
  for f in /etc/nginx/sites-enabled/*; do
    [[ -f "$f" ]] || continue
    if grep -q 'location /' "$f" 2>/dev/null; then
      echo "$f"
      return 0
    fi
  done
  return 1
}

if [[ "$(id -u)" -ne 0 ]]; then
  echo "Run as root: sudo bash scripts/vps/install-nginx-reverb-proxy.sh"
  exit 1
fi

SITE="$(find_site)" || {
  echo "ERROR: could not find nginx site in /etc/nginx/sites-enabled/"
  exit 1
}

echo "==> Using nginx site: $SITE"

if grep -q 'location /app' "$SITE"; then
  echo "location /app already present — nothing to do."
  grep -A8 'location /app' "$SITE" | head -9
  exit 0
fi

mkdir -p "$BACKUP_DIR"
BACKUP="${BACKUP_DIR}/$(basename "$SITE").$(date +%Y%m%d%H%M%S).bak"
cp "$SITE" "$BACKUP"
echo "==> Backup: $BACKUP"

cat > "$SNIPPET" <<EOF
    # Laravel Reverb WebSocket (added by install-nginx-reverb-proxy.sh)
    location /app {
        proxy_http_version 1.1;
        proxy_set_header Host \$http_host;
        proxy_set_header Scheme \$scheme;
        proxy_set_header SERVER_PORT \$server_port;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_read_timeout 60s;
        proxy_send_timeout 60s;
        proxy_pass http://127.0.0.1:${REVERB_PORT};
    }

EOF

# Insert before the first "location / {" block inside the server.
python3 <<PY
from pathlib import Path
site = Path("$SITE")
snippet = Path("$SNIPPET").read_text()
text = site.read_text()
needle = "    location / {"
if needle not in text:
    needle = "location / {"
if needle not in text:
    raise SystemExit("ERROR: could not find 'location / {' in nginx site — add location /app manually")
if "location /app" in text:
    raise SystemExit("location /app already exists")
site.write_text(text.replace(needle, snippet + needle, 1))
print("Inserted location /app before location /")
PY

rm -f "$SNIPPET"

echo "==> Testing nginx..."
nginx -t

echo "==> Reloading nginx..."
systemctl reload nginx

echo ""
echo "Done. Verify:"
echo "  grep -A8 'location /app' $SITE"
echo "  cd $APP_DIR && sudo bash scripts/vps/check-reverb-websocket.sh"
