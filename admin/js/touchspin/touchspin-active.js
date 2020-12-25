(function ($) {
 "use strict";
 
	 $(".touchspin1").TouchSpin({
		buttondown_class: 'btn btn-white',
		buttonup_class: 'btn btn-white'
	});

	$(".touchspin2").TouchSpin({
		min: 0,
		max: 100,
		step: 0.1,
		decimals: 2,
		boostat: 5,
		maxboostedstep: 10,
		postfix: '%',
		buttondown_class: 'btn btn-white',
		buttonup_class: 'btn btn-white'
	});

	$(".touchspin3").TouchSpin({
		verticalbuttons: true,
		buttondown_class: 'btn btn-white',
		buttonup_class: 'btn btn-white'
	});


	
	
 
})(jQuery); 