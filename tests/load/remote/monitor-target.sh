#!/usr/bin/env bash
set -euo pipefail

duration="${1:?duration seconds required}"
prefix="${2:?output prefix required}"
domain="${YELLOWTDS_BENCH_DOMAIN:-ywbtest.site}"
app_root="${YELLOWTDS_BENCH_APP_ROOT:-/var/www/ywbtest.site/fromfolder}"
scheme="${YELLOWTDS_BENCH_SCHEME:-https}"
db="$app_root/db/clicks.db"

[[ "$domain" =~ ^[A-Za-z0-9.-]+$ ]] || { echo 'Unsafe benchmark domain' >&2; exit 2; }
[[ "$app_root" =~ ^/[A-Za-z0-9._/-]+$ ]] || { echo 'Unsafe application root' >&2; exit 2; }
[[ "$scheme" == http || "$scheme" == https ]] || { echo 'Unsupported benchmark scheme' >&2; exit 2; }
port=80
curl_tls=()
if [[ "$scheme" == https ]]; then
    port=443
    curl_tls=(--insecure)
fi

mkdir -p "$(dirname "$prefix")"
vmstat -w -t 1 "$((duration + 1))" > "${prefix}.vmstat.txt" &
vmstat_pid=$!
iostat -dx 1 "$duration" > "${prefix}.iostat.txt" &
iostat_pid=$!
printf 'timestamp,mem_available_kb,load1,fpm_active,fpm_idle,fpm_queue,fpm_max_children,wal_bytes,db_bytes\n' > "${prefix}.server.csv"

for ((i=0; i<duration; i++)); do
    status="$(curl --silent --show-error --max-time 2 "${curl_tls[@]}" --resolve "$domain:$port:127.0.0.1" "$scheme://$domain/yellowtds-fpm-status?full" || true)"
    active="$(awk -F: '/^active processes:/{gsub(/ /,"",$2);print $2}' <<<"$status")"
    idle="$(awk -F: '/^idle processes:/{gsub(/ /,"",$2);print $2}' <<<"$status")"
    queue="$(awk -F: '/^listen queue:/{gsub(/ /,"",$2);print $2}' <<<"$status")"
    max_children="$(awk -F: '/^max children reached:/{gsub(/ /,"",$2);print $2}' <<<"$status")"
    mem="$(awk '/MemAvailable:/{print $2}' /proc/meminfo)"
    load1="$(awk '{print $1}' /proc/loadavg)"
    wal="$(stat -c %s "$db-wal" 2>/dev/null || printf '0')"
    db_size="$(stat -c %s "$db")"
    printf '%s,%s,%s,%s,%s,%s,%s,%s,%s\n' "$(date -u +%FT%TZ)" "$mem" "$load1" "${active:-0}" "${idle:-0}" "${queue:-0}" "${max_children:-0}" "$wal" "$db_size" >> "${prefix}.server.csv"
    sleep 1
done

wait "$vmstat_pid" "$iostat_pid"
