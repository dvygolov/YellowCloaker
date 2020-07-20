<script type = "text/javascript"> 
  history.pushState(null, null, location.href); 
  history.back(); 
  history.forward(); 
  window.onpopstate = function () { history.go(1); }; 
</script>