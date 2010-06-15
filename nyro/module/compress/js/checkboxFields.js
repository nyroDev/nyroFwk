$(function() {
	$('.checkbox_fields > input').change(function() {
		var me = $(this),
			subFields = me.siblings('.subFields');
		me.is(':checked') ? subFields.slideDown() : subFields.slideUp();
	}).change();
});