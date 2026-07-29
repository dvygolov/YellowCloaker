#!/usr/bin/env bash
set -euo pipefail

root="${1:-/opt/yellowtds-loadtest}"
duration="${DURATION:-20s}"
monitor_samples="${MONITOR_SAMPLES:-22}"
mkdir -p "$root/results/calibration"

for rate in 100 250 500 1000; do
    prefix="$root/results/calibration/static-${rate}"
    vmstat -w -t 1 "$monitor_samples" > "${prefix}.vmstat.txt" &
    monitor=$!
    k6 run --quiet --summary-mode=full \
        --summary-export "${prefix}.summary.json" \
        --out "csv=${prefix}.metrics.csv" \
        -e BASE_URL=http://127.0.0.1 \
        -e RATE="$rate" -e DURATION="$duration" \
        -e PREALLOCATED_VUS="$rate" -e MAX_VUS="$((rate * 2))" \
        "$root/k6/scenarios/arrival-rate.js" \
        > "${prefix}.stdout.txt" 2>&1 || true
    wait "$monitor"
    printf 'RATE=%s ' "$rate"
    python3 - "$prefix" <<'PY'
import json
import sys

prefix = sys.argv[1]
with open(prefix + '.summary.json', encoding='utf-8') as handle:
    metrics = json.load(handle)['metrics']

def value(name, key):
    metric = metrics.get(name, {})
    return metric.get('values', metric).get(key)

print(
    'iterations=', value('iterations', 'count'),
    'dropped=', value('dropped_iterations', 'count'),
    'failed=', value('http_req_failed', 'rate'),
    'p95_ms=', value('http_req_duration', 'p(95)'),
)
PY
done
