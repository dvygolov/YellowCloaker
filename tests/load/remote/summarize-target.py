#!/usr/bin/env python3
import csv
import json
import math
import pathlib
import sys

prefix = pathlib.Path(sys.argv[1])

def percentile(values, percentile_value):
    if not values:
        return None
    ordered = sorted(values)
    index = (len(ordered) - 1) * percentile_value / 100
    lower = math.floor(index)
    upper = math.ceil(index)
    if lower == upper:
        return ordered[lower]
    return ordered[lower] + (ordered[upper] - ordered[lower]) * (index - lower)

timestamps = []
request_times = []
upstream_times = []
statuses = {}
with open(str(prefix) + '.nginx.tsv', encoding='utf-8') as handle:
    for raw_line in handle:
        fields = raw_line.rstrip('\n').split('\t')
        if len(fields) < 11 or fields[10].startswith('/yellowtds-fpm-status'):
            continue
        try:
            timestamps.append(float(fields[0]))
            request_times.append(float(fields[3]) * 1000)
            if fields[6] not in ('', '-'):
                upstream_times.append(float(fields[6].split(',')[-1].strip()) * 1000)
            statuses[fields[2]] = statuses.get(fields[2], 0) + 1
        except ValueError:
            continue

cpu = []
iowait = []
steal = []
with open(str(prefix) + '.vmstat.txt', encoding='utf-8') as handle:
    for raw_line in handle:
        fields = raw_line.split()
        if len(fields) >= 18 and fields[0].isdigit() and fields[-1].count(':') == 2:
            cpu.append(float(fields[12]) + float(fields[13]))
            iowait.append(float(fields[15]))
            steal.append(float(fields[16]))
if len(cpu) > 1:
    cpu, iowait, steal = cpu[1:], iowait[1:], steal[1:]

server_rows = []
with open(str(prefix) + '.server.csv', newline='', encoding='utf-8') as handle:
    server_rows = list(csv.DictReader(handle))

def numeric_max(column, default=0):
    values = [float(row[column]) for row in server_rows if row.get(column) not in (None, '')]
    return max(values, default=default)

def numeric_min(column):
    values = [float(row[column]) for row in server_rows if row.get(column) not in (None, '')]
    return min(values) if values else None

elapsed = max(timestamps) - min(timestamps) if len(timestamps) > 1 else None
result = {
    'requests': len(request_times),
    'observed_rps': len(request_times) / elapsed if elapsed and elapsed > 0 else None,
    'status_counts': statuses,
    'request_time_p50_ms': percentile(request_times, 50),
    'request_time_p95_ms': percentile(request_times, 95),
    'request_time_p99_ms': percentile(request_times, 99),
    'request_time_max_ms': max(request_times, default=None),
    'upstream_time_p50_ms': percentile(upstream_times, 50),
    'upstream_time_p95_ms': percentile(upstream_times, 95),
    'upstream_time_p99_ms': percentile(upstream_times, 99),
    'upstream_time_max_ms': max(upstream_times, default=None),
    'target_cpu_avg_percent': sum(cpu) / len(cpu) if cpu else None,
    'target_cpu_max_percent': max(cpu) if cpu else None,
    'target_iowait_avg_percent': sum(iowait) / len(iowait) if iowait else None,
    'target_steal_avg_percent': sum(steal) / len(steal) if steal else None,
    'mem_available_min_mb': numeric_min('mem_available_kb') / 1024 if server_rows else None,
    'fpm_active_max': numeric_max('fpm_active'),
    'fpm_queue_max': numeric_max('fpm_queue'),
    'fpm_max_children_reached_delta': numeric_max('fpm_max_children') - float(server_rows[0]['fpm_max_children']) if server_rows else None,
    'wal_bytes_max': numeric_max('wal_bytes'),
    'db_bytes_max': numeric_max('db_bytes'),
}
with open(str(prefix) + '.target-summary.json', 'w', encoding='utf-8') as handle:
    json.dump(result, handle, indent=2)
print(json.dumps(result, separators=(',', ':')))
