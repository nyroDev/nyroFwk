$(function() {
	var filterTable = $('.filterTable');
	if (filterTable.length) {
		if (!filterTable.is('.filterTableActive'))
			filterTable.hide();
		$('<a href="#" class="filterTableToggle" />')
			.text('show/hide filter')
			.insertBefore(filterTable)
			.click(function(e) {
				e.preventDefault();
				filterTable.slideToggle();
			});
	}
});