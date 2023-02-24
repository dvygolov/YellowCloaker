document.addEventListener('DOMContentLoaded', () => {
    const inputMaskOptions = {
        mask: '{MASK}',
        showMaskOnHover: true,
        showMaskOnFocus: true
    };
    const inputMask = new Inputmask(inputMaskOptions);

    const tels = document.querySelectorAll('input[type="tel"]');
    tels.forEach(tel => {
        inputMask.mask(tel);
    });
});
