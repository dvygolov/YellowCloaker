#!/usr/bin/env bash
set -euo pipefail

domain="${YELLOWTDS_BENCH_DOMAIN:-ywbtest.site}"
app_root="${YELLOWTDS_BENCH_APP_ROOT:-/var/www/ywbtest.site/fromfolder}"
scheme="${YELLOWTDS_BENCH_SCHEME:-https}"
site_root="$(dirname "$app_root")"
db="$app_root/db/clicks.db"
nginx_site="${YELLOWTDS_BENCH_NGINX_SITE:-/etc/nginx/sites-enabled/$domain}"
fpm_pool=/etc/php/8.4/fpm/pool.d/www.conf
stamp="$(date -u +%Y%m%dT%H%M%SZ)"
backup_dir="/var/backups/yellowtds-loadtest/$stamp"
state_file=/root/yellowtds-loadtest-backup-dir

if [[ "$(id -u)" != 0 ]]; then
    echo 'Must run as root' >&2
    exit 2
fi
[[ "$domain" =~ ^[A-Za-z0-9.-]+$ ]] || { echo 'Unsafe benchmark domain' >&2; exit 2; }
[[ "$app_root" =~ ^/[A-Za-z0-9._/-]+$ ]] || { echo 'Unsafe application root' >&2; exit 2; }
[[ "$scheme" == http || "$scheme" == https ]] || { echo 'Unsupported benchmark scheme' >&2; exit 2; }
if [[ ! -f "$db" || ! -f "$nginx_site" || ! -f "$fpm_pool" ]]; then
    echo 'Expected target files are missing' >&2
    exit 2
fi

mkdir -p "$backup_dir/results"
printf '%s\n' "$backup_dir" > "$state_file"
cp -a "$nginx_site" "$backup_dir/nginx-site.conf"
cp -a "$fpm_pool" "$backup_dir/php-fpm-www.conf"
cp -a "$app_root/settings.php" "$backup_dir/settings.php"
if [[ -f "$app_root/settings.local.php" ]]; then
    cp -a "$app_root/settings.local.php" "$backup_dir/settings.local.php"
fi

{
    date -u --iso-8601=seconds
    hostnamectl
    uname -a
    lscpu
    free -h
    df -h "$site_root"
    php -v
    nginx -v 2>&1
    php-fpm8.4 -tt 2>&1
    systemctl status php8.4-fpm nginx --no-pager
} > "$backup_dir/system-before.txt" 2>&1
find "$app_root" -type f -not -path '*/caching/*' -not -path '*/logs/*' -print0 \
    | sort -z | xargs -0 sha256sum > "$backup_dir/code-sha256.txt"

systemctl stop php8.4-fpm
php -r '$p=$argv[1];$d=new SQLite3($p,SQLITE3_OPEN_READWRITE);$d->busyTimeout(30000);if($d->exec("PRAGMA wal_checkpoint(TRUNCATE)")===false){fwrite(STDERR,$d->lastErrorMsg());exit(1);} $d->close();' "$db"
cp -a "$db" "$backup_dir/clicks.db"
sha256sum "$backup_dir/clicks.db" | sed "s#$backup_dir/clicks.db#$db#" > "$backup_dir/database-sha256.txt"
php -r '$d=new SQLite3($argv[1],SQLITE3_OPEN_READONLY);foreach(["campaigns","clicks","click_steps","conversions","blocked","trafficback"] as $t){echo $t,"=",$d->querySingle("SELECT COUNT(*) FROM ".$t),PHP_EOL;}echo "quick_check=",$d->querySingle("PRAGMA quick_check"),PHP_EOL;' "$backup_dir/clicks.db" > "$backup_dir/database-counts.txt"
systemctl start php8.4-fpm

cat > /etc/nginx/conf.d/yellowtds-benchmark-log.conf <<'EOF'
log_format yellowtds_bench '$msec\t$request_id\t$status\t$request_time\t$upstream_connect_time\t$upstream_header_time\t$upstream_response_time\t$upstream_status\t$request_method\t$host\t$request_uri';
EOF
sed -i '/client_max_body_size 100M;/a\    access_log /var/log/nginx/yellowtds-benchmark.log yellowtds_bench;' "$nginx_site"
sed -i '/client_max_body_size 100M;/a\
\n    location = /yellowtds-fpm-status {\
        allow 127.0.0.1;\
        allow 10.114.0.0/16;\
        deny all;\
        include fastcgi_params;\
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;\
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;\
    }' "$nginx_site"
cat >> "$fpm_pool" <<'EOF'

; Temporary YellowTDS benchmark telemetry
pm.status_path = /yellowtds-fpm-status
request_slowlog_timeout = 1s
slowlog = /var/log/php8.4-fpm-yellowtds-slow.log
EOF

install -o www-data -g adm -m 0640 /dev/null /var/log/nginx/yellowtds-benchmark.log
install -o root -g adm -m 0640 /dev/null /var/log/php8.4-fpm-yellowtds-slow.log
php-fpm8.4 -t
nginx -t
systemctl restart php8.4-fpm
systemctl reload nginx
port=80
curl_tls=()
if [[ "$scheme" == https ]]; then
    port=443
    curl_tls=(--insecure)
fi
curl --fail --silent --show-error "${curl_tls[@]}" --resolve "$domain:$port:127.0.0.1" "$scheme://$domain/yellowtds-fpm-status" >/dev/null
curl --fail --silent --show-error "${curl_tls[@]}" --resolve "$domain:$port:127.0.0.1" "$scheme://$domain/" >/dev/null

echo "$backup_dir"
