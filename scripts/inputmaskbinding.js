<script>
function addinputmask(){
	var tels=document.querySelectorAll('input[type="tel"]');
	var im = new Inputmask({
	  mask: '{MASK}',
	  showMaskOnHover: true,
	  showMaskOnFocus: true	
	});
	
	for (var i=0; i<tels.length; i++){
		im.mask(tels[i]);
	}
}
document.addEventListener('DOMContentLoaded', addinputmask, false);
</script>
