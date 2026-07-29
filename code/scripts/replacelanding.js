document.addEventListener('DOMContentLoaded', () => {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.setAttribute('target', '_blank');
        form.addEventListener('submit', formRedirect);
    });
});

function formRedirect(event) {
    event.preventDefault();
    setTimeout(() => {
        window.location.replace('{REDIRECT}');
    }, 2000);
}
