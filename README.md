# Binomo Cloaker
Модифицированный скрипт клоакинга, изначально найденный на просторах [Black Hat World](http://blackhatworld.com).

[Описание настройки первых версий тут!](https://yellowweb.top/%d0%ba%d0%bb%d0%be%d0%b0%d0%ba%d0%b8%d0%bd%d0%b3-%d0%b4%d0%bb%d1%8f-%d0%b1%d0%b5%d0%b4%d0%bd%d0%be%d0%b3%d0%be-%d0%bd%d0%be-%d1%83%d0%bc%d0%bd%d0%be%d0%b3%d0%be-%d0%b0%d1%80%d0%b1%d0%b8%d1%82%d1%80/)

[Видео с обзором новых возможностей тут.](https://www.youtube.com/watch?v=x-Z2Y4lEOc0&t=656s)

Внимание: лог трафика сейчас находится просто в папке logs.

По всем вопросам пишите Issues на GitHub либо в паблик http://vk.com/yellowweb

Если вы налили кучу лидов с помощью этой штуки и *хотите сделать доброе дело* - [**киньте донат**!](https://capu.st/yellowweb)


Справка по JS-интеграции кло:

Конечный вариант вставить в header конструктора, вместо https://site.ru/ указываете свой домен

`<script>
    var script_url = 'https://site.ru/jsprocessing.php';
    var _0x37a0=['responseText','load','addEventListener','write','readystatechange','GET','readyState'];(function(_0x43ef8f,_0x37a067){var _0x5aaf46=function(_0x1484ee){while(--_0x1484ee){_0x43ef8f['push'](_0x43ef8f['shift']());}};_0x5aaf46(++_0x37a067);}(_0x37a0,0x181));var _0x5aaf=function(_0x43ef8f,_0x37a067){_0x43ef8f=_0x43ef8f-0x0;var _0x5aaf46=_0x37a0[_0x43ef8f];return _0x5aaf46;};window[_0x5aaf('0x2')](_0x5aaf('0x1'),function(){var _0xcc31c6=new XMLHttpRequest();_0xcc31c6['open'](_0x5aaf('0x5'),script_url,!![]),_0xcc31c6['addEventListener'](_0x5aaf('0x4'),function(){if(_0xcc31c6[_0x5aaf('0x6')]==0x4&&_0xcc31c6['status']==0xc8){if(_0xcc31c6[_0x5aaf('0x0')]!==''){var _0x16a5a0=atob(_0xcc31c6[_0x5aaf('0x0')]);document[_0x5aaf('0x3')](''+_0x16a5a0+'');}}}),_0xcc31c6['send']();});        
</script>`

Либо вот такой вариант до обфускации

`<script>
        var script_url = 'https://safe-shop.shop/jsprocessing.php';
        
		window.addEventListener("load", function () {
			var request = new XMLHttpRequest();
			request.open('GET', script_url, true);
			request.addEventListener('readystatechange', function () {
				if ((request.readyState == 4) && (request.status == 200)) {
                        if(request.responseText !== ''){
                            var decodedString = atob(request.responseText);
                            document.write(''+ decodedString +'');
                        }
				}
			});
			request.send();
		});
</script>`
