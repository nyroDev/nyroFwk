jQuery(function($) {
	$('.checkbox_fields > input').on('change', function() {
		var me = $(this),
			subFields = me.siblings('.subFields');
		me.is(':checked') ? subFields.slideDown() : subFields.slideUp();
	}).trigger('change');
});