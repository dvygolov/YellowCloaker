<script>
function addbuttonpixel(){
	var buttons=document.querySelectorAll("form button");
	buttons.forEach(function(button){
		button.addEventListener('click', function() { 
			firepixel(button);
		});
	});

	var submits=document.querySelectorAll("form input[type='submit']");
	submits.forEach(function(submit){
		submit.addEventListener('click', function() { 
			firepixel(button);
		});
	});
}

function firepixel(element){
	console.log("Started pixel fire..");
	var node = element;
	while (node.nodeName != "FORM" && node.parentNode) {
		node = node.parentNode;
	}
	
	var hasName=false;
	var hasPhone=false;
    var inputs=node.getElementsByTagName('input');

    for(i=0;i<inputs.length;i++){
        var input=inputs[i];
        if (input.name=="name" && input.value!==''){
			hasName=true;
		}
		if (input.name=="phone" && input.value!==''){
			hasPhone=true;
		}
    }
	if (hasName&&hasPhone)
		fbq('track', '{EVENT}');
}

window.addEventListener('DOMContentLoaded', addbuttonpixel, false);
</script>