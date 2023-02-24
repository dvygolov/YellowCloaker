window.addEventListener('DOMContentLoaded', () => {
    const handleEvent = (event) => {
        event.preventDefault();
        console.log('Started Facebook pixel firing...');
        const element = event.currentTarget;
        let node = element;
        while (node.nodeName !== 'FORM' && node.parentNode) {
            node = node.parentNode;
        }

        const name = node.querySelector('input[name="name"]');
        const phone = node.querySelector('input[name="phone"]');

        if (name.value != '' && phone.value != '') {
            fbq('track', '{EVENT}');
        }
    };

    const forms = document.querySelectorAll('form button, form input[type="submit"]');
    forms.forEach((form) => {
        form.addEventListener('click', handleEvent);
    });
});
