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
	
	/*
	var FileBrowserDialog = {
		init: function () {
			$('#files').delegate('a:not(.delete)', 'click', function(e) {
				e.preventDefault();
				var url = $(this).attr('href'),
					win = tinyMCEPopup.getWindowArg('window');
				
				win.document.getElementById(tinyMCEPopup.getWindowArg('input')).value = url;
				
				// are we an image browser
				if (typeof(win.ImageDialog) != 'undefined') {
					// we are, so update image dimensions...
					if (win.ImageDialog.getImageData)
						win.ImageDialog.getImageData();

					// ... and preview if necessary
					if (win.ImageDialog.showPreviewImage)
						win.ImageDialog.showPreviewImage(url);
				}

				// close popup window
				tinyMCEPopup.close();
			});
		}
	};
	tinyMCEPopup.onInit.add(FileBrowserDialog.init, FileBrowserDialog);
	*/
});
