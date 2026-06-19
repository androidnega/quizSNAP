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

if [ ! -e public/storage ]; then
  echo "Linking public/storage → storage/app/public ..."
  $PHP_BIN artisan storage:link
fi

echo "Starting queue worker (background)..."
$PHP_BIN artisan queue:work --sleep=1 --tries=2 &
QUEUE_PID=$!
cleanup() {
  kill "$QUEUE_PID" 2>/dev/null || true
}
trap cleanup EXIT INT TERM

echo "Starting Laravel dev server on http://127.0.0.1:8000 ..."

$PHP_BIN artisan serve --host=127.0.0.1 --port=8000

