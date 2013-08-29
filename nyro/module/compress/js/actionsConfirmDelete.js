jQuery(function($) {
	$('a.delete').on('click', function(e) {
		if (!confirm('Are you sure you want to delete this element?')) {
			e.preventDefault();
			return false;
		}
	});
});