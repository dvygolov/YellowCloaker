# YellowTDS Load Testing Suite

k6-based load tests to find throughput limits and bottlenecks.

## Prerequisites

1. **k6** — install via one of:
   ```
   winget install Grafana.k6
   choco install k6
   ```
   Linux: `sudo apt install k6` or `snap install k6`

2. **PHP** — already installed (used for the server and setup scripts)

## Quick Start

```powershell
# 1. Start PHP dev server (in a separate terminal)
php -S localhost:8080 -t code/

# 2. Create test campaign
php tests/load/setup_campaign.php

# 3. Run the main mixed traffic test
k6 run -e BASE_URL=http://localhost:8080 loadtest/k6/scenarios/mixed.js

# 4. Clean up when done
php tests/load/teardown.php
```

## Scenarios

| Scenario | File | What it tests |
|----------|------|---------------|
| **White** | `white.js` | Bot UAs + filtered countries → white click path |
| **Black** | `black.js` | Real user traffic → redirect/landing path |
| **JS Connect** | `jsconnect.js` | Two-phase: page load → `js/index.php` script |
| **JS Bot Detection** | `jsbotdetect.js` | Three-phase: jscheck page → pass → landing |
| **Mixed** | `mixed.js` | 60% white + 30% black + 5% JS + 5% postback |
| **SQLite Stress** | `sqlite-stress.js` | Max write pressure on clicks table |
| **Events** | `events.js` | Mixed unique writes, retries, expected 422s, optional stats reads |

## Events API: isolated fixture and benchmark

The HTTP scenario needs real click-step identities because `/api/events.php`
validates the complete `clickid + step_index + variant` tuple. Create its
dedicated campaign explicitly, run k6, and remove it afterwards:

```powershell
# This is the only step below that deliberately writes to the configured runtime DB.
php tests/load/setup_events.php --clicks=5000 --steps=2

.\tests\load\run.ps1 -Profile smoke -Scenario events
# Or run k6 directly:
k6 run -e BASE_URL=http://localhost:8080 loadtest/k6/scenarios/events.js

php tests/load/teardown_events.php
```

`setup_events.php` refuses to replace an existing fixture. The manifest records
the exact campaign ID, name, and database path; teardown deletes only when all
three still match. Before deletion it also verifies that every reserved retry
target kept its original ordinary/Performance values and no unknown event was
stored. Most click-steps are empty, globally unique write targets.
A reserved tail is pre-seeded for duplicate-retry contention. During the same
run, k6 sends:

- one `cta_click` plus one Performance packet to every selected unique target;
- competing ordinary/Performance retries to reserved targets;
- syntactically valid but unknown events that must return exactly 422;
- optional authenticated reads of the fixture campaign's event-heavy
  Flow → Step → Landing statistics table.

Each category has separate latency, error, dropped-iteration, and exact status
classification. Expected 422 responses are not mislabeled as transport errors.
Treat a fixture as single-use for measurements: first-write semantics mean a
repeated run would exercise accepted retries instead of JSON writes, so
teardown and recreate it before every measured run.

To include statistics reads, pass the admin statistics URL. The fixture setup
creates table 0 and `{campaign_id}` is replaced from the manifest:

```powershell
.\tests\load\run.ps1 -Profile smoke -Scenario events `
  -StatsUrl "http://localhost:8080/admin/statistics.php?campId={campaign_id}&table=0&password=YOUR_PASSWORD"
```

The URL is supplied at runtime and is never written to the fixture manifest.

The storage/statistics benchmark never uses the runtime DB. It creates a
one-off SQLite file, seeds realistic Flow → Step → Landing data, exercises the
production event-write methods, verifies exact aggregates and nearest-rank P75,
checks portable regression budgets, and removes the DB. At the default
30,000-step scale, event writes must stay below 25 ms P95 and each full
statistics query below 2 seconds; the latter catches the previous 3+ second
query plan while retaining headroom over the current roughly 1 second result.
The same run also verifies 10,000 high-cardinality leaf groups with a separate
2-second budget:

```powershell
php tests/load/benchmark_events.php

# Small correctness/syntax smoke:
php tests/load/benchmark_events.php --clicks=500 --steps=2 --writes=200 --stats-runs=1
```

Useful overrides are `--write-p95-budget-ms`, `--stats-budget-ms`,
`--high-cardinality-groups`, `--high-cardinality-budget-ms`, and `--keep-db`.
Run `php tests/load/benchmark_events.php --help` for the complete list.

### One-off ywbtest Events demo rebuild

`rebuild_events_demo.php` is an operator tool for a disposable ywbtest/test
database, not a runtime migration. It fully recreates the configured SQLite
database from the current schema and seeds an Events demo:

```powershell
php tests/load/rebuild_events_demo.php --confirm=REBUILD:clicks.db --domain=ywbtest.site
```

The script backs up the old database and settings, runs SQLite
[`PRAGMA quick_check`](https://www.sqlite.org/pragma.html#pragma_quick_check)
before and after the atomic swap, verifies the unchanged
settings hash, and rebuilds the runtime campaign cache. A post-swap failure
restores the old database and cache and removes landings created by that run.
If rollback itself fails, both database files are preserved and their exact
paths are printed.

The script lock prevents overlapping rebuild commands only. It does not stop
or coordinate live PHP requests and must not be run under live traffic.

## Run All Scenarios

```powershell
.\tests\load\run.ps1                                    # full run
.\tests\load\run.ps1 -Profile smoke                     # quick validation
.\tests\load\run.ps1 -Profile smoke -BaseUrl http://remote-server:8080
.\tests\load\run.ps1 -Scenario mixed                    # single scenario
.\tests\load\run.ps1 -Scenario events                   # after setup_events.php
```

## Profiles

| Profile | Max VUs | Duration | Use case |
|---------|---------|----------|----------|
| `smoke` | 10 | ~40s | Validate scripts work |
| `standard` | 1000 | ~5min | Find breaking point |
| `light` | 100 | ~3.5min | JS-heavy scenarios |
| `heavy` | 1000 | ~5min | SQLite stress |

Override: `k6 run -e PROFILE=smoke loadtest/k6/scenarios/mixed.js`

## Key Metrics to Watch

- **http_reqs** — total RPS achieved
- **http_req_duration** — p50/p95/p99 latency
- **http_req_failed** — error rate (watch for SQLite BUSY → 500s)
- **mixed_white_duration** / **mixed_black_duration** — per-type latency
- **sqlite_busy_errors** — SQLite lock contention counter
- **events_unique_ordinary_duration** / **events_unique_performance_duration** — first-write latency
- **events_retry_duration** — accepted duplicate-retry latency under contention
- **events_rejection_duration** — expected unknown-event 422 latency
- **events_stats_duration** — optional concurrent Flow → Step → Landing report latency
- **events_unexpected_errors** — any wrong status/body or transport failure

## Important Notes

1. **Disable VPN/proxy filter** during tests — `is_proxy_or_vpn()` makes external HTTP calls with 5s timeouts that will rate-limit and skew results
2. **Set `debug` to `false`** in `settings.php` for realistic results (JS obfuscation adds CPU load)
3. **Run k6 from a different machine** than the PHP server for accurate measurements
4. **Monitor server resources** during tests: CPU, RAM, disk I/O (use `htop`, Task Manager, or `perfmon`)
5. **SQLite WAL mode** is already configured — good for concurrent reads, but writes are serialized
6. **PHP sessions** use file-based storage by default — this will be a bottleneck under high concurrency

## Interpreting Results

### Healthy
```
http_req_duration p(95) < 500ms
http_req_failed   rate < 1%
```

### Degraded
```
http_req_duration p(95) 500ms-2000ms
http_req_failed   rate 1-5%
```
→ SQLite write contention starting, session lock contention

### Breaking Point
```
http_req_duration p(95) > 2000ms
http_req_failed   rate > 5%
```
→ SQLite BUSY timeouts, PHP process limits reached

## After Testing: Optimization Ideas

Based on what bottleneck you hit first:

- **SQLite writes slow** → batch inserts, async click logging, or switch to MySQL/PostgreSQL
- **Session lock contention** → switch to Redis sessions or stateless tokens
- **DeviceDetector CPU** → pre-warm cache, simplify UA parsing for known patterns
- **MaxMind GeoIP** → load DB into memory (mmap is already configured)
- **PHP process limits** → use php-fpm with nginx instead of built-in server
