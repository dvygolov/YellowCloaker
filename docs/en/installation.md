# VPS Installation

YellowTDS can be installed on a clean Debian/Ubuntu VPS with `install.sh`. The script installs nginx, PHP-FPM, an HTTPS certificate, the MMDB C extension for faster geobase reads, and blocks external access to private runtime files such as SQLite databases, logs, temp files, settings, MMDB databases, and repository metadata.

We recommend [FriendHosting](https://yellowweb.top/friendhosting) for a VPS. For the automatic installer, choose a clean Debian or Ubuntu server without a hosting control panel.

## Short Command

```bash
curl -fsSL https://raw.githubusercontent.com/dvygolov/YellowTDS/multipleconfigs/code/install.sh | sudo bash
```

The script asks for the primary domain. Before issuing the certificate, it verifies that the domain DNS points to the VPS public IP. If the domain is not pointed yet, installation stops and shows the expected IP and currently resolved IPs.

The full installation path has been verified on clean Ubuntu 22.04 and 24.04 systems. An Ubuntu 22.04 `sites-enabled: No such file or directory` error usually means that a hosting panel has replaced the standard nginx layout; the current installer detects common panels and stops before making changes.

## What the Installer Does

- installs nginx, PHP 8.4 FPM/CLI, SQLite, curl, mbstring, zip/xml, APCu, and certbot;
- enables APCu for PHP-FPM; DeviceDetector uses it for regex-catalog caching and automatically keeps using the PHP file cache when APCu is unavailable;
- installs `libmaxminddb` and the PECL `maxminddb` extension;
- enables `maxminddb` for PHP CLI and FPM and verifies the extension is loaded;
- downloads `country.mmdb` and `asn.mmdb` from `sapics/ip-location-db` GitHub Releases into `bases/`;
- configures writable permissions for `db/`, `logs/`, `ycclogs/`, `tmp/`, `caching/`, and `bases/`;
- creates the nginx config and issues an HTTPS certificate with certbot.

## Hosting Control Panels

The automatic installer is intended for a clean VPS and must not be run on top of FastPanel, Plesk, cPanel/WHM, DirectAdmin, HestiaCP, VestaCP, aaPanel, ISPmanager, CyberPanel, or CloudPanel. These panels manage nginx/Apache virtual hosts, PHP-FPM, SSL certificates, and website directories themselves. When a supported panel is detected, `install.sh` stops before changing the server configuration.

YellowTDS works on a panel-managed server, but the website, PHP version, rewrite, permissions, private-file protection, and SSL must be configured through the panel itself. Follow [Installing with Hosting Control Panels](hosting-panels.md) for tested FastPanel, HestiaCP, aaPanel, CloudPanel, and CyberPanel procedures, plus analogous guidance for Plesk, cPanel/WHM, DirectAdmin, ISPmanager, and VestaCP.

## Adding Domains

To add domains to an existing instance without creating a new database:

```bash
curl -fsSL https://raw.githubusercontent.com/dvygolov/YellowTDS/multipleconfigs/code/install.sh | sudo bash -s -- --add-domain
```

The script asks for the existing YellowTDS installation directory and comma-separated domains:

```text
tds1.example.com,tds2.example.com,track.example.net
```

Each domain is checked through DNS, gets its own nginx config and HTTPS certificate, and points to the same YellowTDS directory and SQLite database.

## Environment Variables

For automation, pass values non-interactively:

```bash
curl -fsSL https://raw.githubusercontent.com/dvygolov/YellowTDS/multipleconfigs/code/install.sh \
  | sudo YELLOWTDS_DOMAIN=tds.example.com bash
```

For batch domain additions:

```bash
curl -fsSL https://raw.githubusercontent.com/dvygolov/YellowTDS/multipleconfigs/code/install.sh \
  | sudo YELLOWTDS_APP_DIR=/var/www/tds.example.com YELLOWTDS_DOMAINS=tds1.example.com,tds2.example.com bash -s -- --add-domain
```

Supported variables:

- `YELLOWTDS_DOMAIN` — primary domain for full install;
- `YELLOWTDS_DOMAINS` — comma-separated domains for `--add-domain`;
- `YELLOWTDS_APP_DIR` — install directory or existing instance directory;
- `YELLOWTDS_REPO_ZIP` — repository ZIP URL when a custom source is needed;
- `SKIP_SSL=1` — skip certbot in test environments.

## Private File Protection

The installer nginx config denies direct access to:

- SQLite/data files: `.db`, `.sqlite`, `.sqlite3`, `.db-wal`, `.db-shm`;
- `settings.php`, `.env`, `.git`, SQL, log/cache/backup files;
- `db/`, `logs/`, `ycclogs/`, `tmp/`;
- `bases/*.mmdb`, `bases/*.phar`, `bases/*.txt`;
- `composer.json`, `composer.lock`, `phpunit.xml`, `agents.md`, `AGENTS.md`.

Public entrypoints, admin assets, JS assets, landing and safe-page static assets, thank-you assets, and direct-load routing remain available.

The installer creates `settings.local.php` next to `settings.php`. No separate nginx deny rule is added for it: it is a PHP script that returns an array and emits no output when requested directly. Further system configuration is managed through **Settings** in the admin panel.
