jQuery(function($, undefined) {

	$.fn.extend({
		formAutocomplete: function(opts) {
			return this.each(function() {
				var list = $(this).hide(),
					inputs = list.find('input[name="'+opts.name+'"]'),
					input = $(opts.input).insertBefore(list),
					vals = [],
					curVals = [],
					curLn,
					working = false,
					terms = [],
					index,
					split = function(val) {
						return val.split(/,\s*/);
					},
					extractLast = function(term) {
						return split(term).pop();
					};

				inputs.each(function() {
					var me = $(this),
						val = me.val(),
						label = list.find('label[for="'+me.attr('id')+'"]').text();
					if (me.is(':checked')) {
						terms.push(label);
						curVals.push({
							value: val,
							label: label
						});
					} else {
						vals.push({
							value: val,
							label: label
						});
					}
				});
				terms.push('');
				input.val(terms.join(', '));

				input
					.on('formAutoComplete', function(e, submit) {
						if (!working) {
							working = true;
							terms = split(this.value);
							$.merge(vals, curVals);
							curVals = [];
							inputs.prop('checked', false);
							vals = $.map(vals, function(elt) {
								index = $.inArray(elt.label, terms);
								if (index > -1) {
									curVals.push(elt);
									inputs.filter('[value="'+elt.value+'"]').prop('checked', 'checked');
									terms.splice(index, 1);
									return null;
								}
								return elt;
							});
							if (submit && terms.length) {
								var add = '';
								$.each(terms, function() {
									add+= '<input type="hidden" name="'+opts.nameNew+'[]" value="'+this+'" />';
								});
								list.after(add);
							}
							working = false;
						}
					})
					.on('keydown', function() {
						curLn = this.value.length;
					})
					.on('keyup', function() {
						if (this.value.length < curLn)
							input.trigger('formAutoComplete');
					})
					.autocomplete({
						minLength: 0,
						source: function(request, response) {
							response($.ui.autocomplete.filter(vals, extractLast(request.term)));
						},
						focus: function() {
							// prevent value inserted on focus
							return false;
						},
						select: function(event, ui) {
							terms = split(this.value);
							// remove the current input
							terms.pop();
							// add the selected item
							terms.push(ui.item.label);
							terms.push('');
							this.value = terms.join(', ');
							input.trigger('formAutoComplete');
							return false;
						}
					})
					.closest('form').submit(function() {
						input.trigger('formAutoComplete', [true]);
					});
			});
		}
	});
	
});