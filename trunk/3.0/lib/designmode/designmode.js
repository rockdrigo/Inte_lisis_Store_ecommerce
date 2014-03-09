var Cookie = {
	get: function(name)
	{
		name = name += "=";
		var cookie_start = document.cookie.indexOf(name);
		if(cookie_start > -1) {
			cookie_start = cookie_start+name.length;
			cookie_end = document.cookie.indexOf(';', cookie_start);
			if(cookie_end == -1) {
				cookie_end = document.cookie.length;
			}
			return unescape(document.cookie.substring(cookie_start, cookie_end));
		}
	},

	set: function(name, value, expires)
	{
		if(!expires) {
			expires = "; expires=Wed, 1 Jan 2020 00:00:00 GMT;"
		} else {
			expire = new Date();
			expire.setTime(expire.getTime()+(expires*1000));
			expires = "; expires="+expire.toGMTString();
		}
		document.cookie = name+"="+escape(value)+expires;
	},

	unset: function(name)
	{
		Cookie.set(name, '', -1);
	}
};

var DesignMode = {
	sortableContainers: '#LayoutColumn1, #LayoutColumn2, #LayoutColumn3, #LayoutColumn4',
	enabled: false,

	currentTemplate: '',
	activePanel: '',

	lastPanel: false,

	path: '',
	url: '',
	adminDir: 'admin',

	init: function()
	{
		DesignMode.setURLs();

		// Have we just made a layout change? If so the URL will contain #design_mode_done
		if(document.location.href.indexOf("#design_mode_done") > -1 )
		{
			alert(lang.DesignModeChangesSaved);
		}

		// Only turn design mode on once the entire page is loaded
		DesignMode.begin();
	},

	setURLs: function(){
		var scripts = document.getElementsByTagName('SCRIPT');
		for(var i = 0; i < scripts.length; i++) {
			s = scripts[i];
			if(s.src && s.src.indexOf('designmode.js') > -1) {
				if(!DesignMode.path) {
					DesignMode.path = s.src.replace(/designmode\.js$/, '')+"../designmode";
				}
				if(!DesignMode.url) {
					DesignMode.url = s.src.replace(/designmode\.js$/, '')+"../../" + DesignMode.adminDir + "/designmode.php";
				}
				if(!DesignMode.remoteUrl) {
					DesignMode.remoteUrl = s.src.replace(/designmode\.js$/, '')+"../../remote.php";
				}
				break;
			}
		}
	},

	begin: function()
	{
		$(DesignMode.sortableContainers).sortable({
			connectWith: DesignMode.sortableContainers,
			items: '.Moveable',
			cursor: 'move',
			cancel: '.Locked',
			placeholder: 'DropClass',
			forceHelperSize: true,
			forcePlaceholderSize: true,
			opacity: 0.8,
			tolerance: 'pointer',
			start: function(e, ui) {
				var siblingCount = 0;
				$(ui.item).parent().find('.Moveable').each(function() {
					if(this.offsetHeight > 0) {
						++siblingCount;
					}
				});
				DesignMode.lastPanel = false;
				if(siblingCount == 1) {
					DesignMode.lastPanel = true;
				}
				$('.LNGString', ui.item).removeClass('DesignModeLangOver');
				$('.LNGString', ui.item).data('currentlyEditing', true);
			},
			stop: function(e, ui) {
				if(DesignMode.lastPanel == true) {
					alert(lang.DesignModeRemoveLastPanel);
					return false;
				}
				setTimeout(function() {
					$('.LNGString', ui.item).data('currentlyEditing', false);
				}, 500);
			}
		});

		// Create toolbar
		DesignModeToolbar.init();

		// Initialise all dragable elements
		var disabled = Cookie.get('design_mode_disabled');
		if(!disabled) {
			DesignMode.enable();
		}
		else {
			DesignMode.disable();
		}

		// Initiailise language editing features
		DesignModeLangEdit.init();

		// Create context menu
		DesignModeMenu.init();
	},

	save: function()
	{
		if(!DesignMode.currentTemplate) {
			return;
		}

		sendPanels = '';
		$(DesignMode.sortableContainers).each(function(i, div) {
			sendPanels += $(div).attr('id') + ':';
			$('> .Panel', div).each(function(j, subdiv) {
				sendPanels += $(subdiv).attr('id') + ',';
			});
			sendPanels = sendPanels.replace(/,$/, '', sendPanels);
			sendPanels += '|';
		});

		sendPanels = sendPanels.replace(/\|$/, '', sendPanels);

		$('<form />')
			.attr('method', 'POST')
			.attr('action', DesignMode.url)
			.append(
				$('<input type="hidden" />')
					.attr('name', 'dm_url')
					.val(document.location.href)
			)
			.append(
				$('<input type="hidden" />')
					.attr('name', 'dm_template')
					.val(DesignMode.currentTemplate)
			)
			.append(
				$('<input type="hidden" />')
					.attr('name', 'dm_panels')
					.val(sendPanels)
			)
			.appendTo('body')
			.submit()
		;
	},

	close: function()
	{
		if(!confirm(lang.DesignModeConfirmDisable)) {
			return;
		}

		DesignMode.disable();
		$.ajax({
			url: DesignMode.remoteUrl,
			type: 'post',
			data: {
				w: 'DisableDesignMode'
			}
		});
		$('#design_mode_menu').remove();
	},

	cancel: function()
	{
		if(confirm(lang.DesignModeConfirmUndo))
		{
			var doc_url = document.location.href;
			doc_url = doc_url.replace("#design_mode_done", "");
			document.location.href = doc_url;
		}
	},

	confirmPanelRemove: function()
	{
		message = lang.DesignModeConfirmPanelRemove.replace(':panel', DesignMode.activePanel);
		if(confirm(message)) {
			$('.Panel#'+DesignMode.activePanel).remove();
		}
	},

	editPanel: function()
	{
		var l = (screen.availWidth/2) - 400;
		var t = (screen.availHeight/2) - 200;

		var panel_id = DesignMode.activePanel;
		DesignMode.openDesignModeEditor(DesignMode.url+"?ToDo=editFile&File=Panels/" + panel_id + ".html");
	},

	enable: function()
	{
		DesignMode.enabled = true;
		Cookie.unset('design_mode_disabled');
		$('.DesignModeToggleButton').addClass('DesignModeButtonEnabled');
		$('.Moveable').each(function() {
			this.title = 'This is the "'+this.id+'" panel.';
			this.style.cursor = 'move';
			$(this).mousedown(function(e) {
				if(e.button == 2 && !e.ctrlKey && DesignMode.enabled) {
					DesignMode.activePanel = this.id;
					e.preventDefault();
					e.stopPropagation();
					DesignModeMenu.show(true, e);
				}
			});
		});
		$(DesignMode.sortableContainers).sortable('enable');
	},

	disable: function()
	{
		// Already disabled?
		if(DesignMode.enabled == false) {
			return false;
		}

		$(DesignMode.sortableContainers).sortable('disable');

		DesignMode.enabled = false;

		Cookie.set('design_mode_disabled', 1);

		// Change the button to disabled
		$('.DesignModeToggleButton').removeClass('DesignModeButtonEnabled');

		$('.Moveable').each(function() {
			this.title = '';
			this.style.cursor = 'default';
		});
	},

	toggle: function()
	{
		if(DesignMode.enabled) {
			DesignMode.disable();
		}
		else {
			DesignMode.enable();
		}
	},

	editLayoutFile: function()
	{
		if(DesignMode.currentTemplate != "")
		{
			var l = (screen.availWidth/2) - 400;
			var t = (screen.availHeight/2) - 200;
			DesignMode.openDesignModeEditor(DesignMode.url+"?ToDo=editFile&File=" + DesignMode.currentTemplate);
		}
		else {
			alert("The layout of this page is not editable.");
		}
	},

	editStylesheetFile: function()
	{
		DesignMode.openDesignModeEditor(DesignMode.url+"?ToDo=editFile&File=Styles/styles.css");
	},

	openDesignModeEditor: function(url)
	{
		var dimensions = Cookie.get("design_mode_wh");
		if(dimensions) {
			dimensions = dimensions.split("x");
		}
		else {
			dimensions = [800, 400];
		}

		var l = (screen.availWidth/2) - dimensions[0]/2;
		var t = (screen.availHeight/2) - dimensions[1]/2;
		window.open(url, "", "width="+dimensions[0]+",height="+dimensions[1]+",top="+t+",left="+l+",resizable=yes");
	}
};

var DesignModeToolbar = {
	scrollTop: 0,

	init: function()
	{
		// Generate toolbar
		DesignModeToolbar.toolbar = document.createElement("DIV");
		DesignModeToolbar.toolbar.id = "design_mode_menu";
		DesignModeToolbar.toolbar.className = "DesignModeMenu";

		DesignModeToolbar.toolbar.innerHTML = '<div class="DesignModeMenuHeader" id="DesignModeHeader">' +
			'<a class="DesignModeCloseButton" href="#" onclick="DesignMode.close(); return false;">X</a>' +
			'Design Mode' +
			'</div>';

		DesignModeToolbar.toolbar.innerHTML += '<div class="DesignModeControls">' +
			'<div class="DesignModeButton DesignModeSaveButton"><a href="javascript:DesignMode.save()">'+lang.DesignModeSave+'</a></div>'+
			'<div class="DesignModeButton DesignModeUndoButton"><a href="javascript:DesignMode.cancel()">'+lang.DesignModeUndo+'</a></div>'+
			'<div class="DesignModeButton DesignModeToggleButton"><a href="javascript:DesignMode.toggle()">'+lang.DesignModeToggle+'</a></div>'+
			'<div class="DesignModeButton DesignModeDisableButton"><a href="javascript:DesignMode.close()">'+lang.DesignModeDisable+'</a></div>'+
			'</div>';

		// Do we have a custom set permission for the toolbar?
		var coordinates = Cookie.get('design_mode_toolbar');
		if(coordinates)	{
			coordinates = coordinates.split('x');
			if(!isNaN(parseInt(coordinates[0])) && coordinates[0] < $(window).width() && coordinates[0] > 0) {
				DesignModeToolbar.toolbar.style.left = coordinates[0] + 'px';
			}
			if(!isNaN(parseInt(coordinates[1])) && coordinates[1] < $(window).height() && coordinates[1] > 0) {
				DesignModeToolbar.toolbar.style.top = coordinates[1] + 'px';
			}
		}

		// Append the toolbar to the document
		document.body.appendChild(DesignModeToolbar.toolbar);

		// Add drag events
		$("#design_mode_menu").draggable({
			stop: function(event, ui) {
				DesignModeToolbar.scrollTop = $(this).offset().top - $('body').scrollTop();
				if(parseInt(this.style.left) < 1) {
					this.style.left = '1px';
				}
				Cookie.set('design_mode_toolbar', parseInt(this.style.left)+"x"+parseInt(this.style.top));
			}
		});

		$(window)
			.bind('scroll', function() {
				DesignModeToolbar.toolbar.style.top = ($('body').scrollTop() + DesignModeToolbar.scrollTop) + "px";
			})
			.bind('resize', function(event) {
				menuWidth = $('#design_mode_menu').width();
				if(menuWidth + $('#design_mode_menu').offset().left > $(window).width()) {
					newLeft = ($(window).width() - menuWidth - 50) + 'px';
					$('#design_mode_menu').css('left', newLeft);
				}
			})
		;

		DesignModeToolbar.scrollTop = $('#design_mode_menu').scrollTop() - $('body').scrollTop();
		$(window).trigger('resize');
	}
};

var DesignModeMenu = {

	visible: false,
	over: false,

	init: function()
	{
		// Generate the design mode context menu
		var menu = '<div id="design_mode_context_menu" class="DesignModeContextMenu" style="display: block; top: -9000px; left: -9000px;">';
		menu += '<ul style="text-align: left;">' +
			'<li id="menu_edit_panel" onclick="DesignMode.editPanel(); DesignModeMenu.hide();">'+lang.DesignModeMenuEditPanel+'</li>' +
			'<li id="menu_remove_panel" onclick="DesignMode.confirmPanelRemove(); DesignModeMenu.hide();">'+lang.DesignModeMenuRemovePanel+'</li>' +
			'<li id="menu_sep" style="margin:0px; padding:0px"><hr style="color:#ACA899; margin:6px 6px 3px 6px; width:90%" align="center" /></li>' +
			'<li onclick="DesignMode.editLayoutFile();  DesignModeMenu.hide();">'+lang.DesignModeMenuEditLayout+'</li>' +
			'<li onclick="DesignMode.editStylesheetFile(); DesignModeMenu.hide();">'+lang.DesignModeMenuEditStylesheet+'</li>' +
			'</ul>';
		menu += '</div>';

		$('body').append(menu);

		$('.DesignModeContextMenu li').hover(function() {
			$(this).addClass('DesignModeContextMenuHover');
			DesignModeMenu.over = true;
		}, function() {
			$(this).removeClass('DesignModeContextMenuHover');
			DesignModeMenu.over = false;
		})

		$(document).mousedown(DesignModeMenu.toggle);
		$(document).bind('contextmenu', function(event) {
			if(DesignMode.enabled == true && !event.ctrlKey) {
				return false;
			}
		});
	},

	toggle: function(event)
	{
		if(event.button != 2) {
			if(DesignModeMenu.visible && !DesignModeMenu.over) {
				DesignModeMenu.hide();
			}
		}
		else if(!event.ctrlKey)
		{
			DesignModeMenu.show(false, event);
			return false;
		}
	},

	show: function(overPanel, event)
	{
		if(!DesignMode.enabled) {
			return false;
		}
		$('.DesignModeContextMenu').show();
		x_pos = event.pageX;
		y_pos = event.pageY;

		// Fetches coordinates for menu
		$('.DesignModeContextMenu').css('left', x_pos + "px");
		$('.DesignModeContextMenu').css('top', y_pos + "px");

		DesignModeMenu.visible = true;

		if(overPanel) {
			$('#menu_edit_panel, #menu_remove_panel, #menu_sep').show();
		}
		else {
			$('#menu_edit_panel, #menu_remove_panel, #menu_sep').hide();
		}
	},

	hide: function()
	{
		$('.DesignModeContextMenu').hide();
	}
}

var DesignModeLangEdit = {
	current_item: '',

	init: function(parent)
	{
		$('span.LNGString')
			.attr('title', 'Click to Edit')
			.hover(function(event) {
				if(DesignMode.enabled == true && !$(this).data('currentlyEditing')) {
					$(this).addClass('DesignModeLangOver');
				}
			}, function(event) {
				if(DesignMode.enabled == true) {
					$(this).removeClass('DesignModeLangOver');
				}
			})
			.click(function(event) {
				DesignModeLangEdit.edit(this, event);
			})
		;
	},

	edit: function(element, event)
	{
		if(element != event.target || !DesignMode.enabled || !$(element).hasClass('LNGString') || $(element).data('currentlyEditing')) {
			return;
		}
		$(DesignMode.sortableContainers).sortable('disable');
		event.preventDefault();
		event.stopPropagation();
		$(element)
			.removeClass('DesignModeLangOver')
			.data('oldValue', $(element).html())
			.data('currentlyEditing', true)
		;
		var replacement = $('<span />')
			.addClass('DesignModeEditFields')
			.click(function() {
				return false;
			})
			.css({
				textDecoration: 'none',
				cursor: 'default'
			})
			.append(
				$('<input type="text" />')
					.addClass('DesignModeLangField')
					.keypress(function(e) {
						if(e.keyCode == 13) {
							DesignModeLangEdit.save(this);
							return false;
						}
						else if(e.keyCode == 27) {
							DesignModeLangEdit.cancel(this);
							return false;
						}
					})
					.val($(element).html())
					.css({
						fontSize: $(element).css('fontSize'),
						fontFamily: $(element).css('fontFamily'),
						width: $(element).width() + 25
					})
			)
			.append('<br />')
			.append(
				$('<input type="button" />')
					.val(lang.DesignModeSave)
					.addClass('DesignModeLangSave')
					.click(function() {
						DesignModeLangEdit.save(this)
						return false;
					})
			)
			.append(' '+lang.DesignModeOr+' ')
			.append(
				$('<input type="button" />')
					.val(lang.DesignModeCancel)
					.addClass('DesignModeLangCancel')
					.click(function() {
						DesignModeLangEdit.cancel(this)
						return false;
					})
			)
		;
		$(element).html(replacement);
		$('input:eq(0)', element)
			.focus()
			.select()
		;
	},

	save: function(element)
	{
		element = $(element).parents('.LNGString');
		input = $('input:eq(0)', element);
		$(DesignMode.sortableContainers).sortable('enable');
		if(!input.val()) {
			return false;
		}
		$(element)
			.addClass('DesignModeLangSaving')
			.html(lang.DesignModeLangSaving)
		;

		$.ajax({
			url: DesignMode.remoteUrl,
			type: 'POST',
			dataType: 'xml',
			data: {
				w: 'UpdateLanguage',
				LangName: element.attr('id'),
				NewValue: input.val()
			},
			success: function(xml) {
				if($('status', xml).text() == 1) {
					element.html($('newvalue', xml).text());
				}
				else {
					alert($('message', xml).text());
					document.location.href = document.location.href;
				}

				element
					.data('currentlyEditing', false)
					.removeClass('DesignModeLangSaving')
				;
			}
		});
	},

	cancel: function(element)
	{
		element = $(element).parents('.LNGString');
		$(element)
			.data('currentlyEditing', false)
			.html(element.data('oldValue'))
		;
		$(DesignMode.sortableContainers).sortable('enable');
	}
};

$(document).ready(function() {
	$("input[value*='LNGString']").each(function() {
		this.value = this.value.replace(/<span id='(.+)' class='LNGString'>(.+)<\/span>/, '$2');
	});

	$("submit").each(function() {
		this.value = this.value.replace(/<span id='(.+)' class='LNGString'>(.+)<\/span>/, '$2');
	});
	DesignMode.init();
});