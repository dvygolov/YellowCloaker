<script>
window.onload = function(){
		document.body.oncontextmenu= function(){return false;};
		window.addEventListener('selectstart', function(e){ e.preventDefault(); });
		document.addEventListener('keydown',function(e) {
			if (e.keyCode == 83 && (navigator.platform.match("Mac") ? e.metaKey : e.ctrlKey)) {
				e.preventDefault();
				e.stopPropagation();
			}
		},false);		
}
</script>