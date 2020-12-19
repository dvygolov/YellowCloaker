<script>
function logconversion(element) {
	console.log('starting logging');
	//get the form element
	var node = element;
	while (node.nodeName != "FORM" && node.parentNode) {
		node = node.parentNode;
	}
	
	var lead="";
	var hasName=false;
	var hasPhone=false;
    var inputs=node.getElementsByTagName('input');
	console.log('found '+inputs.length+' inputs');
    for(i=0;i<inputs.length;i++){
        var input=inputs[i];
        if (input.name=="name" && input.value!==''){
			hasName=true;
			console.log('found name');
			lead+=input.name+"="+input.value+"&";
		}
		if (input.name=="phone" && input.value!==''){
			console.log('found phone');
			hasPhone=true;
			lead+=input.name+"="+input.value+"&";
		}
    }
	if (hasName&&hasPhone){
		var ajx = new XMLHttpRequest();
		ajx.open("POST", "../buttonlog.php", true);
		ajx.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		ajx.send(lead);
	}
	else{
		console.log("No name or phone to log conversion!");
	}
}

function addconversionlog(){
	var buttons=document.querySelectorAll("form button");
	buttons.forEach(function(button){
		button.addEventListener('click', function() { 
			logconversion(this);
		});
	});

	var submits=document.querySelectorAll("form input[type='submit']");
	submits.forEach(function(submit){
		submit.addEventListener('click', function() { 
			logconversion(this);
		});
	});
}
window.addEventListener('DOMContentLoaded', addconversionlog, false);
</script>