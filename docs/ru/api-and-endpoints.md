# API и endpoints

## Основные runtime endpoints

- `index.php`
- `js/index.php`
- `api/phpconnect.php`
- `api/postback.php`
- `api/conversion.php`
- `send.php`
- `next.php`
- `api/updateparams.php`

## Endpoints конверсий

`api/postback.php` принимает `clickid`, `status`, необязательные `payout`, `currency`, настроенный в кампании параметр Transaction ID и campaign `pbkey`. По умолчанию Transaction ID приходит в `tid`; для разных партнёрских программ можно разрешить несколько имён, но в одном запросе непустым должно быть только одно. Query и form fields читаются явно: cookies не используются, а один field одновременно в GET и POST отклоняется. Обычно endpoint возвращает структурированный JSON; при включённой pbkey-защите ошибка маскируется как `404 Not Found`. Подробнее: [Конверсии и Postbacks](postbacks.md).

`api/conversion.php` — same-origin POST endpoint для инжектируемой функции `ytdsConversion(status)`. В кампании должен быть включён Website status tracking. Endpoint принимает только текущий `clickid` и внутреннее имя статуса или alias; payout через браузер не передаётся.

Все источники конверсий атомарно пишут одну историю `conversions` и обновляют snapshot клика.

## Интеграция кампании

В разделе **Integration** редактора кампании собраны оба способа внешнего запуска:

- PHP Connect: адрес endpoint и API-ключ кампании для комплектного `phpclient.php`.
- JavaScript Connect: готовый тег со скриптом `js/index.php` и выбор способа открытия результата.

JavaScript-действие хранится на уровне кампании и поддерживает замену содержимого, iframe и redirect.

## Admin endpoints

- `admin/login.php`
- `admin/campeditor.php`
- `admin/clmnseditor.php`
- `admin/clicksdata.php`
- `admin/fileeditor.php`
- `admin/listfolders.php`
- `admin/zipupload.php`
