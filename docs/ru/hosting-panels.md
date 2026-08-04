# Установка через панели управления

Автоматический `install.sh` предназначен для чистого Debian/Ubuntu VPS. На сервере с панелью он останавливается до установки пакетов и изменения конфигурации: панель должна сама управлять сайтом, PHP-FPM, виртуальным хостом и SSL.

YellowTDS работает с панелями, но простого копирования файлов недостаточно. Нужны PHP-модули, GEO-базы, корректный владелец файлов, front controller и запрет прямого доступа к приватным данным.

## Что проверено

Практический тест выполнен 15 июля 2026 года на отдельных чистых DigitalOcean Droplet с Ubuntu 22.04.

Для реального автоматизированного прогона выбраны пять распространённых панелей, которые можно легально развернуть без покупки коммерческой лицензии. Plesk, cPanel/WHM, DirectAdmin и ISPmanager описаны ниже по официальной документации, но не помечены как протестированные.

| Панель | Проверенный стек | Результат |
|---|---|---|
| FastPanel | FastPanel 1.11, nginx + Apache, PHP 8.4 | YellowTDS, админка, SQLite и GEO работают |
| HestiaCP | HestiaCP 1.9.6, nginx + PHP-FPM 8.3 | YellowTDS и HTTPS работают; шаблон пережил пересборку домена |
| aaPanel | aaPanel 8.0.4, nginx 1.26, PHP 8.4 | YellowTDS, вход в админку, SQLite и GEO работают |
| CloudPanel | CloudPanel 2.5.4, nginx, PHP-FPM 8.4 | YellowTDS работает; проверен отдельный vhost template |
| CyberPanel | CyberPanel 2.4.8, OpenLiteSpeed 1.9, PHP 8.3 | YellowTDS, админка, SQLite и GEO работают |

Во всех завершённых тестах локальная версия `install.sh` правильно распознала панель, завершилась с кодом `1` и не изменила пакеты, сайты или конфигурацию веб-сервера.

## Общая подготовка

1. Создайте в панели отдельный сайт и, по возможности, отдельного системного пользователя.
2. Выберите PHP 8.2 или новее; рекомендуется PHP 8.4.
3. Включите `curl`, `mbstring`, `pdo_sqlite`, `sqlite3`, `xml` и `zip`. Расширение `maxminddb` желательно, но не обязательно: `bases/geoip2.phar` является рабочим fallback.
4. Скачайте ветку `multipleconfigs` и скопируйте содержимое её каталога `code/` в document root сайта:

   ```text
   https://github.com/dvygolov/YellowTDS/archive/refs/heads/multipleconfigs.zip
   ```

5. Скачайте из [sapics/ip-location-db](https://github.com/sapics/ip-location-db/releases/latest):
   - `geolite2-country.mmdb` как `bases/country.mmdb`;
   - `origin-asn.mmdb` как `bases/asn.mmdb`.
6. Сделайте владельцем файлов пользователя PHP-FPM сайта. Не используйте `chmod 777`.
7. Убедитесь, что PHP может писать в document root и в `db/`, `logs/`, `ycclogs/`, `tmp/`, `caching/`, `bases/`. Запись в сам document root нужна для `settings.local.php` и встроенного обновления. Код можно оставить с правами `0644`, каталоги — `0755`, runtime-каталоги — `0775`, если владелец выбран правильно.
8. Добавьте rewrite и защитные правила постоянным механизмом панели, а не правкой генерируемого vhost-файла.
9. Выпустите сертификат средствами панели.

## Обязательные правила веб-сервера

Для nginx отсутствующие файлы должны попадать в YellowTDS:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

До общего `location /` и до правил статических файлов должны находиться запреты:

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

Если панель использует Apache backend, добавьте в `.htaccess`:

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

Не создавайте второй `location /`, если шаблон панели уже содержит его: добавьте `try_files` в существующий блок.

## FastPanel — протестировано

Создайте сайт через UI FastPanel либо CLI `mogwai`, выбрав FCGI и PHP 8.4:

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

FastPanel использует собственные каталоги, а не `/etc/nginx/sites-enabled`:

```text
/etc/nginx/fastpanel2-sites/<user>/<domain>.conf
/etc/apache2/fastpanel2-sites/<user>/<domain>.conf
```

Front controller добавьте в `.htaccess`. Nginx FastPanel может отдать `.txt` и другие статические файлы до Apache, поэтому защитный nginx-фрагмент обязателен. Поместите его в постоянный include:

```text
/etc/nginx/fastpanel2-sites/<user>/<domain>.includes
```

В тесте include сохранился после `mogwai sites update-backend`. Сертификат выпускайте через раздел HTTPS FastPanel или `mogwai certificates create-le`.

Официальная справка: [создание сайтов](https://kb.fastpanel.direct/cli/sites/), [выбор PHP](https://kb.fastpanel.direct/sites/how-to-change-php-version/), [настройки сайта](https://kb.fastpanel.direct/sites/site-settings/).

## HestiaCP — протестировано

На DigitalOcean уже может существовать системный пользователь `admin`. В таком случае установите Hestia с другим именем администратора, например `hestiaadmin`, иначе установщик остановится с `Username or Group already exists`.

Создайте домен штатной командой:

```bash
/usr/local/hestia/bin/v-add-web-domain <user> tds.example.com SERVER_IP yes none
```

Document root:

```text
/home/<user>/web/<domain>/public_html
```

В минимальной установке Hestia для PHP 8.3 потребовались дополнительные пакеты:

```bash
apt-get install php8.3-sqlite3 php8.3-maxminddb
systemctl restart php8.3-fpm
```

Скопируйте штатные PHP-FPM templates в отдельные `yellowtds.tpl` и `yellowtds.stpl` под `/usr/local/hestia/data/templates/web/nginx/php-fpm/`, добавьте `try_files` и назначьте шаблон:

```bash
/usr/local/hestia/bin/v-change-web-domain-tpl <user> <domain> yellowtds yes
```

Защитные `location`-блоки держите в постоянных включениях домена `nginx.conf_yellowtds` и `nginx.ssl.conf_yellowtds` под `/home/<user>/conf/web/<domain>/`. Они сохранились после `v-rebuild-web-domain` для HTTP и HTTPS.

После выдачи сертификата из CLI проверьте `nginx -t` и перезагрузите nginx: в тесте без явного reload процесс продолжал отдавать старый сертификат панели.

Официальная справка: [Hestia CLI](https://hestiacp.com/docs/reference/cli), [web templates](https://hestiacp.com/docs/server-administration/web-templates).

## aaPanel — протестировано

Создайте **PHP Project** в разделе Website, выберите nginx и PHP 8.4. Типовые пути:

```text
Document root: /www/wwwroot/<domain>
Vhost:         /www/server/panel/vhost/nginx/<domain>.conf
Rewrite:       /www/server/panel/vhost/rewrite/<domain>.conf
PHP:           /www/server/php/84
PHP-FPM:       /tmp/php-cgi-84.sock
```

Добавляйте front controller и защитные правила через **URL Rewrite** сайта. Не редактируйте основной vhost напрямую. Проверенный rewrite-файл содержал общий nginx-фрагмент из этой инструкции и сохранялся после штатного Save и повторного выбора PHP 8.4: его SHA-256 не изменился, `nginx -t` прошёл, админка осталась доступна, а GEO-база закрыта. Удаление сайта или применение другого rewrite template может заменить файл, поэтому храните копию правил.

PHP-FPM aaPanel обычно работает как `www:www`. Передавайте ему владение файлами, но исключайте `.user.ini`: aaPanel делает этот файл immutable, и слепой рекурсивный `chown` завершится ошибкой. Сохраняйте созданные панелью `404.html` и `502.html`, чтобы системная ошибка не попала во front controller и не показала PHP warning.

В тесте работали вход в админку, создание `db/clicks.db`, `PRAGMA integrity_check=ok` и GEO lookup через PHAR fallback.

Официальная справка: [установка](https://www.aapanel.com/new/download.html), [Website API](https://www.aapanel.com/docs/api/site.html), [PHP Project](https://www.aapanel.com/docs/Function/php.html).

## CloudPanel — протестировано

Создайте Generic PHP site в UI либо CLI:

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

Generic template CloudPanel уже содержит `try_files $uri $uri/ /index.php?$args` во внутреннем server на порту 8080. Добавьте защитные блоки через **Vhost Editor** в оба server-блока до общих/static locations.

Для повторных установок можно импортировать отдельный custom template через `clpctl vhost-template:add`. В проверенном template frontend должен содержать реальный:

```nginx
proxy_pass http://127.0.0.1:8080;
```

Не оставляйте в самостоятельно импортируемом template системный placeholder `{{varnish_proxy_pass}}`: у custom template без Varnish metadata он разворачивается в пустую строку и сайт возвращает 404. После замены custom template правильно направлял `/friendly` в `index.php`, а приватные файлы возвращали 403.

Сертификат выпускается через UI или:

```bash
clpctl lets-encrypt:install:certificate --domainName=tds.example.com
```

Официальная справка: [PHP site](https://www.cloudpanel.io/docs/v2/php/applications/other/), [root CLI](https://www.cloudpanel.io/docs/v2/cloudpanel-cli/root-user-commands/).

## CyberPanel — протестировано

Сайт создан штатной CLI CyberPanel с PHP 8.3:

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

Проверенные пути:

```text
Document root: /home/<domain>/public_html
Vhost:         /usr/local/lsws/conf/vhosts/<domain>/vhost.conf
PHP handler:   /usr/local/lsws/lsphp83/bin/lsphp
```

Vhost CyberPanel включает `autoLoadHtaccess 1`. Используйте только rewrite-правила в `.htaccess`:

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

В бесплатной OLS-редакции коммерческий модуль полных Apache directives не активируется и проверка конфигурации может показать `License validation failed - module features disabled`. Не используйте здесь `Options`, `Deny` или `<FilesMatch>`. Встроенные `mod_rewrite`-правила, включая `[F,L]`, работают без лицензии и прошли HTTP-тест.

В проверке `/` и отсутствующий URL вернули штатный `302` trafficback, admin login — `200`, приватные файлы — `403`. PHP 8.3.32 загрузил все обязательные расширения, запись во все runtime-каталоги прошла.

Переключение сайта PHP 8.3 → 8.2 → 8.3 через `cyberpanel changePHP` сохранило SHA `.htaccess` и `autoLoadHtaccess 1`; после рестарта все проверки снова прошли. В CyberPanel 2.4.8 команда `cyberpanel version` может завершиться `JSONDecodeError` из-за двухстрочного `version.txt`; это отдельный дефект панели и на сайт не влияет.

Официальная справка: [установка CyberPanel](https://cyberpanel.net/KnowledgeBase/home/install-cyberpanel/), [CyberPanel CLI](https://community.cyberpanel.net/t/30683-cyberpanel-command-line-interface), [пути конфигурации](https://cyberpanel.net/KnowledgeBase/home/location-of-configuration-files/), [OpenLiteSpeed rewrite rules](https://docs.openlitespeed.org/config/rewriterules/).

## Другие панели — по аналогии, не протестировано

Следующие варианты в этом прогоне не устанавливались. Используйте тот же общий чек-лист и только постоянные механизмы конкретной панели.

### Plesk

Создайте домен в **Websites & Domains**, выберите PHP 8.x FPM и загрузите файлы в `httpdocs`. Nginx-правила добавляйте через **Apache & nginx Settings → Additional nginx directives**, что соответствует постоянному `vhost_nginx.conf`; не правьте сгенерированный `last_nginx.conf`. SSL выпускайте через SSL It!.

Официальная справка: [hosting settings](https://docs.plesk.com/en-US/obsidian/quick-start-guide/plesk-functionality-explained/managing-web-hosting.74401/), [additional nginx directives](https://docs.plesk.com/en-US/obsidian/administrator-guide/web-servers/apache-and-nginx-web-servers-linux/adjusting-nginx-settings-for-virtual-hosts.71997/), [SSL It!](https://docs.plesk.com/en-US/obsidian/administrator-guide/website-management/websites-and-domains/advanced-website-security/securing-connections-with-ssltls-certificates/securing-connections-with-the-ssl-it%21-extension.80001/).

### cPanel/WHM

Создайте домен в **cPanel → Domains**, PHP назначьте через **MultiPHP Manager**, расширения — через EasyApache 4. При Apache можно использовать `.htaccess`. Для root-level vhost rules используйте userdata includes под `/etc/apache2/conf.d/userdata/`, затем штатную пересборку; не редактируйте `httpd.conf`.

Официальная справка: [Domains](https://docs.cpanel.net/cpanel/domains/domains/create-a-new-domain/), [MultiPHP](https://docs.cpanel.net/cpanel/software/multiphp-manager-for-cpanel/), [vhost includes](https://docs.cpanel.net/ea4/apache/modify-apache-virtual-hosts-with-include-files/), [AutoSSL](https://docs.cpanel.net/whm/ssl-tls/manage-autossl/).

### DirectAdmin

Создайте домен в **Domain Setup**, стандартный document root — `/home/<user>/domains/<domain>/public_html`. Версию PHP выберите через PHP Version Selector. Nginx/Apache правила храните в **Custom HTTPD Configurations** или `CUSTOM`-блоках, чтобы они пережили `da build rewrite_confs`.

Официальная справка: [PHP versions](https://docs.directadmin.com/webservices/php/multiple-php.html), [custom nginx](https://docs.directadmin.com/webservices/nginx/customizing-nginx.html), [SSL/ACME](https://docs.directadmin.com/webservices/ssl/ssl-and-letsencrypt-for-domains.html).

### ISPmanager

Создайте сайт в **Sites**, сразу правильно выберите каталог и отдельного пользователя с PHP-FPM 8.x. Постоянный nginx include размещайте через предусмотренный template engine в `/etc/nginx/vhosts-resources/WEBSITE_NAME/*.conf`; сгенерированный vhost панель может перезаписать. SSL выберите как **New free from Let’s Encrypt**.

Официальная справка: [создание сайта](https://www.ispmanager.com/docs/ispmanager/adding-and-configuring-a-website), [PHP version](https://www.ispmanager.com/docs/ispmanager/how-to-set-and-change-a-php-version), [template engine](https://www.ispmanager.com/docs/ispmanager/template-engine-for-configuration-files).

### VestaCP

VestaCP — legacy-вариант. Устанавливайте YellowTDS только если администратор уже добавил современный PHP 8.2+ FPM/backend template. Сайт создаётся через Web или `v-add-web-domain`, document root — `/home/<user>/web/<domain>/public_html`. Создайте отдельные `.tpl/.stpl`, назначьте их штатной CLI и убедитесь, что HTTP/HTTPS используют один document root.

Официальная справка: [CLI](https://vestacp.com/docs/cli), [templates](https://vestacp.com/docs/template-description).

## Финальная проверка

После установки проверьте:

- `/` и несуществующий friendly URL доходят до `index.php`;
- случайный путь админки показывает страницу входа;
- `db/clicks.db` создаётся и проходит `PRAGMA integrity_check`;
- PHP-пользователь пишет во все runtime-каталоги;
- `settings.php`, `settings.local.php`, `db/db.sql`, `bases/country.mmdb`, `bases/geoip2.phar`, логи, `.gitignore` и `README.md` возвращают 403 или 404;
- после штатной пересборки сайта панелью rewrite и запреты не исчезают;
- сертификат действителен и HTTP перенаправляется на HTTPS.

Если панель не перечислена, не запускайте поверх неё автоматический VPS installer. Создайте обычный PHP-сайт и примените общий чек-лист через поддерживаемый панелью custom template/include.
