#!/usr/bin/env bash
set -euo pipefail

domain="${YELLOWTDS_BENCH_DOMAIN:-ywbtest.site}"
app_root="${YELLOWTDS_BENCH_APP_ROOT:-/var/www/ywbtest.site/fromfolder}"
scheme="${YELLOWTDS_BENCH_SCHEME:-https}"
site_root="$(dirname "$app_root")"
db="$app_root/db/clicks.db"
nginx_site="${YELLOWTDS_BENCH_NGINX_SITE:-/etc/nginx/sites-enabled/$domain}"
fpm_pool=/etc/php/8.4/fpm/pool.d/www.conf
state_file=/root/yellowtds-loadtest-backup-dir
backup_dir="${1:-$(cat "$state_file")}"

[[ "$domain" =~ ^[A-Za-z0-9.-]+$ ]] || { echo 'Unsafe benchmark domain' >&2; exit 2; }
[[ "$app_root" =~ ^/[A-Za-z0-9._/-]+$ ]] || { echo 'Unsafe application root' >&2; exit 2; }
[[ "$scheme" == http || "$scheme" == https ]] || { echo 'Unsupported benchmark scheme' >&2; exit 2; }

case "$backup_dir" in
    /var/backups/yellowtds-loadtest/*) ;;
    *) echo "Unsafe backup path: $backup_dir" >&2; exit 2 ;;
esac
for required in clicks.db nginx-site.conf php-fpm-www.conf database-sha256.txt; do
    [[ -f "$backup_dir/$required" ]] || { echo "Missing $backup_dir/$required" >&2; exit 2; }
done

systemctl stop php8.4-fpm
rm -f "$db" "$db-wal" "$db-shm"
cp -a "$backup_dir/clicks.db" "$db"
if [[ -f "$backup_dir/settings.local.php" ]]; then
    cp -a "$backup_dir/settings.local.php" "$app_root/settings.local.php"
fi
cp -a "$backup_dir/nginx-site.conf" "$nginx_site"
cp -a "$backup_dir/php-fpm-www.conf" "$fpm_pool"
rm -f /etc/nginx/conf.d/yellowtds-benchmark-log.conf
rm -rf "$app_root/loadtest-html"
rm -rf "$app_root/caching/landings/loadtest-html"
if [[ -f "$site_root/loadtest/.yellowtds-loadtest-created" ]]; then
    rm -rf "$site_root/loadtest"
fi

php -r 'require $argv[1] . "/db/db.php"; exit($db->rebuild_runtime_cache() ? 0 : 1);' "$app_root"

php-fpm8.4 -t
nginx -t
systemctl start php8.4-fpm
systemctl reload nginx

sha256sum -c "$backup_dir/database-sha256.txt"
php -r '$d=new SQLite3($argv[1],SQLITE3_OPEN_READONLY);foreach(["campaigns","clicks","click_steps","conversions","blocked","trafficback"] as $t){echo $t,"=",$d->querySingle("SELECT COUNT(*) FROM ".$t),PHP_EOL;}echo "quick_check=",$d->querySingle("PRAGMA quick_check"),PHP_EOL;' "$db" | tee "$backup_dir/database-restored.txt"
diff -u "$backup_dir/database-counts.txt" "$backup_dir/database-restored.txt"
port=80
curl_tls=()
if [[ "$scheme" == https ]]; then
    port=443
    curl_tls=(--insecure)
fi
curl --fail --silent --show-error "${curl_tls[@]}" --resolve "$domain:$port:127.0.0.1" "$scheme://$domain/robots.txt" >/dev/null
echo "Restored from $backup_dir"
