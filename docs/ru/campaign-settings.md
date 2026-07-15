# Настройки кампании

## Основные разделы

На странице настроек кампании есть следующие крупные блоки:

- Domains
- White
- Flows
- Scripts
- Postbacks

Название кампании отображается в верхней части бокового меню. Значок карандаша справа от него позволяет переименовать кампанию, не возвращаясь на dashboard.

## Domains

Здесь задаются домены, по которым runtime будет находить кампанию.

![Общий экран настроек кампании](../assets/screenshots/campaign-settings-overview.png)

## White

Этот блок определяет, что отдать нежелательному трафику:

- folder
- redirect
- curl
- error

Также здесь могут быть domain-specific white settings.

## Flows

Это основная часть black-логики. В flow задаются:

- имя flow
- filters
- steps
- distribution
- optimize_for
- optimize_mode

![Раздел Flows в настройках кампании](../assets/screenshots/campaign-settings-flows.png)

## Scripts

Дополнительные механики:

- backfix
- replace transit or landing
- images lazy load
- redirect rules
- event tracking thresholds

## Postbacks

Здесь находятся:

- inbound status mapping
- outgoing S2S postbacks
