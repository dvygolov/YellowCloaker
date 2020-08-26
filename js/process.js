(function() {
var domain = '{DOMAIN}';
var xhr = new XMLHttpRequest();
xhr.withCredentials = true;
var url = 'https://'+domain+'/js/jsprocessing.php';
var refandurl="referrer=" + escape(document.referrer) + "&uri=" + escape(window.location.href);
if (window.location.search!==''){
    url+=window.location.search;
    url+="&"+refandurl;
}
else
    url+="?"+refandurl;
xhr.open("GET", url, true);
xhr.onload = function() {
     if(xhr.status !== 200) return;

     if (xhr.getResponseHeader("YWBRedirect") === "true") {
            console.log(xhr.getResponseHeader("YWBLocation"));
            document.open();
            document.write('<html><head>');
            document.write('<meta name="referrer" content="never" />');
            document.write('<meta http-equiv="refresh" content="0; url='+xhr.getResponseHeader("YWBLocation")+'" />');
            document.write('</head></html>');
            document.close();
    }
    else {
			if (xhr.responseText==='') {
                console.log('You are not allowed to go futher!');
                return;
            }
            document.open();
            var respText=xhr.responseText.replace('<head>','<head><base href="https://'+domain+'"/>');
            document.write(respText);
            document.close();
    }
};
xhr.send();
})();