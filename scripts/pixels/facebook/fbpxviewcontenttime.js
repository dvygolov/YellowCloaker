<script>
// Delay pixel fire
var fbseconds = {SECONDS};
setTimeout(function() {
  fbq('track', 'ViewContent',{content_name:'{PAGE}',content_category:'Time'});
}, fbseconds * 1000);
</script>