# Статистика

## Что умеет модуль статистики

Статистика кампании позволяет:

- создавать несколько saved tables
- выбирать columns
- настраивать group by
- сохранять filters
- сохранять order by
- экспортировать таблицы в XLSX

## Saved tables

У каждой таблицы есть:

- name
- columns
- groupby
- filters
- orderby

![Пример таблицы статистики кампании](../assets/screenshots/statistics-table-overview.png)

![Модальное окно редактора таблицы статистики](../assets/screenshots/statistics-table-editor-modal.png)

## Custom metrics

Можно создавать формульные custom columns и использовать:

- base metrics
- event metrics
- derived metrics

## Timezone

Date grouping зависит от timezone, заданного в statistics settings кампании.

## Уникальные клики

**Uniques** считает Campaign unique, а **Flow uniques** — уникальные входы по flows и доступен без Group by Flow. **U/C**, **EPuC** и **CPuC** используют Campaign uniques. Старые строки не пересчитываются; для полностью старых и смешанных групп отображается `—`. Подробнее: [Подсчёт уникальности](uniqueness.md).
