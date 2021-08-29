var domain = '{DOMAIN}';
var callback = function (result) {
    var scrpt = document.createElement('script');
    scrpt.setAttribute('id', 'ywb_process');
    var curscript = result.isBot ? 'logjsbot' : 'process';
    if (result.isBot) {
        log("You are a fucking bot! Reason:" + result.reason);
        scrpt.setAttribute('src', 'https://' + domain + '/js/' + curscript + '.php?reason=' + result.reason);
    }
    else
        scrpt.setAttribute('src', 'https://' + domain + '/js/' + curscript + '.php');
    document.body.appendChild(scrpt);
    document.getElementById('ywb_process').remove();
};

var botDetector = new BotDetector({
    timeout: {JSTIMEOUT},
    callback: callback,
    tests: ["{JSCHECKS}"],
    tzStart: {JSTZSTART},
    tzEnd: {JSTZEND}
});
botDetector.monitor();