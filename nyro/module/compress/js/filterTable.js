$(function() {
	var filterTable = $('.filterTable');
	if (filterTable.length) {
		if (!filterTable.is('.filterTableActive'))
			filterTable.hide();
		var buttonList = $('#buttonList'),
			link = $('<a href="#" class="filterTableToggle" />')
				.text('show/hide filter')
				.on('click', function(e) {
					e.preventDefault();
					filterTable.slideToggle();
				});
		buttonList.length ? buttonList.prepend(link) : link.insertBefore(filterTable);
	}
});