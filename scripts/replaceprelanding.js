<script>
document.addEventListener("DOMContentLoaded", function() {

 var elements = document.getElementsByTagName('a');

 for (var i = 0; i < elements.length; i++) {
	 elements[i].addEventListener("click",redirect,false);
 }
});

function redirect(){
	setTimeout(()=>window.location.replace("{REDIRECT}"),2000); //меняет проклу через 2 секунды после перехода на ленд
}
</script>