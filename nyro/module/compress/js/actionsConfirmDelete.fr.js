jQuery(function($) {
	$('a.delete').click(function(e) {
		if (!confirm('Etes-vous sûr de vouloir supprimer cet élément ?')) {
			e.preventDefault();
			return false;
		}
	});
});