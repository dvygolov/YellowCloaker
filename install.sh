#!/usr/bin/env bash
set -euo pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

PRODUCT_NAME="YellowTDS"
PHP_VER="8.4"

fail() {
    echo -e "\n${RED}ERROR: $1${NC}" >&2
    echo -e "${YELLOW}Fix the issue and re-run the installer.${NC}" >&2
    exit 1
}

info() {
    echo -e "${YELLOW}$1${NC}"
}

success() {
    echo -e "${GREEN}$1${NC}"
}

usage() {
    cat <<EOF
Usage:
  sudo bash install.sh
  sudo bash install.sh --add-domain

Environment variables:
  YELLOWTDS_DOMAIN       Primary domain for full install
  YELLOWTDS_DOMAINS      Comma-separated domains for --add-domain
  YELLOWTDS_APP_DIR      Installation directory or existing app directory
  YELLOWTDS_REPO_ZIP     Repository ZIP URL for curl-pipe installs
  SKIP_SSL=1             Skip certbot, useful for test environments
EOF
}

MODE="install"
if [ "${1:-}" = "--add-domain" ]; then
    MODE="add-domain"
elif [ "${1:-}" = "-h" ] || [ "${1:-}" = "--help" ]; then
    usage
    exit 0
elif [ "${1:-}" != "" ]; then
    usage
    fail "Unknown argument: $1"
fi

if [[ $EUID -ne 0 ]]; then
    fail "Run this script as root: sudo bash install.sh"
fi

if ! command -v apt-get >/dev/null 2>&1; then
    fail "This installer supports Debian/Ubuntu systems only"
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
OS_ID=""
OS_CODENAME=""
OS_VERSION_ID=""
if [ -r /etc/os-release ]; then
    # shellcheck disable=SC1091
    . /etc/os-release
    OS_ID="${ID:-}"
    OS_CODENAME="${VERSION_CODENAME:-}"
    OS_VERSION_ID="${VERSION_ID:-}"
fi

restart_service() {
    if command -v systemctl >/dev/null 2>&1 && systemctl --version >/dev/null 2>&1; then
        systemctl restart "$1"
    elif command -v service >/dev/null 2>&1; then
        service "$1" restart
    else
        fail "Neither systemctl nor service is available to restart $1"
    fi
}

enable_and_restart_service() {
    if command -v systemctl >/dev/null 2>&1 && systemctl --version >/dev/null 2>&1; then
        systemctl enable "$1" >/dev/null 2>&1 || true
        systemctl restart "$1"
    elif command -v service >/dev/null 2>&1; then
        service "$1" restart
    else
        fail "Neither systemctl nor service is available to start $1"
    fi
}

disable_bullseye_backports() {
    local file
    local updated=1

    if [ -f /etc/apt/sources.list ] && grep -q 'bullseye-backports' /etc/apt/sources.list; then
        cp /etc/apt/sources.list /etc/apt/sources.list.yellowtds.bak
        sed -i '/bullseye-backports/s/^/# disabled by YellowTDS installer: /' /etc/apt/sources.list
        updated=0
    fi

    for file in /etc/apt/sources.list.d/*.list; do
        [ -f "$file" ] || continue
        if grep -q 'bullseye-backports' "$file"; then
            cp "$file" "${file}.yellowtds.bak"
            sed -i '/bullseye-backports/s/^/# disabled by YellowTDS installer: /' "$file"
            updated=0
        fi
    done

    return $updated
}

update_package_lists() {
    local apt_log="/tmp/yellowtds-apt-update.log"

    if apt-get update >"$apt_log" 2>&1; then
        rm -f "$apt_log"
        return 0
    fi

    if [ "$OS_ID" = "debian" ] && [ "$OS_CODENAME" = "bullseye" ] && grep -q 'bullseye-backports' "$apt_log"; then
        info "Detected obsolete bullseye-backports source, disabling it and retrying..."
        disable_bullseye_backports || true
        if apt-get update >"$apt_log" 2>&1; then
            rm -f "$apt_log"
            return 0
        fi
    fi

    cat "$apt_log"
    rm -f "$apt_log"
    return 1
}

ensure_php_repository() {
    if apt-cache show "php${PHP_VER}-fpm" >/dev/null 2>&1; then
        return 0
    fi

    case "$OS_ID" in
        ubuntu)
            if [ "$OS_CODENAME" = "focal" ] || [ "$OS_VERSION_ID" = "20.04" ]; then
                fail "Ubuntu 20.04 is not supported for automatic PHP ${PHP_VER} provisioning. Use Ubuntu 22.04 or newer."
            fi
            info "Adding ondrej/php PPA for PHP ${PHP_VER}..."
            apt-get install -y -qq software-properties-common >/dev/null || fail "Failed to install software-properties-common"
            add-apt-repository -y ppa:ondrej/php >/dev/null || fail "Failed to add ondrej/php PPA"
            update_package_lists || fail "Failed to refresh package lists after adding ondrej/php PPA"
            ;;
        debian)
            info "Adding packages.sury.org PHP repository for PHP ${PHP_VER}..."
            apt-get install -y -qq ca-certificates curl gnupg apt-transport-https >/dev/null || fail "Failed to install repository prerequisites"
            install -d -m 0755 /usr/share/keyrings || fail "Failed to prepare APT keyring directory"
            curl -fsSL https://packages.sury.org/php/apt.gpg | gpg --dearmor > /usr/share/keyrings/debsuryorg-archive-keyring.gpg \
                || fail "Failed to install Sury PHP repository key"
            cat > /etc/apt/sources.list.d/yellowtds-php-sury.list <<EOF
deb [signed-by=/usr/share/keyrings/debsuryorg-archive-keyring.gpg] https://packages.sury.org/php/ ${OS_CODENAME} main
EOF
            update_package_lists || fail "Failed to refresh package lists after adding Sury PHP repository"
            ;;
        *)
            fail "Unsupported distro '${OS_ID}' for automatic PHP ${PHP_VER} provisioning"
            ;;
    esac

    apt-cache show "php${PHP_VER}-fpm" >/dev/null 2>&1 \
        || fail "Could not find PHP ${PHP_VER} after configuring repositories"
}

normalize_domain() {
    local domain="$1"
    domain="${domain#http://}"
    domain="${domain#https://}"
    domain="${domain%%/*}"
    domain="${domain%%:*}"
    domain="$(printf '%s' "$domain" | tr '[:upper:]' '[:lower:]' | xargs)"
    printf '%s' "$domain"
}

validate_domain() {
    local domain="$1"
    [[ "$domain" =~ ^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$ ]]
}

parse_domain_list() {
    local input="$1"
    local raw
    local domain
    local seen=" "
    PARSED_DOMAINS=()

    IFS=',' read -r -a raw_domains <<< "$input"
    for raw in "${raw_domains[@]}"; do
        domain="$(normalize_domain "$raw")"
        [ -n "$domain" ] || continue
        validate_domain "$domain" || fail "Invalid domain: $domain"
        if [[ "$seen" != *" $domain "* ]]; then
            PARSED_DOMAINS+=("$domain")
            seen="${seen}${domain} "
        fi
    done

    [ "${#PARSED_DOMAINS[@]}" -gt 0 ] || fail "At least one domain is required"
}

detect_public_ip() {
    local ip
    ip="$(curl -fsS --max-time 10 https://api.ipify.org 2>/dev/null || true)"
    if [ -z "$ip" ]; then
        ip="$(curl -fsS --max-time 10 https://ifconfig.me 2>/dev/null || true)"
    fi
    [ -n "$ip" ] || fail "Could not detect this server public IP"
    printf '%s' "$ip"
}

resolve_domain_ips() {
    local domain="$1"
    getent ahostsv4 "$domain" 2>/dev/null | awk '{print $1}' | sort -u
}

verify_domain_points_here() {
    local domain="$1"
    local public_ip="$2"
    local resolved

    resolved="$(resolve_domain_ips "$domain" | tr '\n' ' ')"
    if [ -z "$resolved" ]; then
        fail "Domain $domain does not resolve to any IPv4 address"
    fi

    for ip in $resolved; do
        if [ "$ip" = "$public_ip" ]; then
            success "DNS OK: $domain -> $public_ip"
            return 0
        fi
    done

    fail "Domain $domain is not pointed to this server. Expected $public_ip, resolved: $resolved"
}

safe_name() {
    printf '%s' "$1" | LC_ALL=C tr -c 'A-Za-z0-9_-' '_'
}

install_dependencies() {
    info "[1/5] Updating package lists..."
    update_package_lists || fail "Failed to update package lists"

    info "[2/5] Preparing PHP ${PHP_VER} repository..."
    ensure_php_repository

    info "[3/5] Installing nginx, PHP, HTTPS tools, and MMDB build dependencies..."
    apt-get install -y -qq \
        -o Dpkg::Options::="--force-confdef" \
        -o Dpkg::Options::="--force-confold" \
        nginx \
        php${PHP_VER}-fpm php${PHP_VER}-cli php${PHP_VER}-sqlite3 php${PHP_VER}-curl \
        php${PHP_VER}-mbstring php${PHP_VER}-zip php${PHP_VER}-xml php${PHP_VER}-gd \
        php${PHP_VER}-dev php-pear \
        libmaxminddb0 libmaxminddb-dev \
        certbot python3-certbot-nginx curl wget unzip tar ca-certificates \
        build-essential pkg-config \
        || fail "Failed to install required packages"

    enable_and_restart_service "php${PHP_VER}-fpm" || fail "Failed to start PHP-FPM"
    enable_and_restart_service nginx || fail "Failed to start nginx"
}

install_maxmind_extension() {
    info "[4/5] Installing PECL maxminddb extension..."

    if ! php${PHP_VER} -m 2>/dev/null | grep -qi '^maxminddb$'; then
        printf "\n" | pecl install maxminddb || fail "Failed to install PECL maxminddb extension"
    fi

    echo "extension=maxminddb.so" > "/etc/php/${PHP_VER}/mods-available/maxminddb.ini"
    phpenmod -v "${PHP_VER}" maxminddb || true
    restart_service "php${PHP_VER}-fpm" || fail "Failed to restart PHP-FPM after enabling maxminddb"

    php${PHP_VER} -r 'exit(extension_loaded("maxminddb") ? 0 : 1);' \
        || fail "PHP extension maxminddb is not loaded"

    success "MMDB C-extension is installed and loaded"
}

copy_application() {
    local app_dir="$1"
    local repo_zip="${YELLOWTDS_REPO_ZIP:-https://github.com/dvygolov/YellowTDS/archive/refs/heads/main.zip}"
    local temp_dir
    local source_dir

    if [ "$SCRIPT_DIR" = "$app_dir" ]; then
        info "Using current directory as application directory: $app_dir"
        return 0
    fi

    if [ -e "$app_dir" ] && [ "$(find "$app_dir" -mindepth 1 -maxdepth 1 2>/dev/null | wc -l)" -gt 0 ]; then
        read -r -p "Directory $app_dir is not empty. Overwrite application files? (y/N): " confirm < /dev/tty
        if [[ "$confirm" != "y" && "$confirm" != "Y" ]]; then
            fail "Installation cancelled"
        fi
    fi

    mkdir -p "$app_dir"

    if [ -f "$SCRIPT_DIR/index.php" ] && [ -f "$SCRIPT_DIR/bases/ipcountry.php" ]; then
        tar \
            --exclude='./.git' \
            --exclude='./db/*.db' \
            --exclude='./db/*.db-shm' \
            --exclude='./db/*.db-wal' \
            --exclude='./logs/*' \
            --exclude='./ycclogs/*' \
            --exclude='./tmp/*' \
            --exclude='./caching/currency/*' \
            --exclude='./caching/proxyvpn/*' \
            --exclude='./caching/devices/*' \
            --exclude='./caching/whites_curl/*' \
            -C "$SCRIPT_DIR" -cf - . | tar -C "$app_dir" -xf - \
            || fail "Failed to copy application files"
        return 0
    fi

    info "Installer was not run from a YellowTDS checkout; downloading repository ZIP..."
    temp_dir="$(mktemp -d)"
    curl -fsSL "$repo_zip" -o "${temp_dir}/yellowtds.zip" || {
        rm -rf "$temp_dir"
        fail "Failed to download YellowTDS repository ZIP from $repo_zip"
    }
    unzip -q "${temp_dir}/yellowtds.zip" -d "$temp_dir" || {
        rm -rf "$temp_dir"
        fail "Failed to extract YellowTDS repository ZIP"
    }
    source_dir="$(find "$temp_dir" -mindepth 1 -maxdepth 1 -type d | head -n 1)"
    [ -n "$source_dir" ] && [ -f "$source_dir/index.php" ] || {
        rm -rf "$temp_dir"
        fail "Downloaded repository ZIP does not look like YellowTDS"
    }
    tar \
        --exclude='./.git' \
        --exclude='./db/*.db' \
        --exclude='./db/*.db-shm' \
        --exclude='./db/*.db-wal' \
        --exclude='./logs/*' \
        --exclude='./ycclogs/*' \
        --exclude='./tmp/*' \
        --exclude='./caching/currency/*' \
        --exclude='./caching/proxyvpn/*' \
        --exclude='./caching/devices/*' \
        --exclude='./caching/whites_curl/*' \
        -C "$source_dir" -cf - . | tar -C "$app_dir" -xf - \
        || {
            rm -rf "$temp_dir"
            fail "Failed to copy downloaded application files"
        }
    rm -rf "$temp_dir"
}

set_permissions() {
    local app_dir="$1"

    mkdir -p "$app_dir/db" "$app_dir/logs" "$app_dir/ycclogs" "$app_dir/tmp" \
        "$app_dir/caching/landings" "$app_dir/caching/whites" \
        "$app_dir/caching/whites_curl" "$app_dir/caching/devices" \
        "$app_dir/caching/currency" "$app_dir/caching/proxyvpn"

    find "$app_dir" -type d -exec chmod 0755 {} \;
    find "$app_dir" -type f -exec chmod 0644 {} \;
    [ -f "$app_dir/install.sh" ] && chmod 0755 "$app_dir/install.sh"

    chown -R root:root "$app_dir"
    chown -R www-data:www-data \
        "$app_dir/db" "$app_dir/logs" "$app_dir/ycclogs" "$app_dir/tmp" \
        "$app_dir/caching" "$app_dir/bases"

    find "$app_dir/db" "$app_dir/logs" "$app_dir/ycclogs" "$app_dir/tmp" "$app_dir/caching" "$app_dir/bases" -type d -exec chmod 0775 {} \;
    find "$app_dir/db" "$app_dir/logs" "$app_dir/ycclogs" "$app_dir/tmp" "$app_dir/caching" "$app_dir/bases" -type f -exec chmod 0664 {} \;
}

download_sapics_database() {
    local source_name="$1"
    local output_name="$2"
    local target_dir="$3"
    local temp_dir
    local temp_file

    temp_dir="$(mktemp -d)"
    temp_file="${temp_dir}/${output_name}"

    curl -fsSL "https://github.com/sapics/ip-location-db/releases/download/latest/${source_name}" \
        -o "$temp_file" || {
            rm -rf "$temp_dir"
            fail "Failed to download ${source_name} from sapics/ip-location-db"
        }

    mv "$temp_file" "${target_dir}/${output_name}" || {
        rm -rf "$temp_dir"
        fail "Failed to install ${output_name}"
    }
    rm -rf "$temp_dir"
}

download_geo_databases() {
    local app_dir="$1"

    info "Downloading GeoBases from sapics/ip-location-db..."
    mkdir -p "$app_dir/bases"
    download_sapics_database "geolite2-country.mmdb" "country.mmdb" "$app_dir/bases"
    download_sapics_database "origin-asn.mmdb" "asn.mmdb" "$app_dir/bases"
    chown www-data:www-data "$app_dir/bases/country.mmdb" "$app_dir/bases/asn.mmdb"
    chmod 0664 "$app_dir/bases/country.mmdb" "$app_dir/bases/asn.mmdb"
    date '+%d.%m.%y' > "$app_dir/bases/update.txt"
    chown www-data:www-data "$app_dir/bases/update.txt"
    chmod 0664 "$app_dir/bases/update.txt"
    success "GeoBases downloaded"
}

setup_currency_cron() {
    local app_dir="$1"
    local php_bin
    local cron_file="/etc/cron.d/yellowtds-currency"

    php_bin="$(command -v php${PHP_VER} || command -v php)"
    [ -n "$php_bin" ] || fail "PHP CLI binary not found for currency cron"

    cat > "$cron_file" <<EOF
SHELL=/bin/sh
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
0 * * * * www-data cd ${app_dir} && ${php_bin} ${app_dir}/cron/refresh_currency_rates.php >> ${app_dir}/logs/currency-cron.log 2>&1
EOF

    chmod 0644 "$cron_file"
}

write_nginx_config() {
    local domain="$1"
    local app_dir="$2"
    local config_file="/etc/nginx/sites-available/${domain}"

    cat > "$config_file" <<EOF
server {
    listen 80;
    server_name ${domain};
    root ${app_dir};
    index index.php index.html;

    access_log /var/log/nginx/${domain}.access.log;
    error_log /var/log/nginx/${domain}.error.log;

    client_max_body_size 100M;

    location ^~ /.well-known/acme-challenge/ {
        allow all;
    }

    location ~ /\.(?!well-known) {
        deny all;
    }

    location = /settings.php {
        deny all;
    }

    location ~* \.(?:db|sqlite|sqlite3|db-wal|db-shm|sql|env|log|cache|bak|old|orig|swp|md)$ {
        deny all;
    }

    location ~* ^/(?:composer\.(?:json|lock)|phpunit\.xml|agents\.md|AGENTS\.md)$ {
        deny all;
    }

    location ~* ^/(?:db|logs|ycclogs|tmp)(?:/|$) {
        deny all;
    }

    location ~* ^/caching/(?:devices|currency|proxyvpn|whites_curl)(?:/|$) {
        deny all;
    }

    location ~* ^/bases/.*\.(?:mmdb|phar|txt)$ {
        deny all;
    }

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php${PHP_VER}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }
}
EOF

    ln -sf "$config_file" "/etc/nginx/sites-enabled/${domain}"
}

configure_domain() {
    local domain="$1"
    local app_dir="$2"
    local public_ip="$3"

    validate_domain "$domain" || fail "Invalid domain: $domain"
    [ -d "$app_dir" ] || fail "Application directory does not exist: $app_dir"

    verify_domain_points_here "$domain" "$public_ip"
    write_nginx_config "$domain" "$app_dir"
    nginx -t || fail "Nginx configuration test failed for $domain"
    restart_service nginx || fail "Failed to reload nginx for $domain"

    if [ -n "${SKIP_SSL:-}" ]; then
        info "Skipping SSL setup for $domain because SKIP_SSL is set"
    else
        certbot --nginx -d "$domain" --non-interactive --agree-tos --register-unsafely-without-email --redirect \
            || fail "Failed to issue HTTPS certificate for $domain"
    fi

    success "Domain configured: $domain"
}

run_full_install() {
    local domain="${YELLOWTDS_DOMAIN:-}"
    local app_dir
    local public_ip

    echo -e "${GREEN}${PRODUCT_NAME} VPS installer${NC}"

    if [ -z "$domain" ]; then
        read -r -p "Enter primary domain (e.g. tds.example.com): " domain < /dev/tty
    fi
    domain="$(normalize_domain "$domain")"
    validate_domain "$domain" || fail "Invalid domain: $domain"

    app_dir="${YELLOWTDS_APP_DIR:-/var/www/${domain}}"
    app_dir="$(readlink -m "$app_dir")"

    public_ip="$(detect_public_ip)"
    verify_domain_points_here "$domain" "$public_ip"

    install_dependencies
    install_maxmind_extension

    info "[5/5] Installing application to $app_dir..."
    copy_application "$app_dir"
    set_permissions "$app_dir"
    download_geo_databases "$app_dir"
    setup_currency_cron "$app_dir"
    set_permissions "$app_dir"

    configure_domain "$domain" "$app_dir" "$public_ip"

    success "Installation complete: https://${domain}"
    echo "Open https://${domain}/admin/ and configure settings.php/admin access before production traffic."
}

run_add_domain() {
    local domains="${YELLOWTDS_DOMAINS:-}"
    local app_dir="${YELLOWTDS_APP_DIR:-}"
    local public_ip
    local domain

    echo -e "${GREEN}${PRODUCT_NAME} add-domain mode${NC}"

    if [ -z "$app_dir" ]; then
        read -r -p "Enter existing YellowTDS app directory: " app_dir < /dev/tty
    fi
    app_dir="$(readlink -m "$app_dir")"
    [ -d "$app_dir" ] || fail "Application directory does not exist: $app_dir"

    if [ -z "$domains" ]; then
        read -r -p "Enter domains separated by comma: " domains < /dev/tty
    fi

    parse_domain_list "$domains"
    public_ip="$(detect_public_ip)"

    for domain in "${PARSED_DOMAINS[@]}"; do
        configure_domain "$domain" "$app_dir" "$public_ip"
    done

    success "All domains configured"
}

case "$MODE" in
    install)
        run_full_install
        ;;
    add-domain)
        run_add_domain
        ;;
esac
