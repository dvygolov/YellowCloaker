function metaRedirect(url) {
    document.open();
    document.write(`
        <html>
            <head> 
            <meta name="referrer" content="never" /> 
            <meta http-equiv="refresh" content="0; url=$url" /> 
            </head>
        </html>`);
    document.close();
};