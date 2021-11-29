<script>
function addttbuttonpixel(){
	var buttons=document.querySelectorAll("form button");
	buttons.forEach(function(button){
		button.addEventListener('click', function() { 
			firettpixel(button);
		});
	});

	var submits=document.querySelectorAll("form input[type='submit']");
	submits.forEach(function(submit){
		submit.addEventListener('click', function() { 
			firettpixel(button);
		});
	});
}

function firettpixel(element){
	console.log("Started TikTok pixel firing..");
	var node = element;
	while (node.nodeName != "FORM" && node.parentNode) {
		node = node.parentNode;
	}
	
	var hasName=false;
	var hasPhone=false;
    var inputs=node.getElementsByTagName('input');
    for(i=0;i<inputs.length;i++){
        var input=inputs[i];
        if (input.name=="name" && input.value!=='') hasName=true;
		if (input.name=="phone" && input.value!=='') hasPhone=true;
    }
	if (hasName&&hasPhone) ttq.track('{EVENT}');
}

window.addEventListener('DOMContentLoaded', addttbuttonpixel, false);
</script>