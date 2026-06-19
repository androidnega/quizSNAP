# QuizSnap Production Audit Guide

Use this guide after deploying production configs and application optimizations.  
All measurements are read-only. Do not tune beyond the 8 CPU / 24 GB QuizSnap budget on a 48 GB / 12 CPU server.

---

## Quick Start

```bash
cd /var/www/quizsnap

# Set credentials for the audit script
export QUIZSNAP_APP="/var/www/quizsnap"
export REDIS_PASS="your-redis-password"
export MYSQL_USER="quizsnap_app"
export MYSQL_PASS="your-mysql-password"

chmod +x production-audit.sh
./production-audit.sh
# → writes reports/quizsnap-audit-YYYYMMDD-HHMMSS.txt

# Fill the summary template
cp production-audit-report.txt production-audit-report-$(date +%Y%m%d)-filled.txt
# Paste measurements; complete Current / Safe / Recommended columns
```

Run audits at three points:

1. **Idle** — baseline after deploy
2. **Exam start spike** — first 5–10 minutes of a large exam
3. **Steady state** — autosave + heartbeat + proctoring during active window

---

## 1. PHP-FPM Utilization

```bash
# Pool status (configure nginx fpm-status for 127.0.0.1)
curl -s http://127.0.0.1/fpm-status?full | head -40

# Process count and RSS
ps aux | grep '[p]hp-fpm: pool quizsnap' | wc -l
ps -o rss= -C php-fpm8.4 | awk '{s+=$1} END {printf "PHP-FPM RSS: %.0f MB\n", s/1024}'

# Slow log
sudo tail -50 /var/log/php8.4-fpm-quizsnap-slow.log
```

**Safe:** active processes &lt; 40; listen queue = 0; RSS &lt; 12 GB  
**Watch:** listen queue &gt; 0, slow log growing during exam

---

## 2. CPU Utilization

```bash
top -b -n 1 | head -20
uptime
mpstat -P ALL 1 3    # apt install sysstat
```

**Safe:** total CPU &lt; 70%; load average &lt; 8 on QuizSnap cores  
**Reserved:** 4 cores for OS/infra — do not saturate all 12 cores

---

## 3. RAM Utilization

```bash
free -h
grep -E '^(MemTotal|MemAvailable|SwapTotal|SwapFree):' /proc/meminfo
```

**Safe:** total used &lt; 40 GB; MemAvailable &gt; 8 GB during exam  
**Critical:** any swap usage during exams → reduce workers or load

---

## 4–5. Redis Memory and Connections

```bash
redis-cli -a "$REDIS_PASS" --no-auth-warning INFO memory | grep -E 'used_memory_human|maxmemory_human|mem_fragmentation_ratio'
redis-cli -a "$REDIS_PASS" --no-auth-warning INFO clients | grep -E 'connected_clients|rejected_connections|maxclients'
redis-cli -a "$REDIS_PASS" --no-auth-warning INFO stats | grep instantaneous_ops_per_sec
redis-cli -a "$REDIS_PASS" --no-auth-warning DBSIZE
```

**Safe:** used_memory &lt; 2.5 GB; connected_clients &lt; 3500; rejected_connections = 0

---

## 6–7. MySQL Buffer Pool and Query Performance

```bash
mysql -u"$MYSQL_USER" -p"$MYSQL_PASS" -e "
SHOW GLOBAL STATUS LIKE 'Innodb_buffer_pool_read%';
SHOW GLOBAL STATUS LIKE 'Threads_running';
SHOW GLOBAL STATUS LIKE 'Max_used_connections';
SHOW GLOBAL STATUS LIKE 'Slow_queries';
SHOW GLOBAL STATUS WHERE Variable_name IN ('Questions','Uptime');
"
```

Buffer pool hit rate:

```sql
SELECT
  (1 - (Innodb_buffer_pool_reads / NULLIF(Innodb_buffer_pool_read_requests, 0))) * 100
  AS hit_rate_pct
FROM (
  SELECT
    VARIABLE_VALUE AS Innodb_buffer_pool_reads
  FROM performance_schema.global_status
  WHERE VARIABLE_NAME = 'Innodb_buffer_pool_reads'
) r,
(
  SELECT VARIABLE_VALUE AS Innodb_buffer_pool_read_requests
  FROM performance_schema.global_status
  WHERE VARIABLE_NAME = 'Innodb_buffer_pool_read_requests'
) rr;
```

**Safe:** hit rate &gt; 99%; Threads_running &lt; 80; Slow_queries not rising during exam

---

## 8–10. Queue Workers, Backlog, Failed Jobs

```bash
sudo supervisorctl status | grep quizsnap
cd /var/www/quizsnap
php artisan queue:monitor redis:default,redis:imports
php artisan queue:failed
mysql -u"$MYSQL_USER" -p"$MYSQL_PASS" -N -e "SELECT COUNT(*) FROM failed_jobs;"
```

**Safe:** all workers RUNNING; backlog &lt; 20; failed_jobs = 0

---

## 11. Reverb / WebSocket Connections

```bash
sudo supervisorctl status quizsnap-reverb
ss -tlnp | grep ':8080'
ss -tn state established | grep ':8080' | wc -l
```

**Safe:** Reverb RUNNING; WS connections track active students (not &gt; ~3000)

---

## 12. Nginx Throughput

```bash
ss -tn state established '( dport = :443 or sport = :443 )' | wc -l
sudo tail -1000 /var/log/nginx/quizsnap-access.log | awk '{print $9}' | sort | uniq -c | sort -rn
sudo tail -100 /var/log/nginx/quizsnap-error.log
```

**Safe:** 5xx rate &lt; 0.1%; upstream timeouts rare

---

## 13. Netdata

```bash
curl -sf http://127.0.0.1:19999/api/v1/info && echo OK
```

Review charts: `system.cpu`, `system.ram`, `disk.io`, `apps.mem`, `redis.memory`, `mysql.queries`

---

## 14. Open File Descriptors

```bash
cat /proc/sys/fs/file-nr
ss -s
for pid in $(pgrep -f 'php-fpm: pool quizsnap' | head -1) $(pgrep redis-server | head -1) $(pgrep mysqld | head -1); do
  echo "PID $pid: $(ls /proc/$pid/fd 2>/dev/null | wc -l) FDs"
done
```

---

## 15. Swap Usage

```bash
swapon --show
free -h | grep -i swap
```

**Must be 0 during exams.**

---

## 16. Disk I/O

```bash
vmstat 2 5
iostat -xz 1 3
```

**Safe:** await &lt; 5 ms; %util not pegged at 100% sustained

---

## vmstat / iostat / ss / netstat Reference

```bash
vmstat 2 5
iostat -xz 1 3
ss -s
ss -tn state established | wc -l
netstat -s | head -30    # if ss unavailable
```

---

## Automated Script

`production-audit.sh` runs the above checks and writes a timestamped `.txt` file.  
Complete `production-audit-report.txt` with the summary table and capacity estimates.

---

## Report Template (fill after measurements)

| Metric | Current | Safe | Recommended |
|--------|---------|------|-------------|
| CPU total % | | &lt; 70% | 50–65% peak |
| RAM used (total) | | &lt; 40 GB | &lt; 36 GB |
| Swap used | | 0 | 0 |
| Redis used_memory | | &lt; 2.5 GB | &lt; 2 GB |
| Redis connected_clients | | &lt; 3500 | ~2500 + workers |
| MySQL buffer pool hit % | | &gt; 99% | &gt; 99.5% |
| PHP-FPM active processes | | &lt; 40 | 15–35 |
| PHP-FPM listen queue | | 0 | 0 |
| Queue backlog | | &lt; 100 | &lt; 20 |
| failed_jobs (24h) | | 0 | 0 |
| Reverb WS connections | | &lt; 3000 | ~2500 |
| Nginx 5xx rate | | &lt; 0.1% | 0% |
| Disk await (ms) | | &lt; 5 | &lt; 2 |

### Concurrent Student Capacity (from measured metrics only)

| Estimate | Criteria | Students |
|----------|----------|----------|
| Conservative | swap=0, CPU&lt;70%, FPM queue=0, no 5xx | ___ |
| Realistic | CPU 65–75%, Redis &lt;2.5 GB, queue &lt;20 | ___ |
| Optimistic | All green, headroom remains | ___ |

### Bottlenecks Detected

1. ___
2. ___

### Recommended Changes (only if over Safe column)

- ___

---

## Post-Deploy Verification Commands

```bash
# App health
cd /var/www/quizsnap
php artisan about
php artisan config:show cache.default session.driver queue.default

# Services
sudo systemctl status nginx php8.4-fpm redis-server mysql
sudo supervisorctl status

# Scheduler (cron must call schedule:run every minute)
php artisan schedule:list

# Migrations
php artisan migrate:status | tail -5
```

---

## Rollback Reference

See `IMPLEMENTATION_REPORT.md` → Rollback Instructions.

---

## Resource Budget Reference

| Component | RAM | CPU |
|-----------|-----|-----|
| Total server | 48 GB | 12 |
| OS + infra | 4 GB | 4 |
| Redis | 4 GB (maxmemory 3 GB) | — |
| MySQL | 8 GB (buffer pool 6 GB) | — |
| Monitoring/Nginx/Supervisor | 2 GB | — |
| **QuizSnap app** | **~24 GB** | **~8** |
| **Headroom target** | **≥24 GB free for growth** | **≥4 cores free** |
