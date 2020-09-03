# Binomo Cloaker Yellow Web Edition

**ВНИМАНИЕ: Корректная работа кло гарантируется ТОЛЬКО на доменах с HTTPS!!! Не CloudFlare, нормальный, человеческий HTTPS**

Если вы идёте в логи и видите, что поле SubID на вкладке Leads у вас пусто, то вы ТОЧНО льёте без https.

Модифицированный скрипт клоакинга, изначально найденный на просторах [Black Hat World](http://blackhatworld.com).

[Стрим на котором подробно разобрана кло со всеми функциями](https://www.youtube.com/watch?v=XMua15r2dwg&feature=youtu.be)

[Видео с обзором новых возможностей тут.](https://www.youtube.com/watch?v=x-Z2Y4lEOc0&t=656s)

[Описание настройки первых версий тут!](https://yellowweb.top/%d0%ba%d0%bb%d0%be%d0%b0%d0%ba%d0%b8%d0%bd%d0%b3-%d0%b4%d0%bb%d1%8f-%d0%b1%d0%b5%d0%b4%d0%bd%d0%be%d0%b3%d0%be-%d0%bd%d0%be-%d1%83%d0%bc%d0%bd%d0%be%d0%b3%d0%be-%d0%b0%d1%80%d0%b1%d0%b8%d1%82%d1%80/)


ВНИМАНИЕ: лог трафика и статистика находится в http://ваш.домен/logs?password=ваш_пароль

По всем вопросам пишите Issues на GitHub либо в паблик http://vk.com/yellowweb

[**Поддержать автора проекта**!](https://capu.st/yellowweb)


# JS-интеграция кло
**Способ 1**: в случае подключения этим способом, после проверки пользователя будет совершён редирект на блэк
`<script src = 'https://your.domain/js/indexr.php'></script>`

**Способ 2**: в случае подключения этим способом, после проверки пользователя будет совершена полная подмена страницы на блэк
`<script src = 'https://your.domain/js'></script>`

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
### Setting up the landing's conversion script

### Setting up the "Thank you" page
Thankyou page is a page, where the visitor is redirected after filling the lead form on you black landing OR on your whitepage (if you have one there). Thankyou page's content is loaded from the *thankyou* folder of the cloaker. It has several html-files there, named after the 2-symbol language code. Put the name of your required language into **$thankyou_page_language**. 

If there is no thankyou page for your language - create one! It is as easy as loading for example *EN.html* into your Chrome browser, translating it using the built-in Google Translate and then saving it using your language code. For example: *IT.html*. 
**Warning**: make sure that two macros: *{NAME}* and *{PHONE}* were not translated by Google. If they were, just change them back.

If you want to use your own thankyou page - just rename it using the same 2-symbol language code to the required language and put all its files into *thankyou* folder.
#### Collecting emails on the "Thank you" page
The default thankyou page has a built in email collect form. If you dont' need it - just delete it in code. But if you do, you need to create one more page: the one that the visitor will be redirected AFTER submitting the email form. It should be called using the same 2-symbols language code+email in the end. For example: *SKemail.html*.

## Pixels Setup
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
## Statistics and Postback Setup
## Thankyou Page Setup
## Additional Scripts Setup

# Check Up
# Running traffic and Statistics
# Technical Details
This cloaker uses:
- MaxMind Databases for ISP, Country and City detection
- Bot IP Ranges from various sources collected all over from the Internet in CIDR format
- Sinergi BrowserDetector for (surprise!) browser detection
- IP Utils from Symphony for checking if the IP address is in a selected range
