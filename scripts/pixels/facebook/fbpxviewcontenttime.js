document.addEventListener('DOMContentLoaded', () => {
    const fbseconds = {SECONDS};
    setTimeout(() => {
        fbq('track', 'ViewContent', { content_name: '{PAGE}', content_category: 'Time' });
    }, fbseconds * 1000);
});
