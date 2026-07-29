(function() {
    let jsApiUrl = "{APIURL}";
    const curParams = new URLSearchParams(window.location.search);
    const params = new URLSearchParams();
    if (curParams.size>0)
        params.append('tds_qs', btoa(decodeURIComponent(window.location.search.replace("?", ""))));
    params.append('tds_ref', document.referrer);
    jsApiUrl += `?${params.toString()}`;
    
    const script = document.createElement("script");
    script.type = "application/javascript";
    script.src = jsApiUrl;

    const firstScript = document.getElementsByTagName("script")[0];
    firstScript.parentNode.insertBefore(script, firstScript);
})();