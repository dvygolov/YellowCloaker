(function ($) {
 "use strict";

		var $table = $('#table');
				$('#toolbar').find('select').change(function () {
					$table.bootstrapTable('destroy').bootstrapTable({
						exportDataType: $(this).val()
					});
				});
 
})(jQuery); 