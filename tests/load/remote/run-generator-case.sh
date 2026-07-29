#!/usr/bin/env bash
set -euo pipefail

name="${1:?case name required}"
rate="${2:?rate required}"
seconds="${3:?duration seconds required}"
traffic="${4:-black}"
identity="${5:-new}"
base_url="${6:-https://ywbtest.site/fromfolder}"
result_set="${7:-ywbtest}"
root="${YELLOWTDS_LOADTEST_ROOT:-/opt/yellowtds-loadtest}"

[[ "$name" =~ ^[A-Za-z0-9._-]+$ ]] || { echo 'Unsafe case name' >&2; exit 2; }
[[ "$rate" =~ ^[0-9]+$ && "$seconds" =~ ^[0-9]+$ ]] || { echo 'Rate and duration must be integers' >&2; exit 2; }
[[ "$base_url" =~ ^https?://[A-Za-z0-9.-]+(:[0-9]+)?(/[A-Za-z0-9._~/-]*)?$ ]] || { echo 'Unsafe base URL' >&2; exit 2; }
[[ "$result_set" =~ ^[A-Za-z0-9._-]+$ ]] || { echo 'Unsafe result set' >&2; exit 2; }

result_dir="$root/results/$result_set"
prefix="$result_dir/$name"
mkdir -p "$result_dir"

k6_tls=()
if [[ "${YELLOWTDS_INSECURE_TLS:-0}" == 1 ]]; then
    k6_tls=(--insecure-skip-tls-verify)
fi

vmstat -w -t 1 "$((seconds + 2))" > "${prefix}.generator-vmstat.txt" &
monitor=$!
set +e
k6 run "${k6_tls[@]}" --quiet --summary-mode=full \
    --summary-export "${prefix}.summary.json" \
    --out "csv=${prefix}.metrics.csv" \
    -e BASE_URL="$base_url" \
    -e RATE="$rate" -e DURATION="${seconds}s" \
    -e PREALLOCATED_VUS="$(printf '%d' "$((rate < 20 ? 20 : rate))")" \
    -e MAX_VUS="$((rate * 4 < 80 ? 80 : rate * 4))" \
    -e TRAFFIC="$traffic" -e IDENTITY="$identity" \
    "$root/k6/scenarios/arrival-rate.js" > "${prefix}.stdout.txt" 2>&1
k6_exit=$?
set -e
wait "$monitor"
gzip -f "${prefix}.metrics.csv"

python3 - "$prefix" "$rate" "$k6_exit" <<'PY'
import json
import sys

prefix, requested_rate, k6_exit = sys.argv[1], int(sys.argv[2]), int(sys.argv[3])
with open(prefix + '.summary.json', encoding='utf-8') as handle:
    metrics = json.load(handle)['metrics']

def value(name, key, default=0):
    return metrics.get(name, {}).get(key, default)

cpu = []
steal = []
free_kb = []
with open(prefix + '.generator-vmstat.txt', encoding='utf-8') as handle:
    for line in handle:
        fields = line.split()
        if len(fields) >= 18 and fields[0].isdigit() and fields[-1].count(':') == 2:
            cpu.append(float(fields[12]) + float(fields[13]))
            steal.append(float(fields[16]))
            free_kb.append(int(fields[3]))
if len(cpu) > 1:
    cpu, steal, free_kb = cpu[1:], steal[1:], free_kb[1:]

result = {
    'requested_rps': requested_rate,
    'achieved_rps': value('http_reqs', 'rate'),
    'iterations': value('iterations', 'count'),
    'dropped_iterations': value('dropped_iterations', 'count'),
    'http_failed_rate': value('http_req_failed', 'value'),
    'p50_ms': value('http_req_duration', 'med'),
    'p95_ms': value('http_req_duration', 'p(95)'),
    'p99_ms': value('http_req_duration', 'p(99)'),
    'max_ms': value('http_req_duration', 'max'),
    'k6_exit_code': k6_exit,
    'generator_cpu_avg_percent': sum(cpu) / len(cpu) if cpu else None,
    'generator_cpu_max_percent': max(cpu) if cpu else None,
    'generator_steal_avg_percent': sum(steal) / len(steal) if steal else None,
    'generator_mem_free_min_mb': min(free_kb) / 1024 if free_kb else None,
}
with open(prefix + '.generator-summary.json', 'w', encoding='utf-8') as handle:
    json.dump(result, handle, indent=2)
print(json.dumps(result, separators=(',', ':')))
PY
