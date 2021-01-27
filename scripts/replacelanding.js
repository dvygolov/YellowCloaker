<script>
document.addEventListener("DOMContentLoaded", function() {

 var elements = document.getElementsByTagName('form');

 for (var i = 0; i < elements.length; i++) {
	elements[i].setAttribute('target','_blank');
	elements[i].addEventListener("submit",redirect,false);
 }
});

function redirect(){
    //меняет ленд через 2 секунды после перехода на Спасибо
	setTimeout(()=>window.location.replace("{REDIRECT}"),2000); 
}
</script>