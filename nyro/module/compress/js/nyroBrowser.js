$(function() {
	
	$('#files').delegate('a:not(.delete)', 'click', function(e) {
		e.preventDefault();
		
		var url = $(this).attr('href'),
			file = parent.nyroBrowserFile,
			win = file.parent().parent().parent(),
			width = win.find('#width'),
			height = win.find('#height');

		file.value(url);
		
		if (width)
			width.value($(this).data('width'))
		if (height)
			height.value($(this).data('height'))
		
		parent.nyroBrowserWin.close();
	});
	
});
