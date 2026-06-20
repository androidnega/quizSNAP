#!/usr/bin/env bash
# QuizSnap production deploy — run on server: bash deploy.sh
set -euo pipefail

APP_DIR="/srv/apps/quizsnap"
cd "$APP_DIR"

echo "==> Fetching latest..."
git fetch origin
git reset --hard origin/main

echo "==> Composer..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Migrate..."
php artisan migrate --force

echo "==> Clear stale caches..."
php artisan optimize:clear

echo "==> Rebuild caches..."
php artisan config:cache
php artisan route:cache
php artisan view:clear
php artisan cache:clear

echo "==> Permissions..."
if [[ "$(id -u)" -eq 0 ]]; then
  bash scripts/fix-storage-permissions.sh
else
  sudo bash scripts/fix-storage-permissions.sh
fi

echo "==> Restart workers..."
if command -v supervisorctl >/dev/null 2>&1; then
  supervisorctl restart quizsnap-reverb quizsnap-queue-default:* quizsnap-queue-imports 2>/dev/null || \
    supervisorctl restart all 2>/dev/null || \
    echo "WARN: supervisorctl restart failed — check /etc/supervisor/conf.d/quizsnap.conf paths."
else
  echo "WARN: supervisorctl not found — restart Reverb and queue workers manually."
fi

echo ""
echo "==> Done. Latest commit:"
git log -1 --oneline
echo ""
if command -v supervisorctl >/dev/null 2>&1; then
  supervisorctl status 2>/dev/null | grep quizsnap || true
fi
