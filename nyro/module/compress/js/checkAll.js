jQuery(function($) {
	$('#checkAll').change(function() {
		var me = $(this);
		me.closest('form').find('input[type=checkbox]').attr('checked', me.attr('checked'));
	});
});