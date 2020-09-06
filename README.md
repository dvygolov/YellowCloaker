# Binomo Cloaker Yellow Web Edition

**ВНИМАНИЕ:** Корректная работа кло гарантируется ТОЛЬКО на доменах с HTTPS!!! НЕ CloudFlare, нормальный, человеческий HTTPS!
## Описание
Модифицированный скрипт клоакинга для арбитража трафика, изначально найденный на просторах [Black Hat World](http://blackhatworld.com).
## Справочные материалы
- [Стрим на котором подробно разобрана кло со всеми функциями](https://www.youtube.com/watch?v=XMua15r2dwg&feature=youtu.be)
- [Видео с обзором новых возможностей тут.](https://www.youtube.com/watch?v=x-Z2Y4lEOc0&t=656s)
- [Описание настройки первых версий тут!](https://yellowweb.top/%d0%ba%d0%bb%d0%be%d0%b0%d0%ba%d0%b8%d0%bd%d0%b3-%d0%b4%d0%bb%d1%8f-%d0%b1%d0%b5%d0%b4%d0%bd%d0%be%d0%b3%d0%be-%d0%bd%d0%be-%d1%83%d0%bc%d0%bd%d0%be%d0%b3%d0%be-%d0%b0%d1%80%d0%b1%d0%b8%d1%82%d1%80/)

ВНИМАНИЕ: лог трафика и статистика находится в http://ваш.домен/logs?password=ваш_пароль
# Контакты
По всем вопросам пишите Issues на GitHub либо в паблик http://vk.com/yellowweb
# Поддержка
Если вы хотите, чтобы этот проект и дальше развивался, [**поддержите автора соткой-другой**!](https://capu.st/yellowweb)


# JS-интеграция кло с конструкторами
## Способ №1
В случае подключения этим способом, после проверки пользователя будет совершён редирект на блэк
`<script src="https://your.domain/js/indexr.php"></script>`

## Способ №2
В случае подключения этим способом, после проверки пользователя будет совершена полная подмена страницы на блэк
`<script src="https://your.domain/js" type="text/javascript"></script>`
# Description
Modified cloaking script for affiliate marketing found somewhere on [Black Hat World](http://blackhatworld.com).
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
`<script src = 'https://your.domain/js/indexr.php'></script>`

## Content replacing
Just add this script to your website builder:
`<script src = 'https://your.domain/js'></script>`

# Technical Details
## Used components
This cloaker uses:
- MaxMind Databases for ISP, Country and City detection
- Bot IP Ranges from various sources collected all over from the Internet in CIDR format
- Sinergi BrowserDetector for (surprise!) browser detection
- IP Utils from Symphony for checking if the IP address is in a selected range

## Traffic flow
After the visitor passes the cloaker's filters he is usually shown the prelanding (if you have one). On the prelanding all links are being replaced by the link to the *landing.php* script. After the visitor clicks on the link, the *landing.php* script gets the landing's content, replaces action of all of the forms to *send.php*, adds all additional scripts and shows the content to the visitor. When the visitor fills the form and sends it *send.php* calls the original send script and then removes all of the redirects from it. After that *send.php* redirects to the *thankyou.php* which shows the thankyou page as described in the sections above.
