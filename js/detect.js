let callback = function(detector) {
    let domain = '{DOMAIN}';
    let scrpt = document.createElement('script');
    scrpt.setAttribute('id', 'ywb_process');
    let curscript = detector.isBot ? 'logjsbot' : 'process';
    if (detector.isBot) {
        detector.log("You Shall Not Pass! Reason:" + detector.reason);
        scrpt.setAttribute('src', `${domain}js/${curscript}.php?reason=${detector.reason}`);
    } else {
        detector.log("You are a real human!");
        scrpt.setAttribute('src', `${domain}js/${curscript}.php`);
    }
    document.body.appendChild(scrpt);
    document.getElementById('ywb_process').remove();
};

let botDetector = new BotDetector({
    debug: {DEBUG},
    timeout: {JSTIMEOUT},
    callback: callback,
    tests: ["{JSCHECKS}"],
    tzStart: {JSTZSTART},
    tzEnd: {JSTZEND}
});
botDetector.monitor();
