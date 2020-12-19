var domain = '{DOMAIN}';
var callback = function(result) {
	if (result.isBot) {
		log("You are a fucking bot!");
		var scrpt = document.createElement('script');
        scrpt.setAttribute('id', 'ywb_process'); 
		scrpt.setAttribute('src','https://'+domain+'/js/logjsbot.php');
        document.body.appendChild(scrpt);
        document.getElementById('ywb_process').remove();
	}
	else {
        var scrpt = document.createElement('script');
        scrpt.setAttribute('id', 'ywb_process'); 
		scrpt.setAttribute('src','https://'+domain+'/js/process.php?reason='+result.reason);
        document.body.appendChild(scrpt);
        document.getElementById('ywb_process').remove();
	}
};
var botDetector = new BotDetector({
	timeout: {JSTIMEOUT},
	callback: callback,
	tests:["{JSCHECKS}"]
});
botDetector.monitor();