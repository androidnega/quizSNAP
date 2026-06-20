#!/usr/bin/env bash
# Start the Laravel development server bound to localhost only.
# Usage: ./start-local.sh

set -e
cd "$(dirname "$0")"

PHP_BIN="${PHP:-php}"

# If PHP is not in PATH, fall back to XAMPP's PHP.
if ! command -v "$PHP_BIN" >/dev/null 2>&1; then
  if [ -x "/Applications/XAMPP/xamppfiles/bin/php" ]; then
    PHP_BIN="/Applications/XAMPP/xamppfiles/bin/php"
  else
    echo "PHP binary not found."
    echo "Install PHP or set the PHP environment variable to the PHP executable path."
    exit 1
  fi
fi

echo "Using PHP: $($PHP_BIN -v 2>/dev/null | head -n 1)"

# Prefer Redis for cache/sessions when available (faster route data caching).
if command -v redis-cli >/dev/null 2>&1 && redis-cli ping 2>/dev/null | grep -q PONG; then
  export CACHE_STORE=redis
  export CACHE_DRIVER=redis
  export SESSION_DRIVER=redis
  export QUEUE_CONNECTION=redis
  echo "Redis detected — using redis for cache, sessions, and queue."
else
  echo "Redis not running — using file cache and database queue (install/start Redis for faster pages)."
fi

# Live WebSocket (Laravel Reverb) — defaults for local dev when .env omits them.
export BROADCAST_CONNECTION="${BROADCAST_CONNECTION:-reverb}"
export REVERB_APP_ID="${REVERB_APP_ID:-quizsnap-local}"
export REVERB_APP_KEY="${REVERB_APP_KEY:-quizsnap-local-dev-key}"
export REVERB_APP_SECRET="${REVERB_APP_SECRET:-quizsnap-local-dev-secret}"
export REVERB_HOST="${REVERB_HOST:-127.0.0.1}"
export REVERB_PORT="${REVERB_PORT:-8080}"
export REVERB_SCHEME="${REVERB_SCHEME:-http}"
export REVERB_SERVER_HOST="${REVERB_SERVER_HOST:-127.0.0.1}"
export REVERB_SERVER_PORT="${REVERB_SERVER_PORT:-8080}"
export REVERB_SCALING_ENABLED="${REVERB_SCALING_ENABLED:-false}"

if [ ! -e public/storage ]; then
  echo "Linking public/storage → storage/app/public ..."
  $PHP_BIN artisan storage:link
fi

echo "Starting Reverb WebSocket server (background) on ws://${REVERB_HOST}:${REVERB_SERVER_PORT} ..."
$PHP_BIN artisan reverb:start --host="$REVERB_SERVER_HOST" --port="$REVERB_SERVER_PORT" &
REVERB_PID=$!

echo "Starting queue worker (background)..."
$PHP_BIN artisan queue:work --sleep=1 --tries=2 &
QUEUE_PID=$!

cleanup() {
  kill "$REVERB_PID" 2>/dev/null || true
  kill "$QUEUE_PID" 2>/dev/null || true
}
trap cleanup EXIT INT TERM

echo "Starting Laravel dev server on http://127.0.0.1:8000 ..."
echo "Live socket: ws://${REVERB_HOST}:${REVERB_SERVER_PORT} (BROADCAST_CONNECTION=${BROADCAST_CONNECTION})"

$PHP_BIN artisan serve --host=127.0.0.1 --port=8000
