# Админ-панель

## Главные страницы

- `admin/index.php` — список кампаний и главная статистика
- `admin/campsettings.php` — настройки кампании
- `admin/statistics.php` — статистические таблицы кампании
- `admin/clicks.php` — просмотр кликов

## Dashboard

На главной странице доступны:

- список кампаний
- создание кампании
- trafficback URL
- экспорт таблицы
- настройка набора колонок

![Dashboard со списком кампаний](../assets/screenshots/admin-dashboard-campaigns.png)

Кнопка **Settings** открывает [системные настройки](system-settings.md), управление плагинами и обновлениями. Дата GeoBases в header носит информационный характер.

## Что хранится в UI

Админка управляет:

- columns layouts
- campaign settings JSON
- statistics tables
- filters and order by rules

## File management

Через админку можно:

- выбирать white folders и landing folders
- редактировать файлы
- загружать ZIP
