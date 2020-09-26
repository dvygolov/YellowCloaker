<script>
function pixelFB(id) {
	fetch('https://www.facebook.com/tr?id=' + id + '&ev={EVENT}&noscript=1', {
		'credentials': 'omit',
		'referrerPolicy': 'origin',
		'method': 'GET',
		'mode': 'no-cors'
	});
}
pixelFB('{PIXELID}');
</script>