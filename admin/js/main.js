(function ($) {
 "use strict";

	/*----------------------------
	 jQuery MeanMenu
	------------------------------ */
	jQuery('nav#dropdown').meanmenu();	
	/*----------------------------
	 jQuery myTab
	------------------------------ */
	$('#myTab a').click(function (e) {
		  e.preventDefault()
		  $(this).tab('show')
		});
		$('#myTab3 a').click(function (e) {
		  e.preventDefault()
		  $(this).tab('show')
		});
		$('#myTab4 a').click(function (e) {
		  e.preventDefault()
		  $(this).tab('show')
		});

	  $('#single-product-tab a').click(function (e) {
		  e.preventDefault()
		  $(this).tab('show')
		});
	
	$('[data-toggle="tooltip"]').tooltip(); 
	
	$('#sidebarCollapse').on('click', function () {
                     $('#sidebar').toggleClass('active');
                     
                 });
		// Collapse ibox function
			$('#sidebar ul li').on('click', function () {
				var button = $(this).find('i.fa.indicator-mn');
				button.toggleClass('fa-plus').toggleClass('fa-minus');
				
			});
	/*-----------------------------
			Menu Stick
		---------------------------------*/
		$(".sicker-menu").sticky({topSpacing:0});
			
		$('#sidebarCollapse').on('click', function () {
			$("body").toggleClass("mini-navbar");
		});
		$(document).on('click', '.header-right-menu .dropdown-menu', function (e) {
			  e.stopPropagation();
			});
 
	
/*----------------------------
 wow js active
------------------------------ */
// new WOW().init();
 
})(jQuery); 