var Redirects = {
	url: 'remote.php?remoteSection=redirects&w=getRedirectsTable&page=1',
	urlDefault: 'remote.php?remoteSection=redirects&w=getRedirectsTable&page=1',
	addRedirectRow: '',
	newRedirectId: 0,
	_oldBlurTimeout: {},
	_blurTimeout: {},

	LoadTable: function () {
		$.getJSON(Redirects.url, function(json) {
			if(!json.success) {
				// no redirects
				Redirects.ShowNoRedirects();
				$('#RedirectsTable').hide().html(json.html);
				return;
			}

			Redirects.HideNoRedirects();

			$('#RedirectsTable').html(json.html);

			Redirects.BindLinkFields();

			$('.RedirectAutoURL_Link').each(function() {
				if($(this).find('a').html() == "") {
					$(this).parent().find('.RedirectAutoURL_Text').show();
					$(this).hide();
				} else {
					$(this).parent().find('.RedirectAutoURL_Text').hide();
					$(this).show();
				}
			});

			$(document).trigger('RedirectsTableLoaded');
		});
	},

	ResetURLToDefault: function () {
		Redirects.url = Redirects.urlDefault;
	},

	ShowNoRedirects: function () {
		$('#NoRedirects').show();
		$('#RedirectsTable').hide();
		$('#DeleteSelectedRedirects').attr('disabled', 'disabled');
		$('#ExportRedirects').attr('disabled', 'disabled');
	},

	HideNoRedirects: function () {
		$('#NoRedirects').hide();
		$('#RedirectsTable').show();
		$('#DeleteSelectedRedirects').removeAttr('disabled');
		$('#ExportRedirects').removeAttr('disabled');
	},

	saveOldURL: function (element) {
		if($(element).val() == '') {
			alert(lang.EnterAnOldUrl);
			$(element).focus();
			return;
		}
		$(element).removeClass("inPlaceFieldFocus");
		$('.SaveRedirectButton, .CancelRedirectButton', $(element).parent()).remove();
		if($(element).data('originalValue') != $(element).val()) {
			var newId = element.id.replace('oldUrl_', '');
			var data = {
				'newurl': $(element).val(),
				'id': newId
			};
			$.post('remote.php?remoteSection=redirects&w=saveRedirectURL', data, function (json) {
				if(!json.success) {
					alert(json.message);
					$(element).val($(element).data('originalValue'));
				} else {
					$(element).val(json.url);
					if(typeof json.tmpredirectid != 'undefined') {
						Redirects.updateRowId(json.tmpredirectid, json.id);
					}
				}
			}, 'json');
		}
	},

	updateRowId: function (oldId, newId) {
		$('#RedirectRow_' + oldId).attr('id', 'RedirectRow_' + newId);
		$('#RedirectCheckbox_' + oldId).attr('id', 'RedirectCheckbox_' + newId).val(newId);
		$('#oldUrl_' + oldId).attr('id', 'oldUrl_' + newId).attr('name', 'oldUrl[' + newId + ']');
		$('#RedirectType_' + oldId).attr('id', 'RedirectType_' + newId);
		$('#RedirectAutoURL_' + oldId).attr('id', 'RedirectAutoURL_' + newId);
		$('#RedirectAutoURL_Link_' + oldId).attr('id', 'RedirectAutoURL_Link_' + newId);
		$('#RedirectManualURL_' + oldId).attr('id', 'RedirectManualURL_' + newId);
		$('#linkerButton_' + oldId).attr('id', 'linkerButton_' + newId);
		$('#newUrl_' + oldId).attr('id', 'newUrl_' + newId).attr('name', 'newUrl[' + newId + ']');
		$('#RedirectActions_' + oldId).attr('id', 'RedirectActions_' + newId);
		$('#CopyLink_' + oldId).attr('id', 'CopyLink_' + newId);
		$('#DeleteLink_' + oldId).attr('id', 'DeleteLink_' + newId);
		$('#TestLink_' + oldId).attr('id', 'DeleteLink_' + newId).attr('href', config.ShopPath + $('#oldUrl_' + newId).val());

		$('#RedirectActions_' + newId).show();
	},

	saveNewURL: function (element) {
		$(element).removeClass("inPlaceFieldFocus");

		$('.SaveRedirectButton, .CancelRedirectButton', $(element).parent()).remove();

		if($(element).data('originalValue') != $(element).val()) {
			var newId = element.id.replace('newUrl_', '');
			var data = {
				'newurl': $(element).val(),
				'id': newId
			};
			$.post('remote.php?remoteSection=redirects&w=saveNewRedirectURL', data, function (json) {
				if(!json.success) {
					alert(json.message);
					$(element).val($(element).data('originalValue'));
				} else {
					$(element).val(json.url);
					if(typeof json.tmpredirectid != 'undefined') {
						Redirects.updateRowId(json.tmpredirectid, json.id);
					}
				}
			}, 'json');
		}
	},

	BindLinkFields: function (context) {
		if(typeof context == 'undefined') {
			var context = '#RedirectsTable';
		}

		var _saveOldUrlButtonClick = function () {
			var element = $(this).parent().find('.RedirectCurrentUrl');
			window.clearTimeout(Redirects._oldBlurTimeout[$(element).attr('id')]);
			Redirects.saveOldURL(element.get(0));
		};

		var _cancelOldUrlButtonClick = function (element) {
			var element = $(this).parent().find('.RedirectCurrentUrl');
			window.clearTimeout(Redirects._oldBlurTimeout[$(element).attr('id')]);
			var id = $(element).attr('id').replace('oldUrl_', '');

			if($(element).data('originalValue') == '' && $('#newUrl_' + id).val() == lang.ClickHereToEnterAURL) {
				$('#RedirectRow_' + id).remove();
				return;
			}

			$(element).val($(element).data('originalValue'));
			$(element).removeClass("inPlaceFieldFocus");
			$('.SaveRedirectButton, .CancelRedirectButton', $(element).parent()).remove();
		};

		var _saveNewUrlButtonClick = function () {
			var element = $(this).parent().find('.RedirectNewUrl');
			window.clearTimeout(Redirects._blurTimeout[$(element).attr('id')]);
			Redirects.saveNewURL(element.get(0));
		};

		var _cancelNewUrlButtonClick = function (element) {
			window.clearTimeout(Redirects._blurTimeout[this.id]);
			var element = $(this).parent().find('.RedirectNewUrl')
			$(element).val($(element).data('originalValue'));
			$(element).removeClass("inPlaceFieldFocus");
			$('.SaveRedirectButton, .CancelRedirectButton', $(element).parent()).remove();
		};

		var _setBlurTimeoutOld = function () {
			var id = '#' + this.id;
			Redirects._oldBlurTimeout[this.id] = window.setTimeout('Redirects.saveOldURL($("'+id+'").get(0))', 1000);
		};

		var _setBlurTimeoutNew = function () {
			var id = '#' + this.id;
			Redirects._blurTimeout[this.id] = window.setTimeout('Redirects.saveNewURL($("'+id+'").get(0))', 1000);
		};

		$('.RedirectNewUrl', $(context)).bind('blur', _setBlurTimeoutNew);
		$('.RedirectCurrentUrl', $(context)).bind('blur', _setBlurTimeoutOld);

		$('.RedirectCurrentUrl', $(context)).bind('focus',
			function () {
				window.clearTimeout(Redirects._oldBlurTimeout[this.id]);
				$(this).removeClass("inPlaceFieldHover");
				$(this).addClass("inPlaceFieldFocus");
				$(this).data('originalValue', $(this).val());
				if($(this).parent().find('.SaveRedirectButton, .CancelRedirectButton').length < 1) {
					$(this).after('<input type="button" value="Save" class="SaveRedirectButton" /> <input type="button" value="Cancel" class="CancelRedirectButton" />');
					$('.SaveRedirectButton', $(this).parent()).bind('click', _saveOldUrlButtonClick);
					$('.CancelRedirectButton', $(this).parent()).bind('click', _cancelOldUrlButtonClick);
				}
				if ($(this).val() == lang.ClickHereToEnterAURL) {
					$(this).val('');
				}
			}
		);

		$('.RedirectNewUrl', $(context)).bind('focus',
			function () {
				window.clearTimeout(Redirects._blurTimeout[this.id]);
				$(this).removeClass("inPlaceFieldHover");
				$(this).addClass("inPlaceFieldFocus");
				$(this).data('originalValue', $(this).val());
				if($(this).parent().find('.SaveRedirectButton, .CancelRedirectButton').length < 1) {
					$(this).after('<input type="button" value="Save" class="SaveRedirectButton" /> <input type="button" value="Cancel" class="CancelRedirectButton" />');
					$('.SaveRedirectButton', $(this).parent()).bind('click', _saveNewUrlButtonClick);
					$('.CancelRedirectButton', $(this).parent()).bind('click', _cancelNewUrlButtonClick);
				}
				if ($(this).val() == lang.ClickHereToEnterAURL) {
					$(this).val('');
				}
			}
		);

		$('.RedirectType', $(context)).bind('change', function () {
			var id = this.id.replace('RedirectType_', '');

			if($(this).val() == "auto") {
				$('#RedirectAutoURL_' +id).show();
				$('#RedirectManualURL_' +id).hide();
			} else {
				$('#RedirectAutoURL_' +id).hide();
				$('#RedirectManualURL_' +id).show();
			}
		});
	},

	AddNewRedirectRow: function () {

		if(Redirects.addRedirectRow == '') {
			$.getJSON('remote.php?remoteSection=redirects&w=getEmptyRow', function (json) {
				if(!json.success) {
					alert(json.message);
					return;
				}

				Redirects.addRedirectRow = json.html;
				Redirects.AddNewRedirectRow();
			});
		} else{
			var html = Redirects.addRedirectRow;
			html = html.replace(/\insertId/g, 'tmp' + Redirects.newRedirectId);
			$('#RedirectsTable .RedirectsHeadingRow').after(html);
			Redirects.BindLinkFields('#RedirectRow_tmp'+ Redirects.newRedirectId);
			$('#oldUrl_tmp'+ Redirects.newRedirectId).focus();
			Redirects.newRedirectId++;
			Redirects.HideNoRedirects();
		}

	},

	BulkImportModal: function () {
		$.iModal({
			type: 'ajax',
			url: 'remote.php?remoteSection=redirects&w=loadBulkForm',
			width: '400'
		});
	},

	UploadBulkFile: function () {
		if($('#BulkImportRedirectsFile').val() == '') {
			alert(lang.NoBulkImportFile);
			return;
		}

		$('#LoadingIndicator').show();

		$.ajaxFileUpload({
			url: 'remote.php?remoteSection=redirects&w=uploadbulkfile',
			secureuri: false,
			fileElementId: 'BulkImportRedirectsFile',
			dataType: 'json',
			success: function(json) {
				$('#LoadingIndicator').hide();
				$.iModal.close();

				if(json.success) {
					Redirects.LoadTable();
					Redirects.message(json.message, 'message');
				} else {
					Redirects.message(json.message, 'error');
				}
			},
			error: function (json) {
			//	Redirects.message(json.message, 'error');
			}
		});
	},

	CopyRedirect: function () {
		var id = this.id.replace("CopyLink_", '');
		var data = {'id': id};
		$.post('remote.php?remoteSection=redirects&w=copyRedirect', data, function (json) {
			if(!json.success) {
				Redirects.message(json.message, 'error');
			} else {
				$(document).bind('RedirectsTableLoaded', function () {
					$('#oldUrl_' + json.id).focus();
					$(document).unbind('RedirectsTableLoaded');
				});
				Redirects.ResetURLToDefault();
				Redirects.LoadTable();
			}
		}, 'json');
	},

	DeleteRedirect: function() {
		if(!confirm(lang.ConfirmDeleteRedirect)) {
			return false;
		}

		var id = this.id.replace("DeleteLink_", '');
		var data = {'id': id};

		$.post('remote.php?remoteSection=redirects&w=deleteRedirect', data, function (json) {
			if(!json.success) {
				Redirects.message(json.message, 'error');
			} else {
				Redirects.message(json.message, 'message');
				$("#RedirectRow_" + id).remove();
			}
		}, 'json');
	},

	DeleteSelected: function () {
		if($('.RedirectCheckbox:checked').length < 1) {
			alert(lang.SelectRedirectsToDelete);
			return;
		}

		if(!confirm(lang.ConfirmDeleteSelected)) {
			return false;
		}

		$.post('remote.php?remoteSection=redirects&w=deleteRedirects', $('.RedirectCheckbox:checked'), function (json) {
			if(!json.success) {
				Redirects.message(json.message, 'error');
			} else {
				Redirects.message(json.message, 'message');
				Redirects.LoadTable();
			}
		}, 'json');
	},

	ExportRedirects: function() {
		$("#frmRedirects").get(0).submit();
	},

	SaveRedirectType: function () {
		var id = this.id.replace('RedirectType_', '');
		if (id.substr(0, 3) == 'tmp') {
			return;
		}
		var data = {
			'type': $(this).val(),
			'redirectid': id
		};
		$.post('remote.php?remoteSection=redirects&w=saveRedirectType', data , function (json) {
			if(!json.success) {
				Redirects.message(json.message, 'error');
			}
		}, 'json');
	},

	CheckKey: function (e) {
		var code = (e.keyCode ? e.keyCode : e.which);
		if(code == 38 || code == 40) {
			$(this).trigger('change');
		}
	},

	message: function (text,type){
		if(type=='error'){
			display_error('TemplateMsgBox', text);
		} else {
			display_success('TemplateMsgBox', text);
		}
	},

	init: function () {
		$('#AddNewRedirectButton').bind('click', function () { Redirects.AddNewRedirectRow(); });
		$("#ExportRedirects").bind('click', function () { Redirects.ExportRedirects(); });
		$('#BulkImportRedirects').bind('click', function () { Redirects.BulkImportModal(); });
		$('.CopyLink').live('click', Redirects.CopyRedirect);
		$('.DeleteLink').live('click', Redirects.DeleteRedirect);

		$('.RedirectCurrentUrl, .RedirectNewUrl').live('mouseover',
			function () {
				if(!$(this).hasClass("inPlaceFieldFocus")) {
					$(this).addClass("inPlaceFieldHover");
				}
			}
		);

		$('.RedirectCurrentUrl, .RedirectNewUrl').live('mouseout',
			function () {
				$(this).removeClass("inPlaceFieldHover");
			}
		);

		$('#RedirectsMasterCheckbox').live('change', function () {
			if($(this).is(':checked')) {
				$('#RedirectsTable input:checkbox').attr('checked', 'checked');
			} else {
				$('#RedirectsTable input:checkbox').removeAttr('checked');
			}
		});

		$('.RedirectType').live('change', Redirects.SaveRedirectType);
		$('.RedirectType').live('keyup', Redirects.CheckKey);

		$('#DeleteSelectedRedirects').bind('click', Redirects.DeleteSelected);

		$('#RedirectsTable .PagingNav a').live('click', function () {
			Redirects.url = this.href;
			Redirects.LoadTable();
			return false;
		});

		$('#RedirectsTable a.SortLink').live('click', function () {
			Redirects.url = this.href;
			Redirects.LoadTable();
			return false;
		});

	}
};

$(document).ready(function () {
	Redirects.init();
});
