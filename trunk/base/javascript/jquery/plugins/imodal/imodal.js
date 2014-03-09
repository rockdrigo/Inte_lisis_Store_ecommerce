/*
 * Interspire Modal 1.0
 * (c) 2008 Interspire Pty. Ltd.
 *
 * Based on SimpleModal 1.1.1 - jQuery Plugin
 * http://www.ericmmartin.com/projects/simplemodal/
 * http://plugins.jquery.com/project/SimpleModal
 * http://code.google.com/p/simplemodal/
 *
 * Copyright (c) 2007 Eric Martin - http://ericmmartin.com
 *
 * Dual licensed under the MIT (MIT-LICENSE.txt)
 * and GPL (GPL-LICENSE.txt) licenses.
 *
 * Revision: $Id$
 *
 */
(function ($) {
	$.iModal = function(options) {
		return $.iModal.modal.init(options);
	};

	$.modal = function() {
	};

	$.modal.close = function () {
		return $.iModal.modal.close(true);
	};

	$.iModal.close = function () {
		return $.iModal.modal.close(true);
	};

	$.fn.iModal = function (options) {
		options = $.extend({}, {
			type: 'inline',
			inline: $(this).html()
		}, options);
		return $.iModal.modal.init(options);
	};

	$.iModal.defaults = {
		overlay: 50,
		overlayCss: {},
		containerCss: {},
		close: true,
		closeTitle: 'Close',
		closeTxt: false,
		onOpen: null,
		onShow: null,
		onClose: null,
		onBeforeClose: null,
		onAjaxError: null,
		type: 'string',
		width: '630',
		buttons: '',
		title: '',
		method: 'get',
		top: '15%'
	};

	$.iModal.modal = {
		options: null,
		init: function(options) {
			// Can\'t have more than one modal window open at a time
			if($('#ModalContentContainer').length > 0) {
				return this;
			}
			this.options = $.extend({}, $.iModal.defaults, options);

			if(this.options.type == 'inline') {
				this.options.data = $(this.options.inline).html();
				$(this.options.inline).html('');
			}

			this.generateModal();
			return this;
		},

		checkHeight: function() {
			var winHeight = $(window).height();
			var modalHeight = $("#ModalContentContainer").height();

			if(modalHeight > winHeight * .85) {
				// modal height spans below the fold
				if ($("#ModalContainer").css('top') == '15%') {
					// use 15% of the remaining win height as top
					var top = (winHeight - modalHeight) * 0.15;
					if (top < 0) {
						top = 0;
					}

					// this stays until window height changes
					top += $(window).scrollTop();
					$("#ModalContainer").css({
						position: 'absolute',
						top: (top + 'px')
					});
				}
			}
			else {
				$("#ModalContainer").css({
					position: 'fixed',
					top: '15%'
				});
			}
		},

		ajaxError: function(xhr, status, error)
		{
			this.hideLoader();
			if ($.isFunction(this.options.onAjaxError)) {
				this.options.onAjaxError.apply(this, [xhr, status, error]);
			}
		},

		createFrame: function(container, html)
		{
	    	var frame = $('<iframe />').width('100%').attr('frameBorder', '0').appendTo(container)[0];

			// Grab the frame's document object
	    	var frameDoc = frame.contentWindow ? frame.contentWindow.document : frame.contentDocument;
	    	frameDoc.open(); frameDoc.write(html); frameDoc.close();

			// Auto adjust the iframe's height to the height of the content
			$(frameDoc).ready(function(){
				var height = frameDoc.body.scrollHeight + 20;

				$(frame).height(height);
			});
		},

		displayModal: function(data)
		{
			this.hideLoader();
			modalContent = '';
			if(!$.browser.msie || $.browser.version >= 7) {
				modalContent = '<div id="ModalTopLeftCorner"></div><div id="ModalTopBorder"></div><div id="ModalTopRightCorner"></div><div id="ModalLeftBorder"></div><div id="ModalRightBorder"></div><div id="ModalBottomLeftCorner"></div><div id="ModalBottomRightCorner"></div><div id="ModalBottomBorder"></div>';
			}
			if(data.indexOf('ModalTitle')>0 && data.indexOf('ModalContent')>0){
				modalContent += '<div id="ModalContentContainer">'+data+'</div>';
			}else{
				buttons = '';
				if(this.options.buttons) {
					buttons = '<div class="ModalButtonRow">'+this.options.buttons+'</div>';
				}
				modalContent += '<div id="ModalContentContainer"><div class="ModalTitle">'+this.options.title+'</div><div class="ModalContent">'+data+ '</div>'+buttons+'</div>';
			}

			cssProperties = {
				position: 'fixed',
				zIndex: 3100,
				width: this.options.width+'px'
			};

			if($.browser.msie && $.browser.version < 7) {
				cssProperties.position = 'absolute';
			}

			// If direction is rtl then we need to flip our margin positions
			if ($.browser.msie && $.browser.version <= 7 && $('body').css('direction') == 'rtl') {
				cssProperties.marginRight = (this.options.width/2)+'px';
			} else {
				cssProperties.marginLeft = '-'+(this.options.width/2)+'px';
			}

			cssProperties.top = this.options.top;

			$('<div>')
				.attr('id', 'ModalContainer')
				.addClass('modalContainer')
				.css(cssProperties)
				.hide()
				.appendTo('body')
				.html('<div class="modalData">'+modalContent+'</div>')
			;
			if($('#ModalContainer').find('.ModalButtonRow, #ModalButtonRow').length > 0) {
				$('#ModalContainer').addClass('ModalContentWithButtons');
			}
			if(this.options.close) {
				modal = this;
				$('<a/>')
					.addClass('modalClose')
					.attr('title', this.options.closeTitle)
					.appendTo('#ModalContainer')
					.click(function(e) {
						e.preventDefault();
						modal.close();
					})
				;
				$(document).bind('keypress', function(e) {
					if(e.keyCode == 27) {
						$('#ModalContainer .modalClose').click();
					}
				});

				if (this.options.closeTxt) {
					$('#ModalContainer .modalClose')
						.html(this.options.closeTitle)
						.css('backgroundPosition', '65px 0')
						.css('lineHeight', '15px')
						.css('textDecoration', 'none')
						.css('width', '60px')
						.css('paddingRight', '20px')
						.css('textAlign', 'right')
					;
					$('#ModalContainer .ModalTitle')
						.css('borderBottom', 'medium none')
						.css('backgroundColor', '#fff')
					;
					$('#ModalContainer #ModalTopBorder').css('backgroundImage', 'none');
				}
			}

			if($.isFunction(this.options.onOpen)) {
				this.options.onOpen.apply(this);
			}
			else {
				$('#ModalContainer').show();
				if($.isFunction(this.options.onShow)) {
					this.options.onShow.apply(this);
				}
			}

			// make sure we can see the bottom part of the modal
			// if the window size is too short
			$(window)
				.resize(this.checkHeight)
				.scroll(this.checkHeight)
			;
		},

		showLoader: function()
		{
			$('<div/>')
				.attr('id', 'ModalLoadingIndicator')
				.appendTo('body');
			;
		},

		showOverlayLoader: function(){
			$('<div/>')
				.attr('id', 'ModalOverlay')
				.addClass('modalOverlay')
				.css({
					opacity: 50 / 100,
					height: '100%',
					width: '100%',
					position: 'fixed',
					left: 0,
					top: 0,
					zIndex: 3000
				})
				.appendTo('body')
			;

			$('<div/>')
				.attr('id', 'ModalLoadingIndicator')
				.appendTo('body');
			;
		},

		hideOverlayLoader: function(){
			$('#ModalLoadingIndicator').remove();
			$('.modalOverlay').remove();
		},

		hideLoader: function()
		{
			$('#ModalLoadingIndicator').remove();
		},

		generateModal: function()
		{
			$('<div/>')
				.attr('id', 'ModalOverlay')
				.addClass('modalOverlay')
				.css({
					opacity: this.options.overlay / 100,
					height: '100%',
					width: '100%',
					position: 'fixed',
					left: 0,
					top: 0,
					zIndex: 3000
				})
				.appendTo('body')
			;

			if($.browser.msie && $.browser.version < 7) {
				wHeight = $(document.body).height()+'px';
				wWidth = $(document.body).width()+'px';
				$('#ModalOverlay').css({
					position: 'absolute',
					height: wHeight,
					width: wWidth
				});
				$('<iframe/>')
					.attr('src', 'javascript:false;')
					.attr('id', 'ModalTempiFrame')
					.css({opacity: 0, position: 'absolute', width: wWidth, height: wHeight, zIndex: 1000, top: 0, left: 0})
					.appendTo('body')
				;
			}

			this.showLoader();
			if(this.options.type == 'ajax') {
				modal = this;
				data = {};
				if(this.options.urlData != undefined) {
					data = this.options.urlData;
				}
				var method = 'get';
				if (this.options.method) {
					method = this.options.method;
				}
				$.ajax({
					url: this.options.url,
					type: method,
					success: function(data) {
						modal.displayModal(data);
					},
					error: function(xhr, status, error) {
						modal.ajaxError(xhr, status, error);
					},
					data: data,
					type: this.options.method,
					global: false
				});
			}
			else if(this.options.type == 'iframe'){
				modal = this;
				data = {};
				if(this.options.urlData != undefined) {
					data = this.options.urlData;
				}
				var method = 'get';
				if (this.options.method) {
					method = this.options.method;
				}
				$.ajax({
					url: this.options.url,
					type: method,
					success: function(data) {
						modal.displayModal('');
						var f = modal.createFrame($('#ModalContentContainer .ModalContent'), data);
					},
					error: function(xhr, status, error) {
						modal.ajaxError(xhr, status, error);
					},
					data: data,
					type: this.options.method,
					global: false
				});
			}
			else {
				this.displayModal(this.options.data);
			}
		},

		close: function(external)
		{
			if (!this.options) {
				return;
			}

			if($.isFunction(this.options.onBeforeClose)) {
				this.options.onBeforeClose.apply(this, []);
			}

			if(this.options.type == 'inline') {
				$(this.options.inline).html(this.options.data);
			}

			if($.isFunction(this.options.onClose) && !external) {
				this.options.onClose.apply(this);
			}
			else {
				$('#ModalContainer').remove();
			}

			$('#ModalLoadingIndicator').remove();
			$('#ModalOverlay').remove();
			$('#ModalTempiFrame').remove();
		}
	};
})(jQuery);


function ModalBox(title, content){
	var str = '<div class="ModalTitle">'+title+'</div><div class="ModalContent">'+content+ '</div><div class="ModalButtonRow"></div>';
	$.iModal({ data: str });
}

function ModalBoxInline(title, content, width, withCloseButton){
	if(typeof(width) == 'undefined'){
		var width = 800;
	}
	if(typeof(withCloseButton) == 'undefined'){
		var withCloseButton = false;
	}
	if(withCloseButton){
		var str = '<div class="ModalTitle">'+title+'</div><div class="ModalContent">'+$(content).html()+ '</div><div class="ModalButtonRow"></div>';
	}else{
		var str = '<div class="ModalTitle">'+title+'</div><div class="ModalContent">'+$(content).html()+ '</div><div class="ModalButtonRow"><input type="button" class="CloseButton FormButton" value="Close Window" onclick="$.iModal.close();" /></div>';
	}
	$.iModal({ 'data': str, 'width':width });
}
