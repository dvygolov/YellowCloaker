# Shared Hosting Installation

YellowTDS can run on shared hosting when the plan provides PHP 8.2 or newer, SQLite, Apache `.htaccess` rules, and permission for PHP to create files. Root access is not required. Do not run `install.sh`: upload the files through the hosting file manager, FTP, or SFTP.

For a VPS, we recommend [FriendHosting](https://yellowweb.top/friendhosting) and the [automatic installation on clean Debian or Ubuntu](installation.md).

## Requirements

- PHP 8.2 or newer; PHP 8.4 is recommended;
- `curl`, `gd`, `mbstring`, `pdo_sqlite`, `sqlite3`, `xml`, and `zip`;
- Apache with `mod_rewrite` and `.htaccess`, or equivalent rules from hosting support;
- HTTPS and PHP file-write access.

The `maxminddb` extension is optional because `bases/geoip2.phar` can be used as a fallback.

## Files to upload

Download and extract the `multipleconfigs` branch:

```text
https://github.com/dvygolov/YellowTDS/archive/refs/heads/multipleconfigs.zip
```

The safest choice is the complete application. Keep every root PHP file and `admin/`, `api/`, `bases/`, `caching/`, `cron/`, `db/`, `js/`, `plugins/`, `scripts/`, `thankyou/`, `tmp/`, `logs/`, and `ycclogs/`. Empty runtime directories must still exist.

The live site does not need Git, editor, and PHPUnit metadata; `docs/`, `tests/`, README and agent files; `install.sh`; IDE project files; local SQLite, log, or runtime-cache contents. When unsure, upload the whole archive and protect service files with the rules below.

## Root or nested directory

All of these layouts are supported:

```text
https://tds.example.com/
https://example.com/tds/
https://example.com/tools/tds/
```

Keep the internal layout unchanged. The relative `RewriteRule ^ index.php` below works at the domain root and in nested directories; do not add a leading slash. If the parent contains WordPress or another application, put a separate `.htaccess` inside the YellowTDS directory.

## `.htaccess`

```apache
Options -Indexes -MultiViews
RewriteEngine On

RewriteRule (^|/)\. - [F,L]
RewriteRule ^(?:settings(?:\.local)?\.php|composer\.(?:json|lock)|phpunit\.xml|agents\.md|AGENTS\.md)$ - [F,L,NC]
RewriteRule ^(?:db|logs|ycclogs|tmp)(?:/|$) - [F,L,NC]
RewriteRule ^bases/.*\.(?:mmdb|phar|txt)$ - [F,L,NC]
RewriteRule \.(?:db|sqlite|sqlite3|db-wal|db-shm|sql|env|log|cache|bak|old|orig|swp|md)$ - [F,L,NC]

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

If the `Options` line causes HTTP 500, remove only that line and ask support to disable directory listing and `MultiViews`.

## Finish and verify

Enable PHP 8.2+, the required extensions, and HTTPS. Give PHP write access to the installation root and `db/`, `logs/`, `ycclogs/`, `tmp/`, `caching/`, and `bases/`; normally directories `0755` and files `0644` are sufficient. Avoid `0777`.

Download `geolite2-country.mmdb` as `bases/country.mmdb` and `origin-asn.mmdb` as `bases/asn.mmdb` from [sapics/ip-location-db](https://github.com/sapics/ip-location-db/releases/latest). Open the installation URL and then the admin path.

Verify that friendly URLs reach YellowTDS, the admin retains the directory prefix, the database is created, runtime directories are writable, and settings, SQL, MMDB, log, Git, and README files return 403 or 404.
