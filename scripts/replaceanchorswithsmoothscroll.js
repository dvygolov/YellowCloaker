document.addEventListener('DOMContentLoaded', function() {
    const anchors = document.querySelectorAll('a[href^="#"]');
    for (let anchor of anchors) {
        let id = anchor.getAttribute('href').substring(1);
        anchor.removeAttribute('onclick');
        //anchor.removeAttribute('href');
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            document.getElementById(id).scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        });
    }
});