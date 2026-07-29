function showIframe(b64) {
    function hideElementDelayed(selector) {
        let interval = setInterval(function() {
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

    let html = decodeURIComponent(escape(atob(b64)));
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
        document.addEventListener('DOMContentLoaded', function() {
            appendElement(container);
        });
    }
};