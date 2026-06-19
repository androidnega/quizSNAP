#!/usr/bin/env bash
# =============================================================================
# QuizSnap Production Post-Deployment Audit
# =============================================================================
# Run on the VPS during or after a live exam window (or staged load test).
# Does NOT modify configuration. Read-only measurements.
#
# Usage:
#   chmod +x production-audit.sh
#   ./production-audit.sh
#     → writes measurements to reports/quizsnap-audit-YYYYMMDD-HHMMSS.txt
#   ./production-audit.sh /path/to/custom-audit.txt
#     → writes measurements to the given path
#
# After run, fill production-audit-report.txt (save a dated copy when complete).
#
# Prerequisites: redis-cli, mysql client, curl, optional: iostat, netstat
# Set before run:
export QUIZSNAP_APP="/var/www/quizsnap"
export REDIS_PASS="your-redis-password"
export MYSQL_USER="quizsnap_app"
export MYSQL_PASS="your-mysql-password"
# =============================================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP="${QUIZSNAP_APP:-/var/www/quizsnap}"
STAMP="$(date '+%Y-%m-%d %H:%M:%S %Z')"
STAMP_FILE="$(date '+%Y%m%d-%H%M%S')"

if [[ -n "${1:-}" ]]; then
  REPORT="$1"
else
  REPORT="${SCRIPT_DIR}/reports/quizsnap-audit-${STAMP_FILE}.txt"
fi
mkdir -p "$(dirname "$REPORT")"

hr() { printf '\n%s\n' "────────────────────────────────────────────────────────────"; }
section() { hr; printf '## %s\n' "$1"; }

exec > >(tee "$REPORT") 2>&1
echo "Writing audit measurements to: $REPORT"
echo "Fill summary in: ${SCRIPT_DIR}/production-audit-report.txt"
echo ""

section "QuizSnap Production Audit — $STAMP"
echo "Host: $(hostname)"
echo "App:  $APP"
echo ""
echo "RESOURCE BUDGET (reference):"
echo "  Total server:     48 GB RAM | 12 CPU"
echo "  QuizSnap budget:  24 GB RAM |  8 CPU"
echo "  Redis reserved:   4 GB (maxmemory 3gb)"
echo "  MySQL reserved:   8 GB (buffer pool 6G)"
echo "  OS + infra:       4 GB RAM | 4 CPU"

# ── 1. CPU ───────────────────────────────────────────────────────────────────
section "1. CPU utilization"
echo "--- top (snapshot, batch mode) ---"
top -b -n 1 | head -20
echo ""
echo "--- load average ---"
uptime
echo ""
if command -v mpstat >/dev/null 2>&1; then
  echo "--- mpstat (per-core) ---"
  mpstat -P ALL 1 1 || true
fi

# ── 2. RAM ───────────────────────────────────────────────────────────────────
section "2. RAM utilization"
free -h
echo ""
echo "--- /proc/meminfo (key lines) ---"
grep -E '^(MemTotal|MemFree|MemAvailable|Buffers|Cached|SwapTotal|SwapFree):' /proc/meminfo

# ── 3. Swap ──────────────────────────────────────────────────────────────────
section "15. Swap usage"
swapon --show 2>/dev/null || echo "No swap configured"
free -h | grep -i swap || true
echo "ALERT if Swap used > 0 during exams — increase headroom or reduce workers."

# ── 4. vmstat / iostat ───────────────────────────────────────────────────────
section "VM / disk I/O"
if command -v vmstat >/dev/null 2>&1; then
  echo "--- vmstat (5 samples, 2s interval) ---"
  vmstat 2 5
else
  echo "vmstat not installed (apt install sysstat)"
fi
if command -v iostat >/dev/null 2>&1; then
  echo "--- iostat -xz 1 3 ---"
  iostat -xz 1 3 || true
else
  echo "iostat not installed (apt install sysstat)"
fi

# ── 5. PHP-FPM ───────────────────────────────────────────────────────────────
section "1. PHP-FPM utilization"
if curl -sf http://127.0.0.1/fpm-status?full >/dev/null 2>&1; then
  curl -s http://127.0.0.1/fpm-status?full | head -40
else
  echo "FPM status not reachable (configure Nginx fpm-status allow 127.0.0.1)"
  ps aux | grep '[p]hp-fpm: pool quizsnap' | wc -l | xargs -I{} echo "quizsnap pool processes (ps count): {}"
fi
echo ""
echo "--- PHP-FPM memory (RSS sum, MB) ---"
ps -o rss= -C php-fpm8.4 2>/dev/null | awk '{s+=$1} END {printf "Total PHP-FPM RSS: %.0f MB\n", s/1024}' || \
ps aux | grep '[p]hp-fpm' | awk '{s+=$6} END {printf "Total PHP-FPM RSS: %.0f MB\n", s/1024}'

# ── 6. Redis ─────────────────────────────────────────────────────────────────
section "4-5. Redis memory and connections"
if command -v redis-cli >/dev/null 2>&1; then
  REDIS_AUTH=()
  [[ -n "${REDIS_PASS:-}" ]] && REDIS_AUTH=(-a "$REDIS_PASS" --no-auth-warning)
  echo "--- redis INFO memory ---"
  redis-cli "${REDIS_AUTH[@]}" INFO memory 2>/dev/null | grep -E '^(used_memory_human|used_memory_peak_human|maxmemory_human|mem_fragmentation_ratio):' || true
  echo "--- redis INFO clients ---"
  redis-cli "${REDIS_AUTH[@]}" INFO clients 2>/dev/null | grep -E '^(connected_clients|blocked_clients|maxclients):' || true
  echo "--- redis INFO stats (ops/sec) ---"
  redis-cli "${REDIS_AUTH[@]}" INFO stats 2>/dev/null | grep -E '^(instantaneous_ops_per_sec|total_connections_received|rejected_connections):' || true
  echo "--- DBSIZE ---"
  redis-cli "${REDIS_AUTH[@]}" DBSIZE 2>/dev/null || true
else
  echo "redis-cli not found"
fi

# ── 7. MySQL ─────────────────────────────────────────────────────────────────
section "6-7. MySQL buffer pool and queries"
if command -v mysql >/dev/null 2>&1; then
  MYSQL_CMD=(mysql -N -s)
  [[ -n "${MYSQL_USER:-}" ]] && MYSQL_CMD+=(-u"$MYSQL_USER")
  [[ -n "${MYSQL_PASS:-}" ]] && MYSQL_CMD+=(-p"$MYSQL_PASS")
  echo "--- InnoDB buffer pool ---"
  "${MYSQL_CMD[@]}" -e "SHOW GLOBAL STATUS LIKE 'Innodb_buffer_pool%';" 2>/dev/null | head -15 || echo "MySQL auth failed — set MYSQL_USER/MYSQL_PASS"
  echo "--- Threads / connections ---"
  "${MYSQL_CMD[@]}" -e "SHOW GLOBAL STATUS LIKE 'Threads_%'; SHOW GLOBAL STATUS LIKE 'Max_used_connections';" 2>/dev/null || true
  echo "--- Slow queries ---"
  "${MYSQL_CMD[@]}" -e "SHOW GLOBAL STATUS LIKE 'Slow_queries';" 2>/dev/null || true
  echo "--- Questions per second (derive from Uptime) ---"
  "${MYSQL_CMD[@]}" -e "SHOW GLOBAL STATUS WHERE Variable_name IN ('Questions','Uptime');" 2>/dev/null || true
else
  echo "mysql client not found"
fi

# ── 8. Queue ─────────────────────────────────────────────────────────────────
section "8-10. Queue workers, backlog, failed jobs"
if [[ -d "$APP" ]]; then
  cd "$APP"
  echo "--- supervisorctl status ---"
  supervisorctl status 2>/dev/null | grep -E 'quizsnap' || supervisorctl status 2>/dev/null || true
  echo ""
  echo "--- php artisan queue:monitor ---"
  php artisan queue:monitor redis:default,redis:imports 2>/dev/null || php artisan queue:monitor 2>/dev/null || true
  echo ""
  echo "--- failed jobs count ---"
  php artisan queue:failed 2>/dev/null | tail -5 || true
  FAILED=$("${MYSQL_CMD[@]}" -e "SELECT COUNT(*) FROM failed_jobs;" 2>/dev/null || echo "?")
  echo "failed_jobs table rows: $FAILED"
  echo ""
  echo "--- jobs table pending (if database fallback) ---"
  "${MYSQL_CMD[@]}" -e "SELECT queue, COUNT(*) FROM jobs GROUP BY queue;" 2>/dev/null || echo "(redis queue — check queue:monitor above)"
else
  echo "APP path not found: $APP"
fi

# ── 9. Reverb ────────────────────────────────────────────────────────────────
section "11. Reverb / WebSocket"
echo "--- Reverb process ---"
supervisorctl status quizsnap-reverb 2>/dev/null || ps aux | grep '[a]rtisan reverb' || true
echo "--- Port 8080 listeners ---"
ss -tlnp | grep ':8080' || netstat -tlnp 2>/dev/null | grep ':8080' || true
echo "WebSocket connections: check Netdata or: ss -tn state established | grep ':8080' | wc -l"

# ── 10. Nginx ──────────────────────────────────────────────────────────────────
section "12. Nginx throughput"
if command -v nginx >/dev/null 2>&1; then
  echo "--- nginx -V (worker info) ---"
  nginx -V 2>&1 | tr ' ' '\n' | grep -E 'configure|worker' | head -5 || true
fi
echo "--- established HTTPS connections ---"
ss -tn state established '( dport = :443 or sport = :443 )' 2>/dev/null | wc -l | xargs -I{} echo "HTTPS established: {}"
echo "--- recent 5xx (last 1000 access lines) ---"
tail -1000 /var/log/nginx/quizsnap-access.log 2>/dev/null | awk '{print $9}' | sort | uniq -c | sort -rn | head -10 || echo "Log not found"

# ── 11. Network / FDs ─────────────────────────────────────────────────────────
section "14. Open file descriptors"
echo "--- system-wide FD usage ---"
cat /proc/sys/fs/file-nr 2>/dev/null || true
echo "format: allocated | unused | max"
for pid in $(pgrep -f 'php-fpm: pool quizsnap' 2>/dev/null | head -1) $(pgrep -f 'redis-server' 2>/dev/null | head -1) $(pgrep -f 'mysqld' 2>/dev/null | head -1); do
  name=$(ps -p "$pid" -o comm= 2>/dev/null || echo "?")
  fds=$(ls "/proc/$pid/fd" 2>/dev/null | wc -l || echo "?")
  echo "PID $pid ($name): $fds open FDs"
done
echo ""
echo "--- ss summary ---"
ss -s 2>/dev/null || netstat -s 2>/dev/null | head -20

# ── 12. Netdata ────────────────────────────────────────────────────────────────
section "13. Netdata checks"
if curl -sf http://127.0.0.1:19999/api/v1/info >/dev/null 2>&1; then
  echo "Netdata API: OK — http://YOUR_SERVER:19999"
  echo "Charts to review during exam:"
  echo "  - system.cpu"
  echo "  - system.ram"
  echo "  - disk.io"
  echo "  - apps.mem (php-fpm, mysql, redis)"
  echo "  - nginx.connections"
  echo "  - redis.memory"
  echo "  - mysql.queries"
else
  echo "Netdata not reachable on :19999 (firewall or not installed)"
fi

echo ""
echo "Audit complete."
echo "  Measurements: $REPORT"
echo "  Report form:  ${SCRIPT_DIR}/production-audit-report.txt"
echo "Paste raw output into the report file, then fill Current / Safe / Recommended columns."
