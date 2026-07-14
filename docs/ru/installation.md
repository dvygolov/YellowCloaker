# Установка на VPS

YellowTDS можно установить на чистый Debian/Ubuntu VPS через `install.sh`. Скрипт ставит nginx, PHP-FPM, HTTPS-сертификат, C-расширение MMDB для быстрого чтения геобаз и закрывает извне приватные файлы: SQLite БД, логи, временные файлы, настройки, MMDB базы и служебные файлы репозитория.

## Короткая команда

```bash
curl -fsSL https://raw.githubusercontent.com/dvygolov/YellowTDS/main/install.sh | sudo bash
```

Скрипт спросит основной домен. До выпуска сертификата он проверит, что DNS домена указывает на публичный IP этого VPS. Если домен ещё не привязан, установка остановится с сообщением, какой IP ожидался и какие IP сейчас резолвятся.

## Что делает автоустановщик

- ставит nginx, PHP 8.4 FPM/CLI, SQLite, curl, mbstring, zip/xml и certbot;
- ставит `libmaxminddb` и PECL-расширение `maxminddb`;
- включает `maxminddb` для PHP CLI и FPM и проверяет загрузку расширения;
- скачивает `country.mmdb` и `asn.mmdb` из `sapics/ip-location-db` GitHub Releases в `bases/`;
- настраивает права на `db/`, `logs/`, `ycclogs/`, `tmp/`, `caching/`, `bases/`;
- создаёт nginx-конфиг и выпускает HTTPS-сертификат через certbot.

## Добавление доменов

Чтобы добавить домены к уже установленному инстансу без создания новой БД:

```bash
curl -fsSL https://raw.githubusercontent.com/dvygolov/YellowTDS/main/install.sh | sudo bash -s -- --add-domain
```

Скрипт спросит путь к существующей установке YellowTDS и домены через запятую:

```text
tds1.example.com,tds2.example.com,track.example.net
```

Каждый домен будет проверен по DNS, получит отдельный nginx-конфиг и HTTPS-сертификат, но будет указывать на тот же каталог YellowTDS и ту же SQLite базу.

## Переменные окружения

Для автоматизации можно передать значения без интерактивного ввода:

```bash
curl -fsSL https://raw.githubusercontent.com/dvygolov/YellowTDS/main/install.sh \
  | sudo YELLOWTDS_DOMAIN=tds.example.com bash
```

Для batch-добавления доменов:

```bash
curl -fsSL https://raw.githubusercontent.com/dvygolov/YellowTDS/main/install.sh \
  | sudo YELLOWTDS_APP_DIR=/var/www/tds.example.com YELLOWTDS_DOMAINS=tds1.example.com,tds2.example.com bash -s -- --add-domain
```

Поддерживаются переменные:

- `YELLOWTDS_DOMAIN` — основной домен для полной установки;
- `YELLOWTDS_DOMAINS` — домены через запятую для `--add-domain`;
- `YELLOWTDS_APP_DIR` — каталог установки или существующего инстанса;
- `YELLOWTDS_REPO_ZIP` — URL ZIP-архива репозитория, если нужен нестандартный источник;
- `SKIP_SSL=1` — пропустить certbot в тестовом окружении.

## Защита приватных файлов

nginx-конфиг автоустановщика запрещает прямой доступ к:

- SQLite/data файлам: `.db`, `.sqlite`, `.sqlite3`, `.db-wal`, `.db-shm`;
- `settings.php`, `.env`, `.git`, SQL, log/cache/backup файлам;
- `db/`, `logs/`, `ycclogs/`, `tmp/`;
- `bases/*.mmdb`, `bases/*.phar`, `bases/*.txt`;
- `composer.json`, `composer.lock`, `phpunit.xml`, `agents.md`, `AGENTS.md`.

Публичные entrypoints, админские assets, JS assets, лендинги/вайты, thank-you assets и direct-load routing остаются доступными.

Установщик создаёт `settings.local.php` рядом с `settings.php`. Отдельное nginx-правило для него не добавляется: файл является PHP-скриптом, возвращает массив и не выводит содержимое при прямом запросе. Системные настройки далее меняются через кнопку **Settings** в админке.
