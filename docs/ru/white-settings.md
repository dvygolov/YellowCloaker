# White settings

## Назначение

White settings определяют, что делать с трафиком, который не должен попадать в black funnel.

Краткие пояснения к фильтрам, режимам Global/Domain-Specific и HTTP-кодам доступны по значкам `i`: наведите курсор или переведите на значок фокус с клавиатуры.

## Доступные действия

- local safe page from folder
- redirect
- load a website using CURL
- return HTTP code

![Раздел white settings](../assets/screenshots/white-settings-overview.png)

## Global vs domain-specific

Можно выбрать:

- одну общую white-конфигурацию
- отдельную white-конфигурацию для каждого домена

При переключении на **Domain-Specific** домены сразу появляются в боковом меню редактора — сохранять настройки или перезагружать страницу для этого не нужно. Добавление и удаление доменов также немедленно обновляет эти пункты меню.

## Load modes

Для folder и некоторых других вариантов используются режимы загрузки:

- base
- rewrite
- direct
