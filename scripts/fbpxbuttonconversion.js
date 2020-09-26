<script>
var buttons=document.querySelectorAll("form button:last-child");
buttons.forEach(function(button){
	button.addEventListener('click', function() { fbq('track', '{EVENT}')});
});
</script>