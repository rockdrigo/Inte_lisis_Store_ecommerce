jQuery.extend({


	createUploadIframe: function(id, uri)
	{
			//create frame
			var frameId = 'jUploadFrame' + id;

			if(window.ActiveXObject) {
				var io = document.createElement('<iframe id="' + frameId + '" name="' + frameId + '" />');
				if(typeof uri== 'boolean'){
					io.src = 'javascript:false';
				}
				else if(typeof uri== 'string'){
					io.src = uri;
				}
			}
			else {
				var io = document.createElement('iframe');
				io.id = frameId;
				io.name = frameId;
			}
			io.style.position = 'absolute';
			io.style.top = '-1000px';
			io.style.left = '-1000px';

			document.body.appendChild(io);

			return io
	},
	createUploadForm: function(id, fileElementId)
	{
		//create form
		var formId = 'jUploadForm' + id;
		var fileId = 'jUploadFile' + id;
		var form = $('<form  action="" method="POST" name="' + formId + '" id="' + formId + '" enctype="multipart/form-data"></form>');
		var oldElement = $('#' + fileElementId);
		var newElement = $(oldElement).clone();
		$(oldElement).attr('id', fileId);
		$(oldElement).before(newElement);
		$(oldElement).appendTo(form);
		//set attributes
		$(form).css('position', 'absolute');
		$(form).css('top', '-1200px');
		$(form).css('left', '-1200px');
		$(form).appendTo('body');
		return form;
	},

	ajaxFileUpload: function(s) {
		// TODO introduce global settings, allowing the client to modify them for all requests, not only timeout
		s = jQuery.extend({}, jQuery.ajaxSettings, s);
		var id = new Date().getTime()
		var form = jQuery.createUploadForm(id, s.fileElementId);
		var io = jQuery.createUploadIframe(id, s.secureuri);
		var frameId = 'jUploadFrame' + id;
		var formId = 'jUploadForm' + id;

		var requestDone = false;

		var xhr = { // mock object
			responseText: null,
			responseXML: null,
			status: 0,
			statusText: 'n/a',
			getAllResponseHeaders: function() {},
			getResponseHeader: function() {},
			setRequestHeader: function() {}
		};

		var g = s.global;
		// trigger ajax global events so that activity/block indicators work like normal
		if (g && ! $.active++) $.event.trigger("ajaxStart");
		if (g) $.event.trigger("ajaxSend", [xhr, s]);

		var cbInvoked = 0;
		var operaHack = 0;
		var timedOut = 0;

		// Wait for a response to come back
		var uploadCallback = function(isTimeout)
		{
			var io = document.getElementById(frameId);

			var ok = true;
			try {
				if (timedOut) throw 'timeout';
				// extract the server response from the iframe
				var data, doc;

				doc = io.contentWindow ? io.contentWindow.document : io.contentDocument ? io.contentDocument : io.document;

				if (doc.body == null && !operaHack && $.browser.opera) {
					// In Opera 9.2.x the iframe DOM is not always traversable when
					// the onload callback fires so we give Opera 100ms to right itself
					operaHack = 1;
					cbInvoked--;
					setTimeout(uploadCallback, 100);
					return;
				}

				xhr.responseText = doc.body ? doc.body.innerHTML : null;
				xhr.responseXML = doc.XMLDocument ? doc.XMLDocument : doc;
				xhr.getResponseHeader = function(header){
					var headers = {'content-type': s.dataType};
					return headers[header];
				};

				if (s.dataType == 'json' || s.dataType == 'script') {
					var ta = doc.getElementsByTagName('textarea')[0];
					xhr.responseText = ta ? ta.value : xhr.responseText;
				}
				else if (s.dataType == 'xml' && !xhr.responseXML && xhr.responseText != null) {
					xhr.responseXML = toXml(xhr.responseText);
				}

				data = $.httpData(xhr, s.dataType);
			}
			catch(e){
				ok = false;
				$.handleError(s, xhr, 'error', e);
			}

			 // ordering of these callbacks/triggers is odd, but that's how $.ajax does it
			if (ok) {
				s.success(data, 'success');
				if (g) $.event.trigger("ajaxSuccess", [xhr, s]);
			}
			if (g) $.event.trigger("ajaxComplete", [xhr, s]);
			if (g && ! --$.active) $.event.trigger("ajaxStop");
			if (s.complete) s.complete(xhr, ok ? 'success' : 'error');

			// clean up
			setTimeout(function() {
				$(io).remove();
				xhr.responseXML = null;
			}, 100);
		}
		// Timeout checker
		if ( s.timeout > 0 )
		{
			setTimeout(function(){
				// Check to see if the request is still happening
				if( !requestDone ) uploadCallback( "timeout" );
			}, s.timeout);
		}
		try
		{
		   // var io = $('#' + frameId);
			var form = $('#' + formId);
			$(form).attr('action', s.url);
			$(form).attr('method', 'POST');
			$(form).attr('target', frameId);
			if(form.encoding)
			{
				form.encoding = 'multipart/form-data';
			}
			else
			{
				form.enctype = 'multipart/form-data';
			}
			$(form).submit();

		} catch(e)
		{
			jQuery.handleError(s, xhr, null, e);
		}
		if(window.attachEvent){
			document.getElementById(frameId).attachEvent('onload', uploadCallback);
		}
		else{
			document.getElementById(frameId).addEventListener('load', uploadCallback, false);
		}
		return {abort: function () {}};

	}
})