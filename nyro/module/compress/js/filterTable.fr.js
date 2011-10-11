$(function() {
	var filterTable = $('.filterTable');
	if (filterTable.length) {
		if (!filterTable.is('.filterTableActive'))
			filterTable.hide();
		$('<a href="#" class="filterTableToggle" />')
			.text('afficher/montrer le filtre')
			.insertBefore(filterTable)
			.click(function(e) {
				e.preventDefault();
				filterTable.slideToggle();
			});
	}
});