window.addEventListener('DOMContentLoaded', () => {
    const handleEvent = (event) => {
        event.preventDefault();
        console.log('Started Facebook pixel firing...');
        let node = event.currentTarget;
        while (node.nodeName !== 'FORM' && node.parentNode) {
            node = node.parentNode;
        }

        const name = node.querySelector('input[name="name"]');
        const phone = node.querySelector('input[name="phone"]');

        if (name.value !== '' && phone.value !== '') {
            ttq.track('{EVENT}');
        }
    };

    const formButtons = document.querySelectorAll('form button, form input[type="submit"]');
    formButtons.forEach((button) => {
        button.addEventListener('click', handleEvent);
    });
});