jQuery(function($) {
	$('#checkAll').on('change', function() {
		var me = $(this);
		me.closest('form').find('input[type=checkbox]').prop('checked', me.is(':checked'));
	});
});