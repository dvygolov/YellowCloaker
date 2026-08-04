# Installing with Hosting Control Panels

The automatic `install.sh` is intended for a clean Debian/Ubuntu VPS. On a server with a hosting control panel, it stops before installing packages or changing configuration: the panel must remain in control of the website, PHP-FPM, virtual host, and SSL.

YellowTDS works with hosting panels, but copying the files alone is not enough. It also needs the PHP modules, GEO databases, correct file ownership, a front controller, and direct-access protection for private data.

## Tested Matrix

Each practical test was run on July 15, 2026, on a separate clean DigitalOcean Droplet with Ubuntu 22.04.

The practical matrix uses five widely used panels that can legally be deployed without buying a commercial license. Plesk, cPanel/WHM, DirectAdmin, and ISPmanager are documented below from their official documentation but are not marked as tested.

| Panel | Tested stack | Result |
|---|---|---|
| FastPanel | FastPanel 1.11, nginx + Apache, PHP 8.4 | YellowTDS, admin, SQLite, and GEO work |
| HestiaCP | HestiaCP 1.9.6, nginx + PHP-FPM 8.3 | YellowTDS and HTTPS work; the template survived a domain rebuild |
| aaPanel | aaPanel 8.0.4, nginx 1.26, PHP 8.4 | YellowTDS, admin login, SQLite, and GEO work |
| CloudPanel | CloudPanel 2.5.4, nginx, PHP-FPM 8.4 | YellowTDS works; a reusable vhost template was tested |
| CyberPanel | CyberPanel 2.4.8, OpenLiteSpeed 1.9, PHP 8.3 | YellowTDS, admin, SQLite, and GEO work |

In every completed test, the local `install.sh` correctly detected the panel, exited with status `1`, and did not change packages, websites, or web-server configuration.

## Common Preparation

1. Create a separate website and, where possible, a separate system user in the panel.
2. Select PHP 8.2 or newer; PHP 8.4 is recommended.
3. Enable `curl`, `mbstring`, `pdo_sqlite`, `sqlite3`, `xml`, and `zip`. The `maxminddb` extension is recommended but optional because `bases/geoip2.phar` is a working fallback.
4. Download the `multipleconfigs` branch and copy the contents of its `code/` directory into the website document root:

   ```text
   https://github.com/dvygolov/YellowTDS/archive/refs/heads/multipleconfigs.zip
   ```

5. Download from [sapics/ip-location-db](https://github.com/sapics/ip-location-db/releases/latest):
   - `geolite2-country.mmdb` as `bases/country.mmdb`;
   - `origin-asn.mmdb` as `bases/asn.mmdb`.
6. Make the website PHP-FPM user the owner of the files. Do not use `chmod 777`.
7. Verify that PHP can write to the document root and to `db/`, `logs/`, `ycclogs/`, `tmp/`, `caching/`, and `bases/`. Document-root write access is needed for `settings.local.php` and the built-in updater. Code files can remain `0644`, normal directories `0755`, and runtime directories `0775` when ownership is correct.
8. Add rewrite and private-file rules through the panel's persistent mechanism, not by editing a generated vhost file.
9. Issue the certificate through the panel.

## Required Web-Server Rules

For nginx, missing files must reach YellowTDS:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

Place these deny rules before the general `location /` and before static-file locations:

```nginx
location ~ /\.(?!well-known) {
    deny all;
}

location ~* ^/(?:settings(?:\.local)?\.php|composer\.(?:json|lock)|phpunit\.xml|agents\.md|AGENTS\.md)$ {
    deny all;
}

location ~* \.(?:db|sqlite|sqlite3|db-wal|db-shm|sql|env|log|cache|bak|old|orig|swp|md)$ {
    deny all;
}

location ~* ^/(?:db|logs|ycclogs|tmp)(?:/|$) {
    deny all;
}

location ~* ^/bases/.*\.(?:mmdb|phar|txt)$ {
    deny all;
}
```

If the panel uses an Apache backend, add this `.htaccess` file:

```apache
Options -Indexes
RewriteEngine On

RewriteRule ^(?:settings(?:\.local)?\.php|composer\.(?:json|lock)|phpunit\.xml|agents\.md|AGENTS\.md)$ - [F,L,NC]
RewriteRule ^(?:db|logs|ycclogs|tmp)(?:/|$) - [F,L,NC]
RewriteRule ^bases/.*\.(?:mmdb|phar|txt)$ - [F,L,NC]
RewriteRule \.(?:db|sqlite|sqlite3|db-wal|db-shm|sql|env|log|cache|bak|old|orig|swp|md)$ - [F,L,NC]

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

Do not create a second `location /` if the panel template already has one; add `try_files` to the existing block.

## FastPanel — Tested

Create the website in FastPanel or with `mogwai`, selecting FCGI and PHP 8.4:

```bash
mogwai sites create \
  --server-name=tds.example.com \
  --owner=yellowtds \
  --create-user \
  --ip=SERVER_IP \
  --handler=fcgi \
  --handler_version=8.4
```

Document root:

```text
/var/www/<user>/data/www/<domain>
```

FastPanel uses its own directories instead of `/etc/nginx/sites-enabled`:

```text
/etc/nginx/fastpanel2-sites/<user>/<domain>.conf
/etc/apache2/fastpanel2-sites/<user>/<domain>.conf
```

Put the front controller in `.htaccess`. FastPanel nginx can serve `.txt` and other static files before Apache, so the nginx deny fragment is also required. Store it in the persistent include:

```text
/etc/nginx/fastpanel2-sites/<user>/<domain>.includes
```

The include survived `mogwai sites update-backend` in the test. Issue SSL in the FastPanel HTTPS section or with `mogwai certificates create-le`.

Official references: [site CLI](https://kb.fastpanel.direct/cli/sites/), [PHP selection](https://kb.fastpanel.direct/sites/how-to-change-php-version/), [site settings](https://kb.fastpanel.direct/sites/site-settings/).

## HestiaCP — Tested

DigitalOcean images may already contain a system user named `admin`. In that case, install Hestia with another administrator name such as `hestiaadmin`; otherwise, installation fails with `Username or Group already exists`.

Create the domain with the panel CLI:

```bash
/usr/local/hestia/bin/v-add-web-domain <user> tds.example.com SERVER_IP yes none
```

Document root:

```text
/home/<user>/web/<domain>/public_html
```

The minimal Hestia installation needed extra packages for PHP 8.3:

```bash
apt-get install php8.3-sqlite3 php8.3-maxminddb
systemctl restart php8.3-fpm
```

Copy the standard PHP-FPM templates to separate `yellowtds.tpl` and `yellowtds.stpl` files under `/usr/local/hestia/data/templates/web/nginx/php-fpm/`, add `try_files`, and assign the template:

```bash
/usr/local/hestia/bin/v-change-web-domain-tpl <user> <domain> yellowtds yes
```

Store the deny locations in persistent domain includes named `nginx.conf_yellowtds` and `nginx.ssl.conf_yellowtds` under `/home/<user>/conf/web/<domain>/`. They survived `v-rebuild-web-domain` for both HTTP and HTTPS.

After issuing a certificate through the CLI, run `nginx -t` and reload nginx. During the test, the worker process continued serving the old panel certificate until that explicit reload.

Official references: [Hestia CLI](https://hestiacp.com/docs/reference/cli), [web templates](https://hestiacp.com/docs/server-administration/web-templates).

## aaPanel — Tested

Create a **PHP Project** under Website and select nginx with PHP 8.4. Typical paths are:

```text
Document root: /www/wwwroot/<domain>
Vhost:         /www/server/panel/vhost/nginx/<domain>.conf
Rewrite:       /www/server/panel/vhost/rewrite/<domain>.conf
PHP:           /www/server/php/84
PHP-FPM:       /tmp/php-cgi-84.sock
```

Add the front controller and deny rules through the website's **URL Rewrite** editor. Do not edit the main vhost directly. The tested rewrite file contained the nginx fragment from this guide and retained the same SHA-256 after a normal Save and after selecting PHP 8.4 again; `nginx -t` passed, the admin stayed reachable, and the GEO database stayed blocked. Deleting the site or applying another rewrite template can replace this file, so keep a copy of the rules.

aaPanel PHP-FPM normally runs as `www:www`. Give it ownership of the application files but exclude `.user.ini`: aaPanel marks that file immutable, so a blind recursive `chown` fails. Keep the panel-generated `404.html` and `502.html`; without them, an internal error may reach the front controller and expose a PHP warning.

The tested site passed admin login, created `db/clicks.db`, returned `ok` from `PRAGMA integrity_check`, and resolved GEO through the PHAR fallback.

Official references: [installation](https://www.aapanel.com/new/download.html), [Website API](https://www.aapanel.com/docs/api/site.html), [PHP Project](https://www.aapanel.com/docs/Function/php.html).

## CloudPanel — Tested

Create a Generic PHP site in the UI or CLI:

```bash
clpctl site:add:php \
  --domainName=tds.example.com \
  --phpVersion=8.4 \
  --vhostTemplate='Generic' \
  --siteUser=yellowtds \
  --siteUserPassword='STRONG_UNIQUE_PASSWORD'
```

Document root:

```text
/home/<site-user>/htdocs/<domain>
```

The CloudPanel Generic template already contains `try_files $uri $uri/ /index.php?$args` in its internal port-8080 server. Use the **Vhost Editor** to add the deny blocks to both server blocks before the general/static locations.

For repeat installations, import a separate custom template with `clpctl vhost-template:add`. The tested template needed a real frontend proxy directive:

```nginx
proxy_pass http://127.0.0.1:8080;
```

Do not leave the system-only `{{varnish_proxy_pass}}` placeholder in a manually imported template: without Varnish metadata it expands to an empty string and the site returns 404. After replacing it, the custom template routed `/friendly` to `index.php` and returned 403 for private files.

Issue the certificate in the UI or with:

```bash
clpctl lets-encrypt:install:certificate --domainName=tds.example.com
```

Official references: [generic PHP site](https://www.cloudpanel.io/docs/v2/php/applications/other/), [root CLI](https://www.cloudpanel.io/docs/v2/cloudpanel-cli/root-user-commands/).

## CyberPanel — Tested

Create the website with the supported CyberPanel CLI and PHP 8.3:

```bash
cyberpanel createWebsite \
  --package Default \
  --owner admin \
  --domainName tds.example.com \
  --email admin@example.com \
  --php 8.3 \
  --ssl 0 \
  --dkim 0 \
  --openBasedir 1
```

Tested paths:

```text
Document root: /home/<domain>/public_html
Vhost:         /usr/local/lsws/conf/vhosts/<domain>/vhost.conf
PHP handler:   /usr/local/lsws/lsphp83/bin/lsphp
```

The CyberPanel vhost enables `autoLoadHtaccess 1`. Use rewrite-only rules in `.htaccess`:

```apache
RewriteEngine On

RewriteRule ^\.well-known/acme-challenge/ - [L]
RewriteRule (^|/)\. - [F,L]
RewriteRule ^settings(?:\.local)?\.php$ - [F,L,NC]
RewriteRule \.(db|sqlite|sqlite3|db-wal|db-shm|sql|env|log|cache|bak|old|orig|swp|md|sh)$ - [F,L,NC]
RewriteRule ^(composer\.(json|lock)|phpunit\.xml|agents\.md|AGENTS\.md)$ - [F,L,NC]
RewriteRule ^(db|logs|ycclogs|tmp)(/|$) - [F,L,NC]
RewriteRule ^bases/.*\.(mmdb|phar|txt)$ - [F,L,NC]

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ /index.php [L,QSA]
```

The free OLS edition does not activate the commercial full-Apache-directives module, and its configuration check may print `License validation failed - module features disabled`. Do not rely on `Options`, `Deny`, or `<FilesMatch>` here. Built-in `mod_rewrite` rules, including `[F,L]`, work without a license and passed the HTTP test.

In the test, `/` and a missing route returned the normal `302` trafficback, the admin login returned `200`, and private files returned `403`. PHP 8.3.32 loaded every required extension and wrote to all runtime directories.

Switching the website from PHP 8.3 to 8.2 and back with `cyberpanel changePHP` preserved the `.htaccess` SHA and `autoLoadHtaccess 1`; every check still passed after restart. In CyberPanel 2.4.8, `cyberpanel version` may fail with `JSONDecodeError` because its `version.txt` contains two lines; this is a separate panel bug and does not affect the website.

Official references: [CyberPanel installation](https://cyberpanel.net/KnowledgeBase/home/install-cyberpanel/), [CyberPanel CLI](https://community.cyberpanel.net/t/30683-cyberpanel-command-line-interface), [configuration paths](https://cyberpanel.net/KnowledgeBase/home/location-of-configuration-files/), [OpenLiteSpeed rewrite rules](https://docs.openlitespeed.org/config/rewriterules/).

## Other Panels — Analogous, Not Tested

The following panels were not installed during this test run. Apply the common checklist through each panel's supported persistent mechanism.

### Plesk

Create the domain under **Websites & Domains**, select PHP 8.x FPM, and upload the files to `httpdocs`. Add nginx rules through **Apache & nginx Settings → Additional nginx directives**, which persists as `vhost_nginx.conf`; do not edit generated `last_nginx.conf`. Issue SSL through SSL It!.

Official references: [hosting settings](https://docs.plesk.com/en-US/obsidian/quick-start-guide/plesk-functionality-explained/managing-web-hosting.74401/), [additional nginx directives](https://docs.plesk.com/en-US/obsidian/administrator-guide/web-servers/apache-and-nginx-web-servers-linux/adjusting-nginx-settings-for-virtual-hosts.71997/), [SSL It!](https://docs.plesk.com/en-US/obsidian/administrator-guide/website-management/websites-and-domains/advanced-website-security/securing-connections-with-ssltls-certificates/securing-connections-with-the-ssl-it%21-extension.80001/).

### cPanel/WHM

Create the domain in **cPanel → Domains**, choose PHP through **MultiPHP Manager**, and install extensions through EasyApache 4. With Apache, `.htaccess` is suitable. For root-level vhost rules, use userdata includes under `/etc/apache2/conf.d/userdata/` and run the supported rebuild; do not edit `httpd.conf`.

Official references: [Domains](https://docs.cpanel.net/cpanel/domains/domains/create-a-new-domain/), [MultiPHP](https://docs.cpanel.net/cpanel/software/multiphp-manager-for-cpanel/), [vhost includes](https://docs.cpanel.net/ea4/apache/modify-apache-virtual-hosts-with-include-files/), [AutoSSL](https://docs.cpanel.net/whm/ssl-tls/manage-autossl/).

### DirectAdmin

Create the domain under **Domain Setup**; the default document root is `/home/<user>/domains/<domain>/public_html`. Select PHP through PHP Version Selector. Keep nginx/Apache rules in **Custom HTTPD Configurations** or `CUSTOM` tokens so that they survive `da build rewrite_confs`.

Official references: [PHP versions](https://docs.directadmin.com/webservices/php/multiple-php.html), [custom nginx](https://docs.directadmin.com/webservices/nginx/customizing-nginx.html), [SSL/ACME](https://docs.directadmin.com/webservices/ssl/ssl-and-letsencrypt-for-domains.html).

### ISPmanager

Create the website under **Sites**, selecting the directory correctly up front and using a separate PHP-FPM 8.x user. Store the persistent nginx include through the template engine under `/etc/nginx/vhosts-resources/WEBSITE_NAME/*.conf`; generated vhost files may be overwritten. Choose **New free from Let's Encrypt** for SSL.

Official references: [creating a website](https://www.ispmanager.com/docs/ispmanager/adding-and-configuring-a-website), [PHP version](https://www.ispmanager.com/docs/ispmanager/how-to-set-and-change-a-php-version), [template engine](https://www.ispmanager.com/docs/ispmanager/template-engine-for-configuration-files).

### VestaCP

VestaCP is a legacy option. Install YellowTDS only when the administrator has already provided a modern PHP 8.2+ FPM/backend template. Create the site in Web or with `v-add-web-domain`; the document root is `/home/<user>/web/<domain>/public_html`. Create separate `.tpl/.stpl` files, assign them through the supported CLI, and verify that HTTP and HTTPS use the same document root.

Official references: [CLI](https://vestacp.com/docs/cli), [templates](https://vestacp.com/docs/template-description).

## Final Verification

After installation, verify that:

- `/` and a nonexistent friendly URL reach `index.php`;
- the randomized admin path shows the login page;
- `db/clicks.db` is created and passes `PRAGMA integrity_check`;
- the PHP user can write to all runtime directories;
- `settings.php`, `settings.local.php`, `db/db.sql`, `bases/country.mmdb`, `bases/geoip2.phar`, logs, `.gitignore`, and `README.md` return 403 or 404;
- a supported panel rebuild does not remove rewrite or deny rules;
- the certificate is valid and HTTP redirects to HTTPS.

If a panel is not listed, do not run the automatic VPS installer over it. Create a normal PHP website and apply the common checklist through the panel's supported custom template or include mechanism.
