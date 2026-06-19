#!/usr/bin/env bash
# Fix Laravel storage permissions on production (run with sudo if needed).
# Usage: cd /srv/apps/quizsnap && sudo bash scripts/fix-storage-permissions.sh

set -euo pipefail

APP_DIR="${1:-$(cd "$(dirname "$0")/.." && pwd)}"
WEB_USER="${WEB_SERVER_USER:-www-data}"

cd "$APP_DIR"

echo "==> QuizSnap storage permissions"
echo "App: $APP_DIR"
echo "Web user: $WEB_USER"

DIRS=(
  storage
  bootstrap/cache
)

for d in "${DIRS[@]}"; do
  if [[ ! -d "$d" ]]; then
    echo "Missing directory: $d"
    exit 1
  fi
done

if [[ "$(id -u)" -eq 0 ]]; then
  chown -R "${WEB_USER}:${WEB_USER}" storage bootstrap/cache
  chmod -R ug+rwx storage bootstrap/cache
else
  echo "Not root — trying artisan only (chown may still be required)."
  chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true
fi

php artisan view:clear 2>/dev/null || true
php artisan storage:fix-permissions || true

echo ""
echo "Done. Reload the site. If errors persist, run as root:"
echo "  sudo chown -R ${WEB_USER}:${WEB_USER} storage bootstrap/cache"
echo "  sudo chmod -R ug+rwx storage bootstrap/cache"
