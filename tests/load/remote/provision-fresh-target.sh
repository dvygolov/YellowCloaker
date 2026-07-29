#!/usr/bin/env bash
set -euo pipefail

domain="${1:?benchmark domain required}"
app_root="${2:?application root required}"
expected_commit="${3:?published commit required}"
source_root=/opt/yellowtds-source
site_root="$(dirname "$app_root")"

[[ "$(id -u)" == 0 ]] || { echo 'Must run as root' >&2; exit 2; }
[[ "$domain" =~ ^[A-Za-z0-9.-]+$ ]] || { echo 'Unsafe benchmark domain' >&2; exit 2; }
[[ "$app_root" =~ ^/[A-Za-z0-9._/-]+$ ]] || { echo 'Unsafe application root' >&2; exit 2; }
[[ "$expected_commit" =~ ^[0-9a-f]{40}$ ]] || { echo 'Expected a full Git commit hash' >&2; exit 2; }

export DEBIAN_FRONTEND=noninteractive
apt-get update
apt-get install -y --no-install-recommends git ca-certificates curl openssl sysstat python3

if [[ -d "$source_root/.git" ]]; then
    git -C "$source_root" fetch --depth=1 origin multipleconfigs
    git -C "$source_root" checkout --detach "$expected_commit"
else
    git clone --depth=1 --branch multipleconfigs https://github.com/dvygolov/YellowTDS.git "$source_root"
fi
actual_commit="$(git -C "$source_root" rev-parse HEAD)"
[[ "$actual_commit" == "$expected_commit" ]] || {
    echo "Published commit mismatch: expected $expected_commit, got $actual_commit" >&2
    exit 1
}

YELLOWTDS_DOMAIN="$domain" \
YELLOWTDS_APP_DIR="$app_root" \
YELLOWTDS_ADMIN_PATH=benchmarkadmin \
SKIP_SSL=1 \
bash "$source_root/install.sh"

certificate_dir="/etc/ssl/yellowtds-benchmark"
mkdir -p "$certificate_dir"
openssl req -x509 -newkey rsa:2048 -nodes -days 7 \
    -keyout "$certificate_dir/key.pem" \
    -out "$certificate_dir/cert.pem" \
    -subj "/CN=$domain" \
    -addext "subjectAltName=DNS:$domain"
chmod 0600 "$certificate_dir/key.pem"

nginx_site="/etc/nginx/sites-enabled/$domain"
sed -i "/listen 80;/a\    listen 443 ssl;\n    ssl_certificate $certificate_dir/cert.pem;\n    ssl_certificate_key $certificate_dir/key.pem;" "$nginx_site"
nginx -t
systemctl reload nginx

mkdir -p "$site_root/loadtest"
touch "$site_root/loadtest/.yellowtds-loadtest-created"
chown -R root:root "$site_root/loadtest"

runuser -u www-data -- php -r 'require $argv[1] . "/db/db.php"; exit($db->rebuild_runtime_cache() ? 0 : 1);' "$app_root"
curl --fail --silent --show-error --insecure --resolve "$domain:443:127.0.0.1" "https://$domain/robots.txt" >/dev/null

printf 'commit=%s\n' "$actual_commit"
printf 'domain=%s\n' "$domain"
printf 'app_root=%s\n' "$app_root"
printf 'php=%s\n' "$(php -r 'echo PHP_VERSION;')"
printf 'fpm_max_children=%s\n' "$(awk -F= '/^[[:space:]]*pm.max_children[[:space:]]*=/{gsub(/[[:space:]]/,"",$2); print $2}' /etc/php/8.4/fpm/pool.d/www.conf)"
printf 'opcache=%s\n' "$(php -r 'echo function_exists("opcache_get_status") ? "available" : "missing";')"
printf 'apcu=%s\n' "$(php -r 'echo function_exists("apcu_fetch") ? "available" : "missing";')"
