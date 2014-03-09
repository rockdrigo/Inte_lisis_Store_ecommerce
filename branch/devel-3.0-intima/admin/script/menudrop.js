$(document).ready(function() {
	$('#Menu ul > li > a').dblclick(function(e)
	{
		e.stopPropagation();
		window.location = this.href;
		return false;
	});

	$('#Menu > ul > li > a').click(function(e)
	{
		var elem = this;
		if($(elem).parent().is('.Open')) {
			$(elem.parentNode).removeClass('Open');
			$(elem).parent().find('ul').css('display', 'none');
			$('embed').css('visibility', 'visible');
			return false;
		}


		if(document.topCurrentMenu) {
			$(document.topCurrentMenu).hide();
			$(document.topCurrentButton).removeClass('ActiveButton');
			$('.ControlPanelSearchBar').show();
		}

		if(document.currentMenu) {
			$(document.currentMenu.parentNode).removeClass('Open');
			$(document.currentMenu).parent().find('ul').css('display', 'none');
			$('embed').css('visibility', 'visible');
			if(document.currentMenu.parentNode.id == this.parentNode.id) {
				document.currentMenu = null;
				return false;
			}
		}
		document.currentMenu = this;

		offsetTop = offsetLeft = 0;
		var element = elem;
		do
		{
			offsetTop += element.offsetTop || 0;
			offsetLeft += element.offsetLeft || 0;
			element = element.offsetParent;
		} while(element && $(element).css('position') != 'relative');


		$(elem).parent().find('ul').css('visibility', 'hidden');
		if(navigator.userAgent.indexOf('MSIE') != -1) {
			$(elem).parent().find('ul').css('display', 'block');
		}
		else {
			$(elem).parent().find('ul').css('display', 'table');
		}

		var menuWidth = elem.parentNode.getElementsByTagName('ul')[0].offsetWidth;
		$(elem).parent().find('ul').css('width', menuWidth-2+'px');
		if(offsetLeft + menuWidth > $(window).width()) {
			$(elem).parent().find('ul').css('position', 'absolute');
			$(elem).parent().find('ul').css('left',  (offsetLeft-menuWidth+elem.offsetWidth-3)+'px');
		}
		else if(offsetLeft - menuWidth < $(window).width()) {
			$(elem).parent().find('ul').css('position', 'absolute');
			$(elem).parent().find('ul').css('left',  offsetLeft+'px');
		}
		$('embed').css('visibility', 'hidden');
		$('object').css('visibility', 'hidden');
		$(elem).parent().find('ul').css('visibility', 'visible');
		$(elem).parent().addClass('over');
		$(elem).blur(function(event) {
			if(elem.parentNode.overmenu != true) {
				$(elem.parentNode).removeClass('over');
				$(elem).parent().find('ul').css('display', 'none');
				$('embed').css('visibility', 'visible');
				$('object').css('visibility', 'visible');
			}
		});

		$(document).click(function(event) {
			if(elem.parentNode.overmenu != true) {
				$(elem.parentNode).removeClass('over');
				$(elem).parent().find('ul').css('display', 'none');
				$('embed').css('visibility', 'visible');
				$('object').css('visibility', 'visible');
			}
		});
		return false;
	});
	$('#Menu ul li ul li').mouseover(function() {
		this.parentNode.parentNode.overmenu = true;
		this.onmouseout = function(e) { this.parentNode.parentNode.overmenu = false;}
	});
	$('#Menu ul li ul li').click(function() {
		$(this.parentNode).hide();
		$(this.parentNode.parentNode).removeClass('Open');
	});

	$('.DropShadow').each(function() {
		var offsetHeight = this.offsetHeight;
		var offsetWidth = this.offsetWidth;
		if(offsetHeight == 0) {
			var clone = this.cloneNode(true);
			clone.style.position = 'absolute';
			clone.style.left = '-10000px';
			clone.style.top = '-10000px';
			clone.style.display = 'block';
			document.body.appendChild(clone);
			offsetHeight = clone.offsetHeight;
			offsetWidth = clone.offsetWidth;
			document.body.removeChild(clone);
		}

		$(this).wrap('<div class="DropShadowContainer"><div class="Shadow1"><div class="Shadow2"><div class="Shadow3"><div class="ItemContainer"></div></div></div></div></div>');
		var container = this.parentNode.parentNode.parentNode.parentNode.parentNode;

		$(container).css('height', offsetHeight+"px");
		$(container).css('position', this.style.position);
		$(container).css('top', this.style.top);
		$(container).css('left', this.style.left);
		$(container).css('display', this.style.display);
		$(container).attr('id', this.id);
		$(this).css('position', 'static');
		$(this).css('display', '');
		$(this).removeClass('DropShadow');
		this.id = '';
	});

	$('.PopDownMenu').each(function() {
		$(this).click(function(e) {
			closeMenu();
			if(document.topCurrentMenu) {

				$(document.topCurrentMenu).hide();
				$(document.topCurrentButton).removeClass('ActiveButton');
				$('.ControlPanelSearchBar').show();
			}
			$('.ControlPanelSearchBar').hide();

			var id = this.id.replace(/^MenuButton/, '');

			var menu = $('#Menu'+id);

			var obj = this;
			offsetTop = 0;
			offsetLeft = 0;
			while(obj)
			{
				offsetLeft += obj.offsetLeft;
				offsetTop += obj.offsetTop;
				obj = obj.offsetParent;
				if(obj && CurrentStyle(obj, 'position')) {
					var pos = CurrentStyle(obj, 'position');
					if(pos == "absolute" || pos == "relateive") {
						break;
					}
				}
			}

			$(this).addClass('ActiveButton');
			$('embed').css('visibility', 'hidden');
			$('object').css('visibility', 'hidden');
			$(menu).css('position', 'absolute');
			$(menu).addClass('PopDownMenuContainer');
			$(menu).css('top', offsetTop+this.offsetHeight+1+"px");
			this.blur();
			$(menu).css('left', offsetLeft+2 + "px");
			$(menu).show();
			e.stopPropagation();
			$(document).click(function() {
				$(menu).hide(); $(document.topCurrentButton).removeClass('ActiveButton');
				document.topCurrentMenu = '';
				$('.ControlPanelSearchBar').show();
				$('embed').css('visibility', 'visible');
				$('object').css('visibility', 'visible');
			});
			document.topCurrentMenu = menu;
			document.topCurrentButton = this;
			return false;
		});
	});

	$('#TextLinks ul li.dropdown > a').dblclick(function(e)
	{
		e.stopPropagation();
		window.location = this.href;
		return false;
	});

	$('#TextLinks ul li.dropdown > a').click(function(e)
	{
		var elem = this;
		if($(elem).parent().is('.over')) {
			$(elem.parentNode).removeClass('over');
			$(elem).parent().find('ul').css('display', 'none');
			$('embed').css('visibility', 'visible');
			return false;
		}

		if(document.topCurrentMenu) {
			$(document.topCurrentMenu).hide();
			$(document.topCurrentButton).removeClass('ActiveButton');
			$('.ControlPanelSearchBar').show();
		}

		if(document.currentMenu) {
			$(document.currentMenu.parentNode).removeClass('over');
			$(document.currentMenu).parent().find('ul').css('display', 'none');
			$('embed').css('visibility', 'visible');
		}
		document.currentMenu = this;

		offsetTop = offsetLeft = 0;
		var element = elem;
		do
		{
			offsetTop += element.offsetTop || 0;
			offsetLeft += element.offsetLeft || 0;
			element = element.offsetParent;
		} while(element && $(element).css('position') != 'relative');


		$(elem).parent().find('ul').css('visibility', 'hidden');
		if(navigator.userAgent.indexOf('MSIE') != -1) {
			$(elem).parent().find('ul').css('display', 'block');
		}
		else {
			$(elem).parent().find('ul').css('display', 'table');
		}
		var menuWidth = elem.parentNode.getElementsByTagName('ul')[0].offsetWidth;
		$(elem).parent().find('ul').css('width', menuWidth-2+'px');
		if(offsetLeft + menuWidth > $(window).width()) {
			$(elem).parent().find('ul').css('position', 'absolute');
			$(elem).parent().find('ul').css('left',  (offsetLeft-menuWidth+elem.offsetWidth-3)+'px');
		}
		else if(offsetLeft - menuWidth < $(window).width()) {
			$(elem).parent().find('ul').css('position', 'absolute');
			$(elem).parent().find('ul').css('left',  offsetLeft+'px');
		}
		$('embed').css('visibility', 'hidden');
		$('object').css('visibility', 'hidden');
		$(elem).parent().find('ul').css('visibility', 'visible');
		$(elem).parent().addClass('over');
		$(elem).blur(function(event) {
			if(elem.parentNode.overmenu != true) {
				$(elem.parentNode).removeClass('over');
				$(elem).parent().find('ul').css('display', 'none');
				$('embed').css('visibility', 'visible');
				$('object').css('visibility', 'visible');
			}
		});
		$(document).click(function(event) {
			if(elem.parentNode.overmenu != true) {
				$(elem.parentNode).removeClass('over');
				$(elem).parent().find('ul').css('display', 'none');
				$('embed').css('visibility', 'visible');
				$('object').css('visibility', 'visible');
			}
		});
		return false;
	});
	$('#TextLinks ul li ul li').mouseover(function() {
		this.parentNode.parentNode.overmenu = true;
		this.onmouseout = function(e) { this.parentNode.parentNode.overmenu = false;}
	});
	$('#TextLinks ul li ul li').click(function() {
		$(this.parentNode).hide();
		this.parentNode.parentNode.className = 'dropdown';
	});

	$('.GeneralTable:not(.NoHoverStyles) tbody tr').hover(function() {
		$(this).addClass('Over');
	}, function() {
		$(this).removeClass('Over');
	});

	$('#SearchBar select[name=searchType]').change(function() {
		formAction = $(this).val()+'.php';
		$('#HeaderSearchForm').attr('action', formAction);
	});

	$('.CancelButton').click(function() {
		if (confirm('Are you sure you want to cancel? Click OK to confirm.')) {
			window.location = window.location.toString().replace(/\?.*$/, '');
		}
	})
});

function closeMenu() {
	if(document.currentMenu) {
		$(document.currentMenu.parentNode).removeClass('Open');
		$(document.currentMenu).parent().find('ul').css('display', 'none');
		$('embed').css('visibility', 'visible');
		$('object').css('visibility', 'visible');
	}
}

function toggleCheckoutExtraFields() {
	var setDisplay;
	
	if (document.getElementById('CheckoutUseExtraFields').checked == true) setDisplay = "";
	else setDisplay = "none";
	
	document.getElementById('CheckoutExtraFieldRow1').style.display = setDisplay;
	document.getElementById('CheckoutExtraFieldRow2').style.display = setDisplay;
	document.getElementById('CheckoutExtraFieldRow3').style.display = setDisplay;
	document.getElementById('CheckoutExtraFieldRow4').style.display = setDisplay;
	document.getElementById('CheckoutExtraFieldRow5').style.display = setDisplay;
}

function toggleCheckoutExtraFieldActive(fieldNumber) {
	if(document.getElementById('CheckoutExtraFieldActive'+fieldNumber).checked == true) document.getElementById('DivCheckoutExtraFieldActive'+fieldNumber).style.display = "";
	else if (document.getElementById('CheckoutExtraFieldActive'+fieldNumber).checked == false) document.getElementById('DivCheckoutExtraFieldActive'+fieldNumber).style.display = "none";
}

function CurrentStyle(element, prop) {
	if(element.currentStyle) {
		return element.currentStyle[prop];
	}
	else if(document.defaultView && document.defaultView.getComputedStyle) {
		prop = prop.replace(/([A-Z])/g,"-$1");
		prop = prop.toLowerCase();
		return document.defaultView.getComputedStyle(element, "").getPropertyValue(prop);
	}
}
var LoadingIndicator = {
	Show: function(options) {
		if(options == undefined) {
			options = {};
		}

		if(options.background == undefined) {
			options.background = '#000';
		}

		if(options.parent == undefined) {
			options.parent = $('body');
		}
		else {
			options.parent = $(options.parent)
		}

		if($(options.parent).get(0).tagName == 'BODY') {
			var overlayCss = {
				height: '100%',
				width: '100%',
				position: 'fixed',
				top: 0,
				left: 0
			};
		}
		else {
			var overlayCss = {
				height: $(options.parent).height(),
				width: $(options.parent).width(),
				position: 'absolute',
				top: $(options.parent).offset().top,
				left: $(options.parent).offset().left
			};
		}
		overlayCss = $.extend(overlayCss, {
			backgroundColor: options.background,
			opacity: 0.6,
			zIndex: 3000
		});

		// Create the indicator
		overlay = $('<div>')
			.attr('class', 'LoadingOverlay')
			.css(overlayCss)
			.appendTo('body')
		;

		indicator = $('<div>')
			.attr('class', 'LoadingIndicator')
			.css({width: '100px', height: '100px', marginLeft: '-50px', marginTop: '-50px', backgroundImage: "url('images/loadingBig.gif')", backgroundPosition: 'center', backgroundRepeat: 'no-repeat', position: 'absolute', top: '50%', left: '50%', zIndex: 300001})
			.appendTo(overlay)
		;

		if($.browser.msie && $.browser.version == '6.0') {
			wHeight = $(options.parent).height()+'px';
			wWidth = $(options.parent).width()+'px';
			$(overlay).css({
				position: 'absolute',
				height: wHeight,
				width: wWidth
			});
			$(indicator).css({position: 'absolute'});
		}

		return overlay;
	},

	Destroy: function(overlay)
	{
		$(overlay).remove();
	}
};