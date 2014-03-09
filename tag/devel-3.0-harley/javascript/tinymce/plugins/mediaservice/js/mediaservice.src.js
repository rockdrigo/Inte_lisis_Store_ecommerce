/**
* media service plugin dialog code
*/
;tinyMCEPopup.requireLangPack();

(function($){

	var path = tinyMCEPopup.getParam('shop_path');

	$(function(){
		window.setTimeout(function(){
			// give the dom time to refresh after page load (domready doesn't work here for some reason, selecting a node is fine but it's parentNode is null)

			// observe the media service url for changes
			$('#mediaservice_url').observe({
				type: 'constant',
				delay: 500,
				callback: onMediaServiceUrlChange
			});

			renderMediaServiceIntro();

			onMediaServiceUrlChange();
		}, 1);
	});

	var renderMediaServiceIntro = function () {
		var services = tinymce.plugins.MediaService.getServiceList();
		var service;

		var html = '<ul style="margin:0;padding:0;">';
		for (var i = 0; i < services.length; i++) {
			service = services[i];
			html += '<li style="margin:0 0 0 18px;padding:0;">' + $.htmlEncode(service.getDescription()) + '</li>';
		}
		html += '</ul>';

		$('#mediaservice_intro_servicelist').html(html);
	};

	var onMediaServiceUrlChange = function () {
		var url = $('#mediaservice_url').val();
		var service = tinymce.plugins.MediaService.getServiceByUrl(url);

		var embed = false;

		if (service) {
			service.setUrl(url);
			var embed = service.getEmbedHtml();
		}

		if (embed) {
			$('#mediaservice_preview').html(embed).show();
			$('#mediaservice_intro').hide();
		} else {
			$('#mediaservice_intro').show();
			$('#mediaservice_preview').hide();
		}
	};

})(jQuery);

tinyMCEPopup.onInit.add(function(){
	var ed = tinyMCEPopup.editor;

	var placeholder = ed.selection.getNode();

	if (placeholder !== null && /^mceItemMediaService_(.*?):(.*?)$/.test(placeholder.title)) {
		// the currently selected node in the editor is an existing placeholder to be modified
		var serviceName = RegExp.$1;
		var serializedData = RegExp.$2;
		var service = tinymce.plugins.MediaService.services[serviceName];
		service.unserializeData($.htmlDecode(serializedData));
		$('#mediaservice_url').val(service.getVideoUrl());
	}
});

function insertMediaService () {
	tinyMCEPopup.restoreSelection();
	var ed = tinyMCEPopup.editor;

	var url = $('#mediaservice_url').val();
	var service = tinymce.plugins.MediaService.getServiceByUrl(url);
	if (!service) {
		tinyMCEPopup.alert(ed.getLang('mediaservice_dlg.invalid_url'));
		return;
	}

	service.setUrl(url);

	var placeholder = ed.selection.getNode();

	if (placeholder !== null && /mceItemMediaService mceItemMediaService_/.test(ed.dom.getAttrib(placeholder, 'class'))) {
		// the currently selected node in the editor is an existing placeholder to be modified via the dom
		service.updatePlaceholderNode(placeholder);
		ed.execCommand('mceRepaint');

	} else {
		// a new embed should be inserted via html
		ed.execCommand('mceInsertContent', false, service.getPlaceholderHtml(tinyMCEPopup.getWindowArg("plugin_url")));
	}

	tinyMCEPopup.close();
};
