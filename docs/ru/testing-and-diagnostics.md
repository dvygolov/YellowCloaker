# Тестирование и диагностика

## PHPUnit

Тесты находятся в корневом `tests/`. Из корня репозитория запускаются два набора:

```bash
vendor/bin/phpunit
vendor/bin/phpunit -c tests/application/phpunit.xml
```

## Что покрыто тестами

- flows
- statistics
- Thompson Sampling
- MVT, persistence и rendering
- installer, updater, settings и plugins

## Диагностика

Полезные файлы:

- `tests/engine/check_db.php`
- `tests/tools/phptest.php`
- `tests/load/`

## Серверные журналы

Индикатор **Logs** на главной странице открывает просмотрщик серверных журналов YellowTDS. Доступны период до 31 дня, фильтры по уровню и подсистеме, полнотекстовый поиск, постраничная загрузка и скачивание выбранных записей в ZIP.

![Просмотрщик серверных журналов](../assets/screenshots/server-log-viewer.png)

Новые события записываются в `logs/YYYY-MM-DD.log` в формате JSON Lines. Каждая запись содержит время, уровень (`trace`, `info`, `warning`, `error`), источник и сообщение. `trace` записывается только при включённом Debug mode.

Срок хранения задаётся в **Settings → General → Log retention** и по умолчанию равен 30 дням. Автоочистка затрагивает только файлы нового формата. Старые каталоги `logs/<категория>/` не мигрируются и в просмотрщике не отображаются.

Журналы внешнего PHP Connect-клиента создаются на сайте, где установлен клиент, и не передаются в серверный просмотрщик.
