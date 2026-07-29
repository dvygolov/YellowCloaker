#!/usr/bin/env bash
set -euo pipefail

name="${1:?case name required}"
seconds="${2:?duration seconds required}"
backup_dir="${3:-$(cat /root/yellowtds-loadtest-backup-dir)}"
[[ "$name" =~ ^[A-Za-z0-9._-]+$ ]] || { echo 'Unsafe case name' >&2; exit 2; }
[[ "$seconds" =~ ^[0-9]+$ ]] || { echo 'Duration must be an integer' >&2; exit 2; }
case "$backup_dir" in
    /var/backups/yellowtds-loadtest/*) ;;
    *) echo "Unsafe backup path: $backup_dir" >&2; exit 2 ;;
esac

result_dir="$backup_dir/results"
prefix="$result_dir/$name"
ready_file="/run/yellowtds-benchmark-$name.ready"
trap 'rm -f "$ready_file"' EXIT
mkdir -p "$result_dir"
: > /var/log/nginx/yellowtds-benchmark.log
: > /var/log/php8.4-fpm-yellowtds-slow.log
touch "$ready_file"
/root/monitor-target.sh "$seconds" "$prefix"
cp /var/log/nginx/yellowtds-benchmark.log "${prefix}.nginx.tsv"
cp /var/log/php8.4-fpm-yellowtds-slow.log "${prefix}.fpm-slow.log"
/root/summarize-target.py "$prefix"
