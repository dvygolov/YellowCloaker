function mailCheck() {
	var r = $("#email");
	$(".email-box").hide(); 
	$(".thanks").fadeIn(500); 
	$.post("../mail.php", {email: r.val()});
}