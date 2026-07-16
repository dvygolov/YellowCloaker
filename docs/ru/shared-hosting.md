# Установка на shared hosting

YellowTDS можно установить на обычный виртуальный (shared) хостинг, если он поддерживает PHP 8.2 или новее, SQLite и правила Apache `.htaccess`. Root-доступ не нужен. На shared hosting нельзя запускать `install.sh`: файлы загружаются через файловый менеджер, FTP или SFTP, а PHP и HTTPS настраиваются в панели хостинга.

Если нужен VPS, рекомендуем [FriendHosting](https://yellowweb.top/friendhosting) и [автоматическую установку на чистый Debian/Ubuntu](installation.md).

## Требования к хостингу

- PHP 8.2 или новее; рекомендуется PHP 8.4;
- расширения `curl`, `gd`, `mbstring`, `pdo_sqlite`, `sqlite3`, `xml` и `zip`;
- возможность создавать и изменять файлы из PHP;
- Apache с `mod_rewrite` и поддержкой `.htaccess` либо эквивалентные правила от поддержки хостинга;
- HTTPS для выбранного домена или поддомена.

Расширение `maxminddb` желательно, но не обязательно: YellowTDS может использовать `bases/geoip2.phar`. Тариф, где запрещены SQLite, rewrite или запись из PHP, не подходит.

## Какие файлы загружать

Самый надёжный вариант — скачать ZIP ветки `multipleconfigs`, распаковать его локально и загрузить содержимое каталога YellowTDS в каталог сайта:

```text
https://github.com/dvygolov/YellowTDS/archive/refs/heads/multipleconfigs.zip
```

Обязательно сохраните PHP-файлы в корне, а также каталоги `admin/`, `api/`, `bases/`, `caching/`, `cron/`, `db/`, `js/`, `plugins/`, `scripts/`, `thankyou/`, `tmp/`, `logs/` и `ycclogs/`. Runtime-каталоги должны существовать, даже если при загрузке они пустые.

Для рабочей установки не нужны:

- `.git/`, `.github/`, `.vscode/`, `.playwright-cli/` и `.phpunit.cache/`;
- `docs/` и `tests/`;
- README-файлы, `agents.md`, `phpunit.xml` и `install.sh`;
- файлы IDE `YellowTDS.sln`, `YellowTDS.phpproj`, `YellowTDS.phpproj.user`;
- локальные `db/*.db`, `db/*.db-wal`, `db/*.db-shm`, логи и содержимое runtime-кэшей.

Если сомневаетесь, загрузите весь архив: это безопаснее, чем удалить рабочую зависимость. Служебные файлы затем закройте правилами ниже.

## Корень, папка или подпапка

YellowTDS не обязан находиться в корне домена. Поддерживаются, например:

```text
https://tds.example.com/
https://example.com/tds/
https://example.com/tools/tds/
```

Загрузите приложение целиком в выбранный каталог, не меняя внутреннюю структуру. В `.htaccess` используется относительный переход в `index.php`, поэтому один файл подходит для корня и подкаталогов. Не добавляйте начальный `/` перед `index.php`.

Если в родительском каталоге уже есть WordPress или другое приложение со своим `.htaccess`, положите отдельный `.htaccess` в каталог YellowTDS. Убедитесь, что правила родительского сайта не перехватывают запросы раньше него.

## Настройка `.htaccess`

Создайте в корне установки YellowTDS файл `.htaccess`:

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

Если строка `Options -Indexes -MultiViews` вызывает ошибку 500, удалите только её и попросите поддержку отключить листинг каталогов и `MultiViews` для каталога YellowTDS.

## Права и первый запуск

1. В панели выберите PHP 8.2+ и включите обязательные расширения.
2. Выдайте PHP право записи в корень установки и `db/`, `logs/`, `ycclogs/`, `tmp/`, `caching/`, `bases/`. Обычно достаточно `0755` для каталогов и `0644` для файлов, если PHP работает от владельца аккаунта; не используйте `0777` без требования поддержки.
3. Скачайте `geolite2-country.mmdb` как `bases/country.mmdb` и `origin-asn.mmdb` как `bases/asn.mmdb` из [sapics/ip-location-db](https://github.com/sapics/ip-location-db/releases/latest).
4. Включите HTTPS и откройте URL установки. YellowTDS создаст runtime-файлы, включая SQLite-базу и `settings.local.php`.
5. Откройте путь админки и завершите настройку.

## Проверка

- главная страница и несуществующий URL обрабатываются YellowTDS, а не стандартной страницей 404 хостинга;
- админка открывается внутри того же подкаталога;
- база создана, PHP пишет во все runtime-каталоги;
- `settings.php`, `settings.local.php`, `db/db.sql`, MMDB-базы, логи, `.gitignore` и README возвращают 403 или 404;
- ссылки, JS Connect, API и переходы между шагами сохраняют префикс подкаталога.

Если friendly URL возвращает 404 хостинга, попросите поддержку включить `mod_rewrite` и `AllowOverride`. При ошибке 500 сначала уберите строку `Options`, затем проверьте журналы ошибок.
