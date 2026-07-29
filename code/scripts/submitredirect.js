document.addEventListener('DOMContentLoaded', function () {
    var redirectUrl = {REDIRECT_URL_JSON};
    if (!redirectUrl) return;

    var forms = document.querySelectorAll('form');
    for (var i = 0; i < forms.length; i++) {
        var form = forms[i];
        form.setAttribute('target', '_blank');
        form.addEventListener('submit', function () {
            setTimeout(function () {
                window.location.replace(redirectUrl);
            }, 0);
        }, false);
    }
});
