# QuizSnap Production Implementation Report

Date: 2026-06-19  
Scope: Safe production optimizations for ~2,500 concurrent students on 48 GB / 12 CPU VPS  
Constraint: QuizSnap budget ~8 CPU / 24 GB RAM; ≥24 GB and ≥4 CPU reserved for OS, MySQL, Redis, monitoring, growth

---

## Summary

Codebase audit identified hot-path query amplification (violation counts, proctoring settings, student lookups), redundant eager loads, per-row attendance imports, synchronous AI on the result page, and per-session heartbeat flushes. Changes reduce database round-trips and request-thread work without altering exam behaviour for students.

**Capacity claims:** No throughput numbers are asserted here. Validate with `production-audit.sh` and `production-audit-report.txt` under real exam load.

---

## Files Modified

| File | Change |
|------|--------|
| `app/Http/Controllers/Student/StudentQuizController.php` | Hot-path query reduction, heartbeat write skip, proctoring flag batching, student hash lookup, AI job dispatch |
| `app/Services/QuizConcurrencyService.php` | Single-query violation aggregates; batched heartbeat MySQL flush |
| `app/Services/QuestionAssignmentService.php` | Optional Redis-cached question pool (production env) |
| `app/Models/Setting.php` | `getProctoringFlags()` — one pass over cached settings |
| `app/Models/Student.php` | `findByIndex()` via `index_number_hash` |
| `app/Http/Controllers/Admin/QuizManagementController.php` | Fix N+1 in `liveProctorAllSessions` |
| `app/Http/Controllers/Admin/AttendanceUploadController.php` | Bulk insert/upsert for Excel imports |
| `app/Providers/AppServiceProvider.php` | Reuse `quizAllowsMobile` from request attributes |
| `app/Events/ProctorFrameUpdated.php` | `ShouldBroadcast` (queued) instead of `ShouldBroadcastNow` |
| `config/quiz-scale.php` | `question_pool_cache_seconds` |
| `routes/console.php` | Tab-switch scheduler: every 10s → every 1 min (column rarely set) |
| `.env.production` | `QUIZ_QUESTION_POOL_CACHE_SECONDS=120` |

## Files Added

| File | Purpose |
|------|---------|
| `database/migrations/2026_06_19_100000_add_performance_indexes_for_quiz_hot_paths.php` | Performance indexes |
| `app/Jobs/GenerateWrongAnswerExplanationsJob.php` | Queue AI wrong-answer explanations off result page |

## Production Config Files (unchanged this pass, deploy as-is)

- `.env.production`
- `nginx-production.conf`
- `php-fpm-production.conf`
- `mysql-production.cnf`
- `redis-production.cnf`
- `supervisor-production.conf`
- `production-audit.sh`
- `production-audit-report.txt`

---

## Database Changes

### Migration

Run on production after deploy:

```bash
cd /var/www/quizsnap
php artisan migrate --force
```

### New Indexes

| Table | Index | Supports |
|-------|-------|----------|
| `quiz_sessions` | `(quiz_id, student_index)` | Session lookup by student per quiz |
| `quiz_sessions` | `last_heartbeat_at` | Live proctor heartbeat filter |
| `quiz_sessions` | `(ended_at, auto_submit_after)` | Scheduler scans |
| `quiz_violations` | `(quiz_session_id, image_url)` | Capture count queries |
| `valid_indices` | `course_id` | Attendance replace/delete by course |

---

## Performance Improvements (code analysis)

| Area | Before | After | Mechanism |
|------|--------|-------|-----------|
| Quiz `show()` | 3 violation COUNT queries + 6 setting lookups + eager `quiz.questions` | 1 grouped violation query + batched settings + no question pool load | Fewer queries per page load |
| `recordViolation()` | Up to 4 aggregate queries per event | 1 grouped query reused for thresholds + response | Fewer writes/reads under proctoring load |
| `heartbeat()` | MySQL UPDATE every request | UPDATE only when clearing `auto_submit_after` or capturing device | Fewer row updates at 2,500 × ~45s |
| Heartbeat flush job | N individual UPDATEs | 1 batched CASE UPDATE | Lower scheduler DB load |
| `saveAnswer()` | Possible double encryption (manual + cast) | Eloquent `EncryptedNullable` only | Correctness + less CPU |
| `liveProctorAllSessions` | N queries for student names | 1 query per class group batch | Admin poll every 5s |
| Attendance Excel | Row-by-row INSERT/SELECT | Chunked insert/upsert (500 rows) | Faster admin uploads |
| Result page AI | Sync API loop blocking render | `GenerateWrongAnswerExplanationsJob` on queue | Shorter result page TTFB |
| Proctor frame broadcast | Sync `ShouldBroadcastNow` | Queued `ShouldBroadcast` | FPM workers not blocked on Reverb |
| Exam start assignment | Full question load every session | 120s pool cache (production only) | Fewer reads during start spike |

---

## Risks

| Risk | Mitigation |
|------|------------|
| Question pool cache stale if questions edited mid-exam | TTL 120s; set `QUIZ_QUESTION_POOL_CACHE_SECONDS=0` to disable |
| AI explanations not on first result page view | Job runs async; student can refresh after a few seconds |
| Queued proctor frames slightly delayed | Acceptable; live proctor feed disabled on student UI today |
| Batched heartbeat flush uses raw SQL | Scoped to flush command; only updates `last_heartbeat_at` |
| `index_number_hash` missing for legacy students | Falls back to no match; name display optional only |

---

## Rollback Instructions

### Application code

```bash
cd /var/www/quizsnap
git checkout <previous-release-tag>
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo supervisorctl restart all
```

### Migration rollback (if needed)

```bash
php artisan migrate:rollback --step=1
```

### Config rollback

Restore prior copies of `/etc/nginx/sites-available/quizsnap`, PHP-FPM pool, Redis, MySQL, Supervisor from backup.

### Feature flags without full rollback

```env
QUIZ_QUESTION_POOL_CACHE_SECONDS=0
QUIZ_DEFER_HEARTBEAT_WRITES=false
```

---

## Expected Impact

Measurable only after deployment audit. Expected directional effects:

- Lower MySQL QPS during steady exam (heartbeats, violation events, quiz page load)
- Lower PHP-FPM active time per request on result page and proctor broadcast path
- Reduced exam-start DB read burst when question pool cache is warm
- Admin live-proctor API: fewer queries per 5s poll cycle

**Do not treat 2,500 concurrent students as validated until `production-audit-report.txt` is filled from measured peak metrics.**

---

## Not Changed (intentional)

- `.env`, `.env.local`, `.env.testing` — local dev unchanged
- Autosave/finalize remain synchronous (durability requirement)
- Violation image capture remains synchronous (client expects `image_url` in response)
- Client polling intervals unchanged (would need frontend change)

---

## Sign-off Checklist

- [ ] `php artisan migrate --force` on production
- [ ] Production configs copied and services restarted
- [ ] `./production-audit.sh` during exam spike
- [ ] `production-audit-report.txt` completed with conservative / realistic / optimistic capacity
