# Black settings и flows

## Что здесь настраивается

Black branch — это логика для разрешённого трафика.

Основные элементы:

- JS Connect action
- JS bot detection
- flows

## JS bot detection

Поддерживаются:

- events
- timeout
- timezone checks

Это дополнительный этап проверки уже на стороне браузера.

## Flows

Flow — это отдельный маршрут трафика в black branch.

Flow включает:

- name
- filters
- steps
- distribution
- optimization settings

Порядок flows задаёт порядок проверки трафика. Чтобы изменить его, перетащите flow за ручку слева от названия. Когда ручка в фокусе, порядок также можно менять клавишами `↑` и `↓`.

При включённом [подсчёте уникальности](uniqueness.md) в filters доступно условие **Uniqueness** с областями Campaign и Flow. В Safe Page этого фильтра нет.

## Steps

В step можно задать:

- folders
- redirect URLs
- weights
- load type

Порядок steps меняется той же ручкой слева от номера шага. Redirect является завершающим действием, поэтому такой step закреплён последним и не перетаскивается.
