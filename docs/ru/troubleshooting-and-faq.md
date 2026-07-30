# Troubleshooting и FAQ

## Не получается войти в админку

Проверьте:

- `adminPassword`
- `adminDomain`
- список `adminIp` и наличие текущего IP среди адресов через запятую
- блокировку по rate limit

## Пустая статистика

Проверьте:

- date range
- timezone
- filters
- наличие кликов в кампании

## Не обновляются конверсии по postback

Проверьте:

- `clickid`
- `status`
- payout
- mapping статусов
