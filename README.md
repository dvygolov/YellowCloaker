```                
                            Binomo Cloaker  
    _            __     __  _ _             __          __  _     
   | |           \ \   / / | | |            \ \        / / | |    
   | |__  _   _   \ \_/ /__| | | _____      _\ \  /\  / /__| |__  
   | '_ \| | | |   \   / _ \ | |/ _ \ \ /\ / /\ \/  \/ / _ \ '_ \ 
   | |_) | |_| |    | |  __/ | | (_) \ V  V /  \  /\  /  __/ |_) |
   |_.__/ \__, |    |_|\___|_|_|\___/ \_/\_/    \/  \/ \___|_.__/ 
           __/ |                                                  
          |___/             https://yellowweb.top                 

If you like this script, PLEASE DONATE!  
WebMoney: Z182653170916  
Bitcoin: bc1qqv99jasckntqnk0pkjnrjtpwu0yurm0qd0gnqv  
Ethereum: 0xBC118D3FDE78eE393A154C29A4545c575506ad6B  
```

# Binomo Cloaker [Yellow Web](https://yellowweb.top) Edition
English version of this help is down below 👇 Warning: this help is outdated! Now all of the settings are made using the UI: */admin?password=12345*
**Use PHP version 7.2 or higher and create HTTPS certificates for all of your domains!**
# Поддержка
Если вы хотите, чтобы этот проект и дальше развивался, [**поддержите автора соткой-другой**!](https://t.me/yellowwebdonate_bot)

# Описание
Модифицированный скрипт клоакинга для арбитража трафика, изначально найденный на просторах [Black Hat World](http://blackhatworld.com).
# Справочные материалы
- [ПОСЛЕДНИЙ стрим на которой разобран ВЕСЬ графический интерфей кло](https://youtu.be/-ikmpq-L8ZE)
- [Стрим на котором подробно разобрана кло со всеми функциями](https://www.youtube.com/watch?v=XMua15r2dwg&feature=youtu.be)
- [Видео с обзором новых возможностей тут.](https://www.youtube.com/watch?v=x-Z2Y4lEOc0&t=656s)
- [Описание настройки первых версий тут!](https://yellowweb.top/%d0%ba%d0%bb%d0%be%d0%b0%d0%ba%d0%b8%d0%bd%d0%b3-%d0%b4%d0%bb%d1%8f-%d0%b1%d0%b5%d0%b4%d0%bd%d0%be%d0%b3%d0%be-%d0%bd%d0%be-%d1%83%d0%bc%d0%bd%d0%be%d0%b3%d0%be-%d0%b0%d1%80%d0%b1%d0%b8%d1%82%d1%80/)

# Установка
Скачайте последнюю версию всех файлов из этого репозитория и загрузите их себе на хостинг. На хостинге должен быть включён **PHP версии 7.2 или выше** и вы должны **создать HTTPS сертификат для вашего домена. Без HTTPS кло не будет корректно работать и вариант просто включить HTTPS на CloudFlare не катит! Да, если используете CloudFlare, то после того, как вам выпустили нормальный сертификат, включайте HTTPS в Full-режим!** Могу [порекомендовать вам хостинг Beget для кло](https://yellowweb.top/beget), он простой и удобный и там можно в пару кликов выпустить HTTPS-сертификат.

Если у вас есть локальные проклы и ленды, тогда создайте папку для каждого из них в корневой папке кло и скопируйте их файлы каждый в свою папку.
*Например:*
Если у вас 2 проклы и 2 ленда, создайте 2 папки для прокл: p1 и p2. И две папки для лендов: land1, land2.

# Настройка
Для настройки кло создан пользовательский интерфейс, доступный по адресу: https://ваш.домен/admin?password=12345 Не забудьте поменять пароль доступа!
## Настройка вайта
Вайт - это страница, которая показывается посетителю, который не прошёл через фильтры кло. Это нежелательные посетители.

Для начала вам надо определиться, какой тип вайта вы хотите использовать. Кло может: 
- показывать локальные вайты
- редиректить на любой другой сайт
- подгружать контент любого другого сайта через CURL
- возвращать любой HTTP-код (например, ошибку 404 или просто 200)

Когда вы определились, поменяйте значение на одно из следующих:

### Локальный вайт-пейдж из папки
Это для локальный вайтов. Вы должны создать папку в корне кло, например *white* и скопировать туда все файлы вайта. Затем пропишите название папки в соответствующем поле 
### Редирект
Это для редиректа всего вайт-трафика на другой сайт. Вводим адрес сайта и выбираем тип редиректа. Это может быть: 301,302,303 или 307. Загуглите разницу, если вам это важно.
### Curl
Это для подгрузки контента любого другого сайта. Пишем адрес сайта в соответствующем поле.
### Возврат HTTP-кода
Вы можете вернуть любую HTTP-ошибку для вайт-трафика. Например: *404*. Либо код *200* для показа пустой страницы.

## Индивидуальные вайты для разных доменов
Если у вас привязано к хостингу несколько доменов (или субдоменов) и вы льёте на них траф, вы можете сделать так, что для разных доменов будут показываться разные вайты, поменяв соответствующую настройку. 

Затем заполните поля. Формат такой:
`ваш.домен => whiteaction:value`
Например:
`https://mydomain.com => curl:https://ya.ru`
Все возможные значения whiteaction: *folder, curl, redirect, error*

## Настройка воронки
Кло умеет работать со следующими воронками:
- локальный ленд (или несколько лендов)
- локальная прокла (проклы) -> локальные ленды
- локальные проклы + редирект на ленд на другом сайте
- сразу же редирект на другой сайт

Разберём все эти конфигурации.
### Локальные ленды
Вы можете использовать один или несколько лендов. Траф будет разделён равномерно между ними. Скажем, для двух лендов это будет 50/50. Каждый ленд должен лежать в своей папке. Ставим **"Не использовать прелендинг"**, а метод загрузки ленидингов - **"Локальный лендинги из папки"**  Если лендов несколько, то используем запятую, как разделитель. Например:
`land1,land2`

### Локальные проклы - Локальные ленды
Проделайте всё то же самое, что в пункте про **Локальные ленды** но также заполните поле **"Папки, где лежат преленды"**. Например, для двух прокл:
`p1,p2`
### Локальные проклы + redirect
Заполняем названия папок прокл. Например, для двух прокл:
`p1,p2`
Затем заменяем **"Метод загрузки лендингов"** на *Редирект*. Последний шаг: заполните адрес редиректа.
### Сразу редирект
Если вы просто хотите редиректить весь проходящий по фильтрам кло траф,то тогда используйте **$black_action = *'redirect'*** и заполните адрес редиректа **$black_redirect_url**. Также выберите тип редиректа: 301,302,303 or 307. Загуглите разницу, если вам это важно. Введите тип редиректа в **$black_redirect_type**.
### Настройка скрипта конверсий локального ленда
У каждого ленда есть возможность отправлять лиды в ПП (кэп!). И у каждой ПП своя механика отправки этих самых лидов.
По умолчанию кло ищет файл *order.php*, находящийся в папке ленда. Если у вашей ПП скрипт называется по-другому, что переименуйте значение в переменной **$black_land_conversion_script**. Чтобы понять, как называется скрипт отправки, откройте индексный файл ленда и поищите любую форму - *<form*. Гляньте у формы атрибут *action*. В нём и прописан скрипт. Если атрибута *action* нет, значит лид отправляет индексный файл!
Если скрипт находится в какой-то папке, то введите относительный путь к скрипту,например:
`$black_land_conversion_script='folder/conversion.php';`
После того, как вы это всё настроили, отправьте тестовый лид. Если лида нет в стате ПП, тогда откройте скрипт отправки лидов и поищите, нет ли в нём строки
`exit();`
Если есть, то удалите или закомментируйте эти строки (с учётом синтаксиса языка!!!).
### Настройка страницы Спасибо.
Посетитель попадает на страницу Спасибо после того, как он отправляет свои данные с блэка *или вайта*! Контент страницы подгружается из папки *thankyou* кло. Если посмотреть, в ней лежит несколько html-файлов, названных двухбуквенными кодами языков. Введите нужный язык страницы спасибо в  **$thankyou_page_language**.

Если для вашего языка нет страницы Спасибо - создайте её. Это просто: загружаем в браузере Chrome англоязычный вариант страницы Спасибо и встроенным переводчиком переводим на нужный язык. Далее сохраняем перевод под нужным именем, например *IT.html*.
**Внимание**: откройте переведённую страницу в текстовом редакторе и убедитесь, что 2 макроса *{NAME}* and *{PHONE}* НЕ были переведены. Если были - верните их на место!

Если вы хотите использовать свою собственную страницу Спасибо, то переименуйте её двухбуквенным кодом языка и положите все нужные файлы в папку *thankyou*.
#### Сбор почт на странице Спасибо
На странице Спасибо по умолчанию есть форма сбора email-адресов. Если она вам не нужна - просто удалите её  в коде страницы. Но если нужна, то вам нужно создать ещё одну страницу: ту, на которую пользователь попадёт ПОСЛЕ того, как оставит свою почту. Она должна быть названа в виде двухбуквенного названия языка + email.html. Например: *SKemail.html*. В папке *thankyou* лежит пример такой страницы.

## Настройка пикселей
Вы можете добавить различные пиксели на ваши проклы и ленды. Вот полный список:
- Яндекс Метрика
- Google Tag Manager
- Facebook Pixel

### Яндекс Метрика
Чтобы добавить скрипт Яндекс Метрики на ваши прелендинги и лендинги, просто заполните ID метрики в **$ya_id**.
### Google Tag Manager
Чтобы добавить скрипт Google Tag Manager на ваши прелендинги и лендинги, просто заполните GTM ID в **$gtm_id**.
### Facebook Pixel
ID пикселя фб кло получает из ссылки. Он должен быть в ней в формате: *px=1234567890*. Например:
`https://ваш.домен?px=5499284990`
Если в адресе есть параметр *px*, тогда кло добавит полный Javascript-код пикселя фб на страницу Спасибо. Вы можете задать нужное событие пикселя в переменной **$fb_thankyou_event**. По умолчанию это *Lead*, но вы можете поменять его на *Purchase* или на любое другое.
Вы также можете использовать событие *PageView*. Чтобы включить его, поменяйте **$fb_use_pageview** на *true*. После этого код пикселя будет добавлен на все основные страницы прокл и лендов и эти страницы будут слать событие *PageView* в фб для каждого посетителя.
**Примечание:** Используйте плагин *Facebook Pixel Helper* для Google Chrome чтобы проверить, что события отсылаются корректно!
## Настройка фильтров кло
Кло умеет фильтровать траф по следующим критериям:
- Встроенная база IP
- ОС посетителя
- Страна посетителя
- User Agent посетителя (браузер)
- ISP посетителя (провайдер)
- Наличие реферера
- По любой части ссылки, по которой был переход

*Примечание:* везде, где вы хотите использовать несколько параметров, используйте запятую в качестве разделителя!
Для начала, добавьте все разрешённые операционные системы в **$os_white**. Вот список доступных:
- Android
- iOS
- Windows
- Linux
- OS X
- и другие не особо популярные...

Выберите те, что вам нужны.
Затем заполните все двухбуквенные коды разрешённых стран в **$country_white**. Например: *RU,RS,IT,ES*. 

Теперь избавьтесь от всех ненужных интернет-провайдеров. Добавьте их в **$isp_black**. Например: *google,facebook,yandex*. Если вы хотите защитить свою связку от спай-сервисом, то добавьте сюда всех облачных провайдеров, навроде: *amazon,azure* и т.п.

Добавьте в список запрещённых User Agent-ов **$ua_black** слова, по которым они будут фильтроваться. 
Например: *facebook,Facebot,curl,gce-spider*

Добавьте список слов, которые могут быть в ссылке, по которой перешёл посетитель, которые сигнализируют вам о том, что ему надо показать вайт в  **$tokens_black** или оставьте эту переменную пустой - ''.

Если у вас есть доп. список IP адресов от которых вы хотите избавиться - добавьте их в **$ip_black**.

И наконец: если вы хотите блокировать *прямых* посетителей тогда измените **$block_without_referer** на *true*. **Внимание**: некоторые ОС и браузеры некорректно передают реферер или не передают его вовсе. Так что, если хотите  использовать эту фишку, проверьте её сначала на небольшом объёме трафа, иначе вы можете потерять $$.

## Настройка распределения трафа
Вы можете временно выключить все фильтры кло и слать весь траф на вайт. Например, во время модерации. Для этого измените **$full_cloak_on** на *true*.
Также вы можете выключить все фильтры кло и слать весь траф на блэк. Например, для тестирования блэка. Для этого измените **$disable_tds** на *true*.
Вы можете сохранять "путь" пользователя (т.е. те преленды и ленды на которые он попадёт в воронке). Тогда он всегда, сколько бы раз он не зашёл, будет видеть одни и те же страницы. Для этого измените **$save_user_flow** на *true*.
## Настройка статистики и постбэка
Просмотр статистика защищён паролем. Задайте его в переменной **$log_password**.
Если вы всегда называете свои креативы одинаково и передаёте их названия в кло из вашего источника трафа, то на странице статистики вы сможете посмотреть, сколько было кликов с того или иного крео. Для этого задайте название параметра в котором передаются имена крео в переменной **$creative_sub_name**. Например, если ссылка в источника трафа выглядит так:
`https://your.domain?mycreoname=greatcreo`
тогда вам нужно изменить переменную следующим образом:
`$creative_sub_name = 'mycreoname';`
после чего в стате вы увидите:
*greatcreo - 154 клика*
### Настройка постбэка
Кло умеет получать постбэки из ПП и показывать статус лидов в стате. Для начала, вам надо передавать в ПП уникальный id посетителя - subid. Subid создаётся для каждого посетителя автоматом и хранится в куки. Вы должны спросить вашего менеджера, как передавать subid в ПП (они обычно знают этот параметр под именем clickid). Пусть они скажут вам, в какой суб-метке вам надо его передавать, потому что у разных ПП разные суб-метки. У кого-то они называются *sub1* *sub2* и т.д., а где-то *subacc*, где-то как-то ещё. Для примера представим, что суб-метка называется *sub1*. За передачу параметров в ПП отвечает массив **$sub_ids**. Изменим название справа от *subid* на *sub1*:
`$sub_ids = array("subid"=> "sub1", .....);`
Так мы настраиваем кло взять значение куки *subid* и передать его в метку *sub1*. Если, скажем *subid* был *12uion34i2* в итоге получится:
- если был локальный ленд, то во все формы ленда добавится скрытое input-поле
`<input type="hidden" name="sub1" value="12uion34i2"`
- если у нас редирект, то будет: `http://redirect.link?sub1=12uion34i2`

Далее нам надо указать в ПП, куда слать постбэк. В кло за обработку постбэков отвечает файл *postback.php*. Нам нужно получить из ПП 2 параметра: *subid* и статус лида. Используя две эти вещи кло меняет у себя в логах статус лида и отображает изменение в Статистике.
Посмотрите в справке ПП или спросите вашего менеджера, какой макрос использует ПП для передачи статуса лида. Обычно он так и называется, *{status}*. Возвращаясь к нашему примеру: поскольку мы отправляли *subid* в суб-метке *sub1*, макрос для получения *subid* из ПП будет *{sub1}*. Давайте создадим полный адрес постбэка. Вы должны вставить его в поле Postback Url в вашей ПП. Например:
`https://your.domain/postback.php?subid={sub1}&status={status}`
И, наконец, разберитесь сами или спросите менеджера, какие статусы шлёт ПП в постбэке. Обычно это:
- Lead
- Purchase
- Reject
- Trash

Если ваша ПП шлёт статусы по-другому, то исправьте значения следующих переменных соответственно настройкам ПП:
- **$lead_status_name**
- **$purchase_status_name**
- **$reject_status_name**
- **$trash_status_name**

После настройки отправьте тестового лида и на странице Лиды в статистике наблюдайте, как лид изменит статус на Треш.

## Настройка дополнительных скриптов
### Отключение кнопки "Назад"
Вы можете отключить кнопку "Назад" в браузере посетителя, чтобы он не мог покинуть вашу страницу. Для этого измените **$$disable_back_button** на *true*.
### Замена кнопки "Назад"
Вы можете изменить адрес, на который попадёт посетитель, нажав кнопку "Назад". Эту фишку можно использовать для домонетизации и для отправки посетителя на другой оффер. Изменяем **$replace_back_button** на *true* и вводим адрес в **$replace_back_address**.
**Внимание:** Не используйте этот скрипт вместе со скриптом **Отключения кнопки Назад**!!!
### Запрет контекстного меню, выделения текста и сохранения по Ctlr+S
You can disable the ability to select text on your prelandings and landings, disable the ability to save the page using Ctrl+S keys and also disable the browser's context menu. To do so just change **$disable_text_copy** to *true*.
### Замена прелендинга на другой сайт
Вы можете включить эту настройку для того, чтобы ленд открывался в новой вкладке браузера, а прокла бы заменялась на другой сайт. Это можно использовать для домонетизации трафа. Для включения измените **$replace_prelanding** на *true* и вставьте адрес в **$replace_prelanding_address**.
### Маски для телефонов
Вы можете настроить кло так, чтобы он применяла к полям ввода номера телефона определённые маска. Когда вы включите эту возможность, посетитель не сможет вводить буквы в номер и не сможет ввести больше или меньше цифр, чем требуется. В маске задаются префиксы телефона, кол-во цифр и разделители. Чтобы включить маски измените **$black_land_use_phone_mask** на *true* и отредактируйте саму маску в **$black_land_phone_mask**.
# Проверка
Добавьте код вашей страны в список разрешённых, чтобы иметь возможность перейти на блэк. Пройдите по всем элементам воронки. Проверьте пиксель и отстукивание лидов в ПП, постбэк.
# Просмотр трафика и статистики
После того, как вы начали лить, вы можете просматривать стату по трафику на странице Статистика:
`https://your.domain/logs?password=yourpassword`
где *yourpassword* это значение переменной **$log_password** из файла *settings.php*.
# JS-интеграция кло с конструкторами
`<script src = 'https://your.domain/js/index.php'></script>`
# Контакты
По всем вопросам пишите Issues на GitHub либо в паблик http://vk.com/yellowweb

# Description
Modified cloaking script for affiliate marketing found somewhere on [Black Hat World](http://blackhatworld.com).
If you like this software, [please donate a few bucks using this Telegram bot!](https://t.me/yellowwebdonate_bot)
# Installation
Just download the latest copy of all files from this repository and upload them to your hosting. Your hosting should allow to run PHP-scripts and you SHOULD create a HTTPS-certificate for your domain. **Without HTTPS the cloaker won't work properly!** I can definitely [recommend Beget Hosting for the cloaker](https://yellowweb.top/beget). It's cheap and convenient.

If you have local prelandings or landings, then create a folder for each of them in the root folder of the cloaker and copy all files there accordingly. 
*For example:*
If you have 2 prelandings and 2 landings create 2 folders for prelandings: p1 and p2. And 2 folders for landings: land1, land2.  

# Setup
Right now the cloaker doesn't have any UI for the settings. So, just open the settings.php file in any text-editor. I recommend Notepad++ for that, cause it has PHP-syntax highlighting and it'll be easier to read and edit.

## White Page Setup
White Page is a page that is shown to the visitor, which doesn't pass any of the cloaker's filters. So, it is for visitors, that we don't want.

First of all you need to decide, what kind of a white page action you want to use. The cloaker can use local whitepages, it can show any other site as a whitepage (without redirects), it can redirect white-traffic to any website and it can also show an error to such visitors.

When you decided, change the **$white_action** value to one of the following:
### site
This is for local whitepages. You need to create a folder in the root directory of the cloaker, for example: *white* and copy all of your whitepage's files there. Then write the folder name into **$white_folder_name** value. 
### redirect
Choose this, if you want to redirect all of the white traffic. Just enter the full website url into **$white_redirect_url** and also choose a redirect type. It can be 301,302,303 or 307. Google the difference if you need. Enter the value into **$white_redirect_type**.
### curl
Use it, if you want to load any other's site content on your domain without redirects. Enter full website's url into **$white_curl_url**.
### error
You can return any type of HTTP-errors for all of the white-traffic. For example: *404*. Just enter the error code into **$white_error_code**.

## Domain Specific White Pages
If you have MULTIPLE domains (or subdomains) parked to your hosting, and you run traffic for all of them, you can choose to use different white actions for different domains. To do it first of all change **$white_use_domain_specific** to *true*.

Then fill **$white_domain_specific** array. The fomat is like this
`"your.domain" => "whiteaction:value"`
An example is provided in the default settings.php file.
## Money Page Setup
Money page (called Black page here) can be one of the following:
- local landing page(s)
- local prelanding(s) + local landing(s)
- local prelanding(s) + redirect to the aff network's landing
- redirect

Let's dive into each of these configurations.
### Local landing page(s)
You can use one ore multiple landing pages if you need. The traffic will be distributed proportionally. For example 50-50 for 2 landings. Each landing should be in a separate folder. Make **$black_action = *'site'*** and put the folder name into **$black_land_folder_name**. In case of mutiple landings use comma as a separator. For example:
`$black_land_folder_name = 'land1,land2';`
*Note:* be sure to check, that you don't have anything in **$black_preland_folder_name**. It should be:
`$black_preland_folder_name = ''; `
### Local prelanding(s) + local landing(s)
Do everything the same as in the description for **Local landing page** but also fill the **$black_preland_folder_name**. For example, for two prelandings:
`$black_preland_folder_name = 'p1,p2';`
### Local prelanding(s) + redirect
Fill the **$black_preland_folder_name**. For example, for two prelandings:
`$black_preland_folder_name = 'p1,p2';`
Then change **$black_land_use_url** to *true*. Last step: put full redirect url int **$black_land_url**
### Redirect
If you just want to redirect all of your black traffic, then use **$black_action = *'redirect'*** and put the full url of the website, where you want to redirect people into **$black_redirect_url**. Also choose a redirect type. It can be 301,302,303 or 307. Google the difference if you need. Enter the value into **$black_redirect_type**.
### Setting up the local landing's conversion script
Each landing page has an ability to send leads to your affiliate network. And each affiliate network, that provide you these landings has their own script and mechanics for sending this info.
By default the cloaker will look for the *order.php* file, that should be located in the landing's folder. But if your script has a different name, then you should rename the value of **$black_land_conversion_script**. If your script is in some folder, the put this folder name before the script name like this:
`$black_land_conversion_script='folder/conversion.php';`
After setting this up send a test lead to your aff network. If you can't see the lead in you network's statistics, then open your conversion script and look for these kind of lines:
`exit();`
Remove or comment all of them. Then send a test lead again.
### Setting up the "Thank you" page
Thankyou page is a page, where the visitor is redirected after filling the lead form on you black landing OR on your whitepage (if you have one there). Thankyou page's content is loaded from the *thankyou* folder of the cloaker. It has several html-files there, named after the 2-symbol language code. Put the name of your required language into **$thankyou_page_language**. 

If there is no thankyou page for your language - create one! It is as easy as loading for example *EN.html* into your Chrome browser, translating it using the built-in Google Translate and then saving it using your language code. For example: *IT.html*. 
**Warning**: make sure that two macros: *{NAME}* and *{PHONE}* were not translated by Google. If they were, just change them back.

If you want to use your own thankyou page - just rename it using the same 2-symbol language code to the required language and put all its files into *thankyou* folder.
#### Collecting emails on the "Thank you" page
The default thankyou page has a built in email collect form. If you dont' need it - just delete it in code. But if you do, you need to create one more page: the one that the visitor will be redirected AFTER submitting the email form. It should be called using the same 2-symbols language code+email in the end. For example: *SKemail.html*.

## Pixels Setup
You can add various pixels on your prelandings and landings. Full list includes:
- Yandex Metrika
- Google Tag Manager
- Facebook Pixel

### Yandex Metrika
To add Yandex Metrika's script to your prelandings and landings just fill your Yandex Metrika id. Put it into **$ya_id**.
### Google Tag Manager
To add the Google Tag Manager's script to your prelandings and landings just fill your GTM id. Put it into **$gtm_id**.
### Facebook Pixel
Facebook Pixel's id is taken from the link, that you put into your traffic source. It should be in format *px=1234567890*. For example:
`https://your.domain?px=5499284990`
If the url has this *px* parameter, then the full javascript code of the Facebook Pixel will be added to the Thankyou page. You can set the Facebook Pixel's event in **$fb_thankyou_event** variable. By default it is *Lead* but you can change it to *Purchase* or anything that you need.
You can also use the pixel's *PageView* event. To do so, change **$fb_use_pageview** to *true*. If you do so, then the pixel's code will be added to all of your local prelandings and landings and they will send the *PageView* event for each visitor to Facebook.
Use Facebook Pixel Helper plugin for Google Chrome to check, if the pixel's event fire correctly!
## Cloaker's Filters Setup
The cloaker can filter traffic based on:
- Built in IP database
- Visitor's OS
- Visitor's country
- Visitor's User Agent
- Visitor's ISP
- Visitor's referer
- Any token in the url

*Note:* comma should be used everywhere, where multiple values are needed.
First of all put all of the OSes that should be allowed to view the black page into **$os_white**. The full list is:
- Android
- iOS
- Windows
- Linux
- OS X
- and some non-significant others

Choose any that you need. 
Then put all the country codes that are allowed into **$country_white**. For example: *RU,RS,IT,ES*. 

Now get rid of all of the Internet Service Providers that you don't need. Put them into **$isp_black**. For example: *google,facebook,yandex*. If you want to protect your landings from Spy services use *amazon,azure* and other cloud-providers here.

Put all the unnecessary User Agents into **$ua_black**. 
For example: *facebook,Facebot,curl,gce-spider*

Put all of the words, that can be found in the url that signal you, that this visitor should be shown the white page into **$tokens_black** or leave it empty.

If you have any additional IP addresses that you want to get rid of - put them into **$ip_black**.

And last but not least: if you want to block *direct* visitors from seeing your black page, then change **$block_without_referer** to *true*. **Warning**: some OSes and browsers don't pass the referer correctly, so test this first on a small amount of traffic or you'll loose money.

## Traffic Distibution Setup
You can temporary disable all of your filters and send all traffic to the whitepage. For example, you can use it for moderation. To do so, change **$full_cloak_on** to *true*.
You can also disable the filters and always show the blackpage. For example, for testing purposes. To do so change **$disable_tds** to *true*.
You can save the user's flow (the prelandings and the landgins which will be shown to the visitor) so (s)he will always see the same pages when (s)he visits the site for the second time or even just refreshes the page. To do so, change **$save_user_flow** to *true*.
## Statistics and Postback Setup
Your statistics is protected with a password, to set it, please fill the **$log_password** variable.
If you name your creatives properly and pass their names from the traffic source, you can see the number of clicks for each of the creative in the Statistics. To do so, please put the parameter name in which you pass the creative name into **$creative_sub_name** variable. For example, if you link looks like this:
`https://your.domain?mycreoname=greatcreo`
then you need to do it like this:
`$creative_sub_name = 'mycreoname';`
### Postback setup
The cloaker is able to receive postbacks from your aff network. To do so, first of all you need to pass the unique visitor's id (called subid here) to your network. Subid is created for each visitor and is stored in a cookie. You should ask your aff manager, how should you pass this id (they know it as "clickid") and what sub-parameter should you use. Usually it is done using sub-parameters like *sub1* or *subacc*. Let's stick to *sub1* for this example. So, we should edit the **$sub_ids** array, the part, that has *subid* on the left side to look like this:
`$sub_ids = array("subid"=> "sub1", .....);`
This way we tell the cloaker to take the value of *subid* and add it to all forms on the landing in the form of *sub1* (or add it to your redirect link, if you don't have local landing). So if the *subid* was *12uion34i2* we will have:
- in case of local landing
`<input type="hidden" name="sub1" value="12uion34i2"`
- in case of redirect `http://redirect.link?sub1=12uion34i2`

Now we need to tell the aff network where to send the postback info. The cloaker has *postback.php* file in its root folder. It is the file, which receives and processes postbacks. We need to receive 2 parameters from the aff network: *subid* and lead status. Using this two things we can change the lead status in our logs and show this change in statistics.
Look in help or ask your manager: what macros does your network use to send *status*, usually it is called the same: *{status}*. So, returning to our example: we sent *subid* in *sub1* so the macros to receive back our *subid* will be *{sub1}*. Let's create a full postback url. You should put this url in the Postback field of your Aff Network. For example:
`https://your.domain/postback.php?subid={sub1}&status={status}`
Now, ask your aff manager or look in their help section, what are the statuses, that they send us in postback. Usually they are:
- Lead
- Purchase
- Reject
- Trash

If your aff network uses other statuses then change these variable values accordingly:
- **$lead_status_name**
- **$purchase_status_name**
- **$reject_status_name**
- **$trash_status_name**

After setting this up send a test lead and watch on the Leads page how the status changes to *Trash* after a while.

## Additional Scripts Setup
### Disable Back Button
You can disable the back button in the visitor's browser, so (s)he can't leave your page. To do so change **$$disable_back_button** to *true*.
### Replace Back Button
You can replace the url of the back button in the visitor's browser. So after (s)he clicks on it, (s)he will be redirected to some other place, for example to another offer. To do so change **$replace_back_button** to *true* and put the url that you want into **$replace_back_address**.
**Warning:** Don't use this script with **Disable Back Button** script!!!
### Disable Text Selection, Ctrl+S and Context Menu
You can disable the ability to select text on your prelandings and landings, disable the ability to save the page using Ctrl+S keys and also disable the browser's context menu. To do so just change **$disable_text_copy** to *true*.
### Replacing Prelanding
You can make the cloaker to open the landing page in a separate browser's tab and then redirect the tab with the prelanding to another url. After the user closes your landing page tabe (s)he'll see the tab with this url. Use it to show another offer to the user. To do so change **$replace_prelanding** to *true* and put your url into **$replace_prelanding_address**.
### Phone Masks
You can tell the cloaker to use masks for the phone field on your local landings. When you do so, the visitor won't be able to add any letters into the phone field, only numbers. The mask defines numbers count and delimeters. To enable masks just change **$black_land_use_phone_mask** to *true* and edit your mask in **$black_land_phone_mask**.
# Check Up
Add your own country to the cloaker's filters to be able to see the black page. Then go through all of the funnel's components. Send a test lead, verify that it reached your aff network.
# Running traffic and Statistics
After you started running traffic you can monitor it and also look at the statistics. To do so just go to a link like this:
`https://your.domain/logs?password=yourpassword`
where *yourpassword* is a value of **$log_password** from the settings.php file.

# Javascript Integration
You can connect this cloaker to any website or website-builder that allows adding Javascript. For example: *GitHub, Wix, Shopify* and so on.
When you do so you run traffic to the website-builder and after the visitor comes to this site a little script checks, if (s)he is allowed to view the blackpage. If (s)he is, then 2 things can happen:
- A redirect to your blackpage
- Website builder's content is replaced by the blackpage

## Redirect
Just add this script to your website builder:
`<script src="https://your.domain/js/indexr.php"></script>`

## Content replacing
Just add this script to your website builder:
`<script src="https://your.domain/js"></script>`
Don't use this method if you have only landings without prelandings!
# Technical Details
## Used components
This cloaker uses:
- MaxMind Databases for ISP, Country and City detection
- Bot IP Ranges from various sources collected all over from the Internet in CIDR format
- Sinergi BrowserDetector for (surprise!) browser detection
- IP Utils from Symphony for checking if the IP address is in a selected range

## Traffic flow
After the visitor passes the cloaker's filters he is usually shown the prelanding (if you have one). On the prelanding all links are being replaced by the link to the *landing.php* script. After the visitor clicks on the link, the *landing.php* script gets the landing's content, replaces action of all of the forms to *send.php*, adds all additional scripts and shows the content to the visitor. When the visitor fills the form and sends it *send.php* calls the original send script and then removes all of the redirects from it. After that *send.php* redirects to the *thankyou.php* which shows the thankyou page as described in the sections above.
