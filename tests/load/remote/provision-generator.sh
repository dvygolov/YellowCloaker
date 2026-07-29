#!/usr/bin/env bash
set -euo pipefail

root="${1:-/opt/yellowtds-loadtest}"
[[ "$(id -u)" == 0 ]] || { echo 'Must run as root' >&2; exit 2; }
[[ "$root" =~ ^/[A-Za-z0-9._/-]+$ ]] || { echo 'Unsafe loadtest root' >&2; exit 2; }

export DEBIAN_FRONTEND=noninteractive
apt-get update
apt-get install -y --no-install-recommends ca-certificates curl gnupg nginx sysstat python3 gzip
mkdir -p /etc/apt/keyrings
curl -fsSL https://dl.k6.io/key.gpg | gpg --dearmor --yes -o /etc/apt/keyrings/k6-archive-keyring.gpg
printf '%s\n' 'deb [signed-by=/etc/apt/keyrings/k6-archive-keyring.gpg] https://dl.k6.io/deb stable main' > /etc/apt/sources.list.d/k6.list
apt-get update
apt-get install -y k6

cat > /etc/nginx/sites-available/yellowtds-generator-calibration <<'EOF'
server {
    listen 127.0.0.1:80 default_server;
    server_name _;
    access_log off;
    location / {
        default_type text/plain;
        return 200 'ok';
    }
}
EOF
rm -f /etc/nginx/sites-enabled/default
ln -sf /etc/nginx/sites-available/yellowtds-generator-calibration /etc/nginx/sites-enabled/yellowtds-generator-calibration
nginx -t
systemctl restart nginx

mkdir -p "$root/k6/scenarios" "$root/results"
printf 'k6=%s\n' "$(k6 version | head -n 1)"
printf 'root=%s\n' "$root"
