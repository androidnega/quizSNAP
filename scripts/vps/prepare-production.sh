#!/usr/bin/env bash
# QuizSnap VPS process stack (run under Supervisor — see scripts/vps/supervisor-quizsnap.conf)
# Do NOT use "php artisan serve" in production.

set -euo pipefail
cd "$(dirname "$0")/.."

PHP_BIN="${PHP:-php}"

echo "==> QuizSnap production helpers"
echo "PHP: $($PHP_BIN -v 2>/dev/null | head -n 1)"

if ! command -v redis-cli >/dev/null 2>&1; then
  echo "WARN: redis-cli not found. Install Redis and set CACHE_STORE=redis SESSION_DRIVER=redis."
else
  if redis-cli ping 2>/dev/null | grep -q PONG; then
    echo "OK: Redis responds to PING"
  else
    echo "WARN: Redis not responding. Start Redis before exams."
  fi
fi

$PHP_BIN artisan optimize:clear
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
# Do not pre-compile views as root/deploy user — PHP-FPM (www-data) must write storage/framework/views at runtime.
$PHP_BIN artisan view:clear
$PHP_BIN artisan storage:fix-permissions || true

echo ""
echo "If storage:fix-permissions failed, run as root:"
echo "  sudo bash scripts/fix-storage-permissions.sh"
echo ""
echo "Start these via Supervisor (recommended):"
echo "  1. php artisan reverb:start --host=0.0.0.0 --port=\${REVERB_PORT:-8080}"
echo "  2. php artisan queue:work redis --sleep=1 --tries=3 --max-time=3600"
echo "  3. Cron: * * * * * cd $(pwd) && php artisan schedule:run"
echo ""
echo "Nginx/PHP-FPM should point document root to: $(pwd)/public"
echo "Set in .env: CACHE_STORE=redis SESSION_DRIVER=redis QUEUE_CONNECTION=redis BROADCAST_CONNECTION=reverb"
