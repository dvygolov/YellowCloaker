<script>
// Delay pixel fire by 3 seconds
var seconds = {SECONDS};
setTimeout(function() {
  fbq('track', 'ViewContent',{content_name:'{PAGE}',content_category:'Time'});
}, seconds * 1000);
</script>