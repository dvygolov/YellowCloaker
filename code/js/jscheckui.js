 let initUIfunc = function () {
    if (this.initialized) return;
    const loading = document.getElementById('loading');
    const checkboxContainer = document.getElementById('checkboxContainer');
    const inputContainer = document.getElementById('inputContainer');
    const deviceInstruction = document.getElementById('deviceInstruction');
    const checkbox = document.getElementById('checkbox');
    const humanInput = document.getElementById('humanInput');
    const countdown = document.getElementById('countdown');

    let countdownInterval;

    function initializeUI() {
        console.log('Security Check UI initialization...');
        if (!window.botDetector || !window.botDetector.selectedTests) {
            setTimeout(initializeUI, 100);
            return;
        }
        console.log('UI initialized!');
        const tests = window.botDetector.selectedTests;
        const hasKeydown = tests.includes('keydown');
        const hasPointerdown = tests.includes('pointerdown');
        let hasDeviceMotion = false;
        let hasDeviceOrientation = false;
        if (window.botDetector.isAndroidDevice()) {
            hasDeviceMotion = tests.includes('devicemotion');
            hasDeviceOrientation = tests.includes('deviceorientation');
        }

        loading.style.display = 'none';

        if (hasKeydown) {
            inputContainer.style.display = 'flex';
            if (!hasPointerdown) {
                humanInput.focus();
            }
        } else if (hasPointerdown) {
            checkboxContainer.style.display = 'flex';
        }

        if (hasDeviceMotion || hasDeviceOrientation) {
            deviceInstruction.style.display = 'block';
        }

        startCountdown();
    }

    function startCountdown() {
        if (!window.botDetector || !window.botDetector.timeout) return;

        let timeLeft = Math.floor(window.botDetector.timeout / 1000); 
        countdown.style.display = 'block';

        function updateCountdown() {
            if (timeLeft <= 0) {
                countdown.textContent = 'Time up!';
                countdown.className = 'countdown danger';
                clearInterval(countdownInterval);
                return;
            }

            countdown.textContent = `${timeLeft}s remaining`;

            if (timeLeft <= 5) {
                countdown.className = 'countdown danger';
            } else if (timeLeft <= 10) {
                countdown.className = 'countdown warning';
            } else {
                countdown.className = 'countdown';
            }

            timeLeft--;
        }

        updateCountdown();
        countdownInterval = setInterval(updateCountdown, 1000);
    }

    checkboxContainer.addEventListener('click', function () {
        checkbox.classList.add('checked');
    });

    humanInput.addEventListener('input', function () {
        if (this.value.length > 0) {
            this.style.borderColor = '#28a745';
        }
    });

    initializeUI();
    this.initialized = true;
};

document.addEventListener('DOMContentLoaded', initUIfunc);