(function ($) {
 "use strict";
 
	$("#ionrange_1").ionRangeSlider({
		min: 0,
		max: 5000,
		type: 'double',
		prefix: "$",
		maxPostfix: "+",
		prettify: false,
		hasGrid: true
	});
	$("#ionrange_2").ionRangeSlider({
            min: 0,
            max: 10,
            type: 'single',
            step: 0.1,
            postfix: " carats",
            prettify: false,
            hasGrid: true
        });
	 $("#ionrange_3").ionRangeSlider({
		min: -50,
		max: 50,
		from: 0,
		postfix: "&deg;",
		prettify: false,
		hasGrid: true
	});

	$("#ionrange_4").ionRangeSlider({
		values: [
			"January", "February", "March",
			"April", "May", "June",
			"July", "August", "September",
			"October", "November", "December"
		],
		type: 'single',
		hasGrid: true
	});
	
	
 
})(jQuery); 