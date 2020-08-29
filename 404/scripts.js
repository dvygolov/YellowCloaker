function mailCheck() {
	var r = $("#email");
	$(".email-box").hide(); 
	$(".thanks").fadeIn(500); 
	$.post("/thankyou/mail.php", {email: r.val()});
	setTimeout(function(){document.location.href="/";},2000);
}