document.addEventListener('DOMContentLoaded', function() {
    document.body.oncontextmenu = function () {
        return false;
    };
    window.addEventListener('selectstart', function (e) {
        e.preventDefault();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === "s" && (navigator.platform.match("Mac") ? e.metaKey : e.ctrlKey)) {
            e.preventDefault();
            e.stopPropagation();
        }
    }, false);
});
