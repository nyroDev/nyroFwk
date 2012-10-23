$(function() {
	var pluploadNb = 0;
	$.fn.extend({
		nyroPlupload: function(opts) {
			return this.each(function() {
				var me = $(this).hide(),
					myPluploadNb = pluploadNb++,
					curOpts = opts,
					cont = $('<div id="pluploadCont'+myPluploadNb+'" class="pluploadCont" />').insertAfter(me),
					curFiles = {},
					browse = $('<a href="#" id="pluploadBrowse'+myPluploadNb+'" class="pluploadBrowse">'+curOpts.texts.browse+'</a>').appendTo(cont),
					list = $('<div id="pluploadList'+myPluploadNb+'" class="pluploadList" />').appendTo(cont);
				
				curOpts.container = 'pluploadCont'+myPluploadNb;
				curOpts.drop_element = 'pluploadCont'+myPluploadNb;
				curOpts.browse_button = 'pluploadBrowse'+myPluploadNb;
				if (!curOpts.url)
					curOpts.url = me.closest('form').attr('action');
				var uploader = new plupload.Uploader(curOpts);
				uploader.bind('FilesAdded', function(up, files) {
					for (var i in files) {
						var curFile = files[i],
							name = curFile.name;
						if (name.length > 30)
							name = name.substr(0, 30) + '...'
						curFiles[curFile.id] = $('<div>'+name+' (' + plupload.formatSize(curFile.size) + ') - <strong>'+curOpts.texts.waiting+'</strong><div class="pluploadProgress"><div class="pluploadProgressBar"></div></div><a href="#" class="pluploadCancel" rel="'+curFile.id+'">Cancel</a></div>');
						curFiles[curFile.id].find('.pluploadCancel').on('click', function(e) {
							e.preventDefault();
							uploader.removeFile(uploader.getFile($(this).attr('rel')));
						});
						list.append(curFiles[curFile.id]);
					}
					setTimeout(function() {uploader.start();}, 1);
				});
				uploader.bind('UploadProgress', function(up, file) {
					if (curFiles[file.id]) {
						curFiles[file.id]
							.children('strong').text(file.percent+' %').end()
							.find('.pluploadProgressBar').css('width', file.percent+'%');
					}
				});
				uploader.bind('FileUploaded', function(up, file) {
					if (curFiles[file.id]) {
						curFiles[file.id].children('strong').text(curOpts.texts.complete);
						curFiles[file.id].delay(curOpts.hideDelay).fadeOut(function() {
							curFiles[file.id].remove();
							curFiles[file.id] = undefined;
							delete(curFiles[file.id]);
						})
					}
				});
				uploader.bind('FilesRemoved', function(up, files) {
					for (var i in files) {
						var file = files[i];
						if (curFiles[file.id]) {
							curFiles[file.id].children('strong').text(curOpts.texts.cancel);
							curFiles[file.id].delay(curOpts.hideDelay * 3).fadeOut(function() {
								curFiles[file.id].remove();
								curFiles[file.id] = undefined;
								delete(curFiles[file.id]);
							})
						}
					}
				});
				uploader.bind('Error', function(up, obj) {
					if (obj.file && curFiles[obj.file.id]) {
						curFiles[obj.file.id]
							.addClass('pluploadError')
							.children('strong').html(curOpts.texts.error+'<br />'+obj.message+(obj.status ? ' ('+obj.status+')' : ''));
					}
				});
				if (curOpts.onAllComplete && $.isFunction(curOpts.onAllComplete)) {
					uploader.bind('UploadComplete', function() {setTimeout(curOpts.onAllComplete, 20);});
				}
				uploader.init();
			});
		}
	});
});