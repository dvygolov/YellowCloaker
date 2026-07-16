# Как это работает

## Общий request flow

Главный путь обработки запроса:

1. Запрос приходит в `index.php`.
2. Проект определяет кампанию по домену.
3. `core.php` собирает параметры клика и проверяет фильтры.
4. `tds.php` выбирает Safe Page, offer funnel или trafficback.
5. Выбранный action выполняется через `main.php`, `actions.php`, `htmlprocessing.php` и связанные части.

## Ветка Safe Page

Ветка Safe Page используется для нежелательного трафика. Возможные действия:

- local safe page from folder
- redirect
- curl-loaded page
- HTTP error code

Настройки Safe Page могут быть глобальными или domain-specific.

## Black branch

Black-ветка используется для разрешённого трафика. Она состоит из flows, а flow состоит из шагов funnel.

Для каждого шага можно настроить:

- folders
- redirect URLs
- weights
- load mode

## Distribution modes

Поддерживаются:

- equal
- weighted
- thompson

## Логирование и данные

Во время обработки система пишет:

- clicks
- blocked clicks
- trafficback clicks
- statuses and payouts
- custom events

Эти данные затем используются в статистике и click views.
