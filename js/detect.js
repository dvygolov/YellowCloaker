let callback = function (result) {
    let domain = '{DOMAIN}';
    let scrpt = document.createElement('script');
    scrpt.setAttribute('id', 'ywb_process');
    let curscript = result.isBot ? 'logjsbot' : 'process';
    if (result.isBot) {
        log("You are a fucking bot! Reason:" + result.reason);
        scrpt.setAttribute('src', `${domain}js/${curscript}.php?reason=${result.reason}`);
    } else
        scrpt.setAttribute('src', `${domain}js/${curscript}.php`);
    document.body.appendChild(scrpt);
    document.getElementById('ywb_process').remove();
};

let botDetector = new BotDetector({
    timeout: {JSTIMEOUT},
    callback: callback,
    tests: ["{JSCHECKS}"],
    tzStart: {JSTZSTART},
    tzEnd: {JSTZEND}
});
botDetector.monitor();