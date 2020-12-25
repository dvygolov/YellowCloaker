(function ($) {
 "use strict";

		/*--------------------------
		 mCustomScrollbar
		---------------------------- */	
			$(window).on("load",function(){
				$(".message-menu, .notification-menu, .comment-scrollbar, .notes-menu-scrollbar, .project-st-menu-scrollbar, .report-graph-scroll, .report-graph-scroll2").mCustomScrollbar({
					axis:"x",
					axis:"y",
					autoHideScrollbar: true,
					scrollbarPosition: "outside",
					theme:"light-1"
					
				});
				$(".timeline-scrollbar").mCustomScrollbar({
					setHeight:636,
					autoHideScrollbar: true,
					scrollbarPosition: "outside",
					theme:"light-1"
					
				});
				$(".project-list-scrollbar").mCustomScrollbar({
					setHeight:636,
					theme:"light-2"
				});
				$(".messages-scrollbar").mCustomScrollbar({
					setHeight:503,
					autoHideScrollbar: true,
					scrollbarPosition: "outside",
					theme:"light-1"
				});
				$(".chat-scrollbar").mCustomScrollbar({
					setHeight:250,
					theme:"light-2"
				});
				$(".widgets-chat-scrollbar").mCustomScrollbar({
					setHeight:335,
					autoHideScrollbar: true,
					scrollbarPosition: "outside",
					theme:"light-1"
				});
				$(".widgets-todo-scrollbar").mCustomScrollbar({
					setHeight:322,
					autoHideScrollbar: true,
					scrollbarPosition: "outside",
					theme:"light-1"
				});
				$(".user-profile-scrollbar").mCustomScrollbar({
					setHeight:1820,
					autoHideScrollbar: true,
					scrollbarPosition: "outside",
					theme:"light-1"
				});
			});
			
			
		 
 
})(jQuery); 