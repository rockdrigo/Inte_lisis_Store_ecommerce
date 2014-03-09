/*
* NOTES:
* This was originally intended to encapsulate both swf- and non-swf-based uploading methods but time ran out to do this, so far it only provides non-swf-based uploading.
*
* REQUIRES:
* htmlEncode jquery plugin
* growingUploader jquery plugin
* ajaxFileUpload jquery plugin
*
* USAGE:
* new MultiUploadDialog([Object:options]); // will open an upload modal with a dynamically expanding list of file inputs
*
* OPTIONS:
* See MultiUploadDialog.defaults below
*/

MULTIUPLOADDIALOG_RESULT_SKIPPED = 1;
MULTIUPLOADDIALOG_RESULT_SUCCESS = 2;
MULTIUPLOADDIALOG_RESULT_ERROR = 3;

MultiUploadDialog = function (options) {
	var self = this;
	self.options = $.extend({}, MultiUploadDialog.defaults, options);

	var _resultList;
	var _inputList;
	var _inputCounter;

	var _resetUploads = function () {
		_resultList = [];
		_inputList = [];
		_inputCounter = 0;
	};

	var _findInputs = function () {
		_inputList = [];
		$('.MultiUploadDialogInput').each(function(){
			//	select all MultiUploadDialogInput and then filter based on parents() due to a bug in IE7 where #ModalContainer .MultiUploadDialogInput fails to find all dynamically cloned inputs
			if (!$(this).parents('#ModalContainer').length) {
				return;
			}

			if ($(this).val() && $(this).attr('id')) {
				// file input element must not be empty and must have an id
				_inputList.push(this);
			}
		});
	};

	var _startUploads = function () {
		_resetUploads();
		_findInputs();
		if (!_inputList.length) {
			alert(self.options.noinputsalerttext);
			return;
		}
		$(self).trigger('uploadsbegin');
		_disableButtons();
		_showProgress();
		_nextUpload();
	};

	var _nextUpload = function () {
		var input = _inputList[_inputCounter];
		var id = $(input).attr('id');

		_updateProgress();

		$(self).trigger('uploadbefore');

		$.ajaxFileUpload ({
			url: self.options.action,
			secureuri: false,
			fileElementId: id,
			dataType: 'json',
			error: _uploadError,
			success: _uploadSuccess
		});
	};

	var _uploadError = function (data, status, e) {
		$(self).trigger('uploaderror', [data, status, e]);
		_uploadComplete(data);
	};

	var _uploadSuccess = function (data) {
		$(self).trigger('uploadsuccess', [data]);
		_uploadComplete(data);
	};

	var _uploadComplete = function (data) {
		$(self).trigger('uploadcomplete', [data]);

		_inputCounter++;

		if (_inputCounter >= _inputList.length) {
			_finishUploads();
		} else {
			_nextUpload();
		}
	};

	var _finishUploads = function () {
		_updateProgress();
		_enableButtons();
		_closeDialog();
		$(self).trigger('uploadsfinished');
	};

	var _openDialog = function () {
		// template should provide ModalTitle and ModalContent elements
		var template = self.options.dialogtemplate;
		if (/^#\S+$/.test(template)) {
			template = $(template).html();
		}

		if (!template) {
			alert('MultiUploadDialog: template is null');
			return;
		}

		template = template
					.replace(/%titletext%/gi, $.htmlEncode(self.options.titletext))
					.replace(/%closetext%/gi, $.htmlEncode(self.options.closetext))
					.replace(/%introtext%/gi, $.htmlEncode(self.options.introtext))
					.replace(/%submittext%/gi, $.htmlEncode(self.options.submittext))
					.replace(/%cleartext%/gi, $.htmlEncode(self.options.cleartext));

		$.iModal({
			width: self.options.dialogwidth,
			title: self.options.titletext,
			data: template
		});

		$('#ModalContentContainer .GrowingUploader').growingUploader({
			maximum: self.options.limit,
			clearSelector: 'a'
		});

		$('#ModalContentContainer .Submit').click(_startUploads);
		$('#ModalContentContainer .CloseButton').click(_closeDialog);
	};

	var _closeDialog = function () {
		$.iModal.close();
	};

	var _disableButtons = function () {
		$('#ModalContentContainer .Submit, #ModalContentContainer .CloseButton').attr('disabled', true);
	};

	var _enableButtons = function () {
		$('#ModalContentContainer .Submit, #ModalContentContainer .CloseButton').attr('disabled', false);
	};

	var _showProgress = function () {
		var template = self.options.progresstemplate;
		if (/^#\S+$/.test(template)) {
			template = $(template).html();
		}

		var indicator = $('#ModalContentContainer .ProgressIndicator').show();
		if (template) {
			indicator.html(template);
		}

		$('#ModalContentContainer .UploadDialog').hide();
	};

	var _updateProgress = function () {
		var percent = _inputCounter / _inputList.length;

		var barWidth = $('#ModalContentContainer .ProgressBar').width();
		var colourWidth = Math.floor(barWidth * percent);

		$('#ModalContentContainer .ProgressBarColour').css('width', colourWidth + 'px');
		$('#ModalContentContainer .ProgressBarText').text(Math.floor(percent * 100) + '%');

		if (_inputCounter >= _inputList.length) {
			var messageText = 'Finished uploading.';
		} else {
			var filename = _inputList[_inputCounter].value;
			if (filename.indexOf('/') !== -1) {
				filename = filename.split('/');
				filename = filename[filename.length - 1];
			} else if (filename.indexOf('\\') !== -1) {
				filename = filename.split('\\');
				filename = filename[filename.length - 1];
			}

			var messageText = 'Now uploading file ' + (_inputCounter + 1) + ' of ' + _inputList.length + ': ' + filename;
		}

		$('#ModalContentContainer .ProgressMessage').text(messageText);
	};

	var _hideProgress = function () {
		$('#ModalContentContainer .UploadDialog').show();
		$('#ModalContentContainer .ProgressIndicator').hide();
	};

	// initialise
	_resetUploads();
	_openDialog();
};

MultiUploadDialog.defaults = {
	action: '', /* url to post uploads to, including GET query parameters if any */
	data: {}, /* additional data key:value pairs to send with post data of each upload */
	limit: 0, /* limit number of file inputs */
	dialogtemplate: '#MultiUploadDialogTemplate', /* template html to use for file input dialog - if this begins with # and has no spaces in it it will be treated as the id of an existing element to copy html from */
	dialogwidth: 500, /* width of the modal dialog */
	progresstemplate: '#MultiUploadProgressTemplate', /* template html to use for the progress indicator - if this begins with # and has no spaces in it it will be treated as the id of an existing element to copy html from */

	/* these options are valid only when using the standard dialog template, or a template that includes the placeholders */
	titletext: 'Upload Files', /* text to show as introduction */
	introtext: 'Select a file to upload below. You may upload multiple files but you must select one file at a time.', /* text to show above file dialogs as intro */
	submittext: 'Upload', /* text to show on submit button */
	closetext: 'Cancel', /* text to show on close button */
	cleartext: 'Remove', /* text to show on the clear buttons */
	noinputsalerttext: "Please choose an image first by clicking the 'Browse...' button.", /* text to show in alert() when upload button is pushed but no files selected */

	/* events triggered by the dialog - can be provided by options to bound to using jquery event binding */
	uploadsbegin: function(){}, /* upload batch about to start - use preventDefault to skip it */
	uploadbefore: function(){}, /* individual upload about to start - use preventDefault to skip it */
	uploadsuccess: function(){}, /* individual upload complete, triggered for both errors and successes */
	uploaderror: function(){}, /* individual upload HTTP or other transport error */
	uploadcomplete: function(){}, /* individual upload success (meaning the HTTP request was successful, the server script may still have provided errors to parse) */
	uploadsfinished: function(){} /* all uploads finished */
};
