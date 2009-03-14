jQuery(function($) {
	var nyroDebugger = $('#nyroDebugger');
	var debugElts = $('div.debugElt', nyroDebugger);
	$('#nyroDebugger > ul > li > a').click(function() {
		var elt = $('#'+$(this).attr('rel'));
		if (elt.is(':visible'))
			elt.slideToggle(300);
		else {
			var visible = debugElts.filter(':visible');
			if (visible.length)
				visible.slideToggle(300, function() {
					elt.slideToggle(300);
				});
			else
				elt.slideToggle(300);
		}
	});
	$('ul > li#close', nyroDebugger).click(function() {
		nyroDebugger.remove();
	});
	$('img.close', debugElts).click(function() {
		debugElts.filter(':visible').slideToggle(300);
	});
});