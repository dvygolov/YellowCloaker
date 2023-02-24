window.addEventListener('DOMContentLoaded', () => {
    const handleEvent = (event) => {
        event.preventDefault();
        console.log('Started Facebook pixel firing...');
        const element = event.currentTarget;
        let node = element;
        while (node.nodeName !== 'FORM' && node.parentNode) {
            node = node.parentNode;
        }

        const hasName = node.querySelector('input:not([value=""])[name="name"]');
        const hasPhone = node.querySelector('input:not([value=""])[name="phone"]');

        if (hasName && hasPhone) {
            fbq('track', '{EVENT}');
        }
    };

    const forms = document.querySelectorAll('form button, form input[type="submit"]');
    forms.forEach((form) => {
        form.addEventListener('click', handleEvent);
    });
});
