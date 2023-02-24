document.addEventListener('DOMContentLoaded', function() {
    function executeWhenReachedPagePercentage(percentage, callback) {
        if (typeof percentage !== 'number') {
            console.error(`First parameter must be a number, got ${typeof percentage} instead.`);
            return;
        }
        if (typeof callback !== 'function') {
            console.error(`Second parameter must be a function, got ${typeof callback} instead.`);
            return;
        }

        function getDocumentLength() {
            const D = document;
            const { body, documentElement } = D;
            return Math.max(
                body.scrollHeight, documentElement.scrollHeight,
                body.offsetHeight, documentElement.offsetHeight,
                body.clientHeight, documentElement.clientHeight
            );
        }

        function getWindowLength() {
            return window.innerHeight || (document.documentElement || document.body).clientHeight;
        }

        function getScrollableLength() {
            const documentLength = getDocumentLength();
            const windowLength = getWindowLength();
            return documentLength > windowLength ? documentLength - windowLength : 0;
        }

        let scrollableLength = getScrollableLength();

        window.addEventListener('resize', () => {
            scrollableLength = getScrollableLength();
        });

        function getCurrentScrolledLengthPosition() {
            return window.pageYOffset || (document.documentElement || document.body.parentNode || document.body).scrollTop;
        }

        function getPercentageScrolled() {
            if (scrollableLength === 0) {
                return 100;
            }
            return getCurrentScrolledLengthPosition() / scrollableLength * 100;
        }

        const executeCallback = (() => {
            let wasExecuted = false;
            return () => {
                if (!wasExecuted && getPercentageScrolled() > percentage) {
                    wasExecuted = true;
                    callback();
                }
            };
        })();

        if (getDocumentLength() === 0 || (getWindowLength() / getDocumentLength() * 100 >= percentage)) {
            callback();
        } else {
            window.addEventListener('scroll', executeCallback);
        }
    }

    executeWhenReachedPagePercentage({PERCENT}, () => {
        ttq.track('ViewContent');
    });
});