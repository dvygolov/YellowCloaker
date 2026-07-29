document.addEventListener('DOMContentLoaded', function () {
    var nextUrl = {NEXT_URL_JSON};
    var redirectUrl = {REDIRECT_URL_JSON};
    if (!nextUrl || !redirectUrl) return;

    function normalize(url) {
        try {
            return new URL(url, window.location.href).toString();
        } catch (e) {
            return '';
        }
    }

    var normalizedNextUrl = normalize(nextUrl);
    var links = document.getElementsByTagName('a');
    for (var i = 0; i < links.length; i++) {
        var link = links[i];
        var href = link.getAttribute('href');
        if (!href) continue;

        var isNextLink = href === nextUrl || normalize(href) === normalizedNextUrl;
        if (!isNextLink) continue;

        link.setAttribute('target', '_blank');
        link.setAttribute('rel', 'noopener');
        link.addEventListener('click', function () {
            setTimeout(function () {
                window.location.replace(redirectUrl);
            }, 0);
        }, false);
    }
});
