function popitup(url) {
	newwindow=window.open(url,'win_config_variation','height=300,width=700');
	if (window.focus) {newwindow.focus();}
	newwindow.onunload = window.location.reload( true );
}

function ShowQuickHelp(container, title, desc)
{
	div = document.createElement("div");
	div.id = 'help';
	div.style.zIndex = 1;
	div.style.display = 'block';
	div.style.position = 'absolute';
	div.style.width = '185px';
	div.style.backgroundColor = '#FEFCD5';
	div.style.border = 'solid 1px #E7E3BE';
	div.style.padding = '10px';
	if(title != '') {
		div.innerHTML = '<div class="helpTip"><strong>' + title + '</strong></div><br />';
	}
	div.innerHTML += '<div style="width:185px; padding-left:0px" class=helpTip>' + desc + '</div>';

	SetQuickHelpPosition(div, container, 185)

	container.parentNode.appendChild(div);
}

function SetQuickHelpPosition(d, container, width)
{
	var containerX = 0;
	var containerY = 0;
	var containerTemp = container;
	while( containerTemp != null ) {
		containerX += containerTemp.offsetLeft;
		containerY += containerTemp.offsetTop;
		containerTemp = containerTemp.offsetParent;
	}
	var scrollXY = getScrollXY();
	var windowRight = document.documentElement.clientWidth;
	var divX = windowRight-width;
	var divY = containerY+15;
	if (divX<=containerX-scrollXY[0]) {
		d.style.left= divX+'px';
	}
	else if(width+containerX+50 > $(window).width()) {
		d.style.left = (divX-width-50)+'px';
	}
	d.style.top = divY+'px';
}

function getScrollXY()
{
	var scrOfX = 0, scrOfY = 0;
	if( typeof( window.pageYOffset ) == 'number' ) {
		//Netscape compliant
		scrOfY = window.pageYOffset;
		scrOfX = window.pageXOffset;
	} else if( document.body && ( document.body.scrollLeft || document.body.scrollTop ) ) {
		//DOM compliant
		scrOfY = document.body.scrollTop;
		scrOfX = document.body.scrollLeft;
	} else if( document.documentElement && ( document.documentElement.scrollLeft || document.documentElement.scrollTop ) ) {
		//IE6 standards compliant mode
		scrOfY = document.documentElement.scrollTop;
		scrOfX = document.documentElement.scrollLeft;
	}
	return [ scrOfX, scrOfY ];
}

function HideQuickHelp(p)
{
	if ($("#help").length) {
		$("#help").css('display','none');
		setTimeout('RemoveHelp()', 1);
	}
}

function RemoveHelp() {
	$("#help").remove();
}


function ShowHelp(img, title, desc)
{
	img = document.getElementById(img);
	div = document.createElement('div');
	div.id = 'help';

	div.style.display = 'inline';
	div.style.position = 'absolute';
	div.style.width = '350';

	div.style.backgroundColor = '#FEFCD5';
	div.style.border = 'solid 1px #E7E3BE';
	div.style.padding = '10px';
	div.innerHTML = '<span class=helpTip><strong>' + title + '<\/strong><\/span><br /><img src=images/1x1.gif width=1 height=5><br /><div style="padding-left:10; padding-right:5" class=helpTip>' + desc + '<\/div>';

	//img.parentNode.appendChild(div);
	var parent = img.parentNode;
	if(img.nextSibling)
		parent.insertBefore(div, img.nextSibling);
	else
		parent.appendChild(div)
}

function HideHelp(img)
{
	if ($("#help").length) {
		$("#help").css('display','none');
		setTimeout('RemoveHelp()', 1);
	}
}

function SetCookie(cookieName,cookieValue,nDays)
{
	var today = new Date();
	var expire = new Date();

	if(nDays==null || nDays==0)
		nDays = 1;

	expire.setTime(today.getTime() + 3600000*24*nDays);
	document.cookie = cookieName+"="+escape(cookieValue) + ";expires="+expire.toGMTString();
}

function ReadCookie(n) {
var cookiecontent = new String();
if(document.cookie.length > 0) {
	var cookiename = n+ '=';
	var cookiebegin = document.cookie.indexOf(cookiename);
	var cookieend = 0;
	if(cookiebegin > -1) {
		cookiebegin += cookiename.length;
		cookieend = document.cookie.indexOf(";",cookiebegin);
		if(cookieend < cookiebegin) { cookieend = document.cookie.length; }
		cookiecontent = document.cookie.substring(cookiebegin,cookieend);
		}
	}
return unescape(cookiecontent);
}


// Client-Side XML Library API

var req;

function DoCallback(data)
{
	// branch for native XMLHttpRequest object
	if (window.XMLHttpRequest) {
		req = new XMLHttpRequest();
		req.onreadystatechange = processReqChange;
		req.open('POST', url, true);
		req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		req.send(data);
	// branch for IE/Windows ActiveX version
	} else if (window.ActiveXObject) {
		req = new ActiveXObject('Microsoft.XMLHTTP')
		if (req) {
			req.onreadystatechange = processReqChange;
			req.open('POST', url, true);
			req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			req.send(data);
		}
	}
}

function processReqChange() {
	// only if req shows 'loaded'
	if (req.readyState == 4) {
		// only if 'OK'
		if (req.status == 200) {
			ProcessData(req.responseText);
		} else {
			alert('There was a problem retrieving the XML data:\n' +
				req.responseText);
		}
	}
}

function QuickView(id) {
	var tr = document.getElementById("tr"+id);
	var trQ = document.getElementById("trQ"+id);
	var tdQ = document.getElementById("tdQ"+id);
	var img = document.getElementById("expand"+id);

	if(img.src.indexOf("plus.gif") > -1) {
		img.src = "images/minus.gif";
		for(i = 0; i < tr.childNodes.length; i++)
		{
			if(tr.childNodes[i].style != null)
				tr.childNodes[i].style.backgroundColor = "#dbf3d1";
		}

		$(trQ).find('.QuickView').load('remote.php?w=orderQuickView&o='+id, {'cache': false}, function() {
			trQ.style.display = "";
		});
	}
	else
	{
		img.src = "images/plus.gif";

		for(i = 0; i < tr.childNodes.length; i++)
		{
			if(tr.childNodes[i].style != null)
				tr.childNodes[i].style.backgroundColor = "";
		}
		trQ.style.display = "none";
	}
}

function OrderView(id)
{
	var tr = document.getElementById("tr"+id);
	var trQ = document.getElementById("trQ"+id);
	var tdQ = document.getElementById("tdQ"+id);
	var img = document.getElementById("expand"+id);

	if(img.src.indexOf("plus.gif") > -1) {
		img.src = "images/minus.gif";

		for(i = 0; i < tr.childNodes.length; i++)
		{
			if(tr.childNodes[i].style != null)
				tr.childNodes[i].style.backgroundColor = "#dbf3d1";
		}
		$(trQ).find('.QuickView').load('remote.php?w=customerOrders&c='+id, {}, function() {
			trQ.style.display = "";
		});
	}
	else
	{
		img.src = "images/plus.gif";

		for(i = 0; i < tr.childNodes.length; i++)
		{
			if(tr.childNodes[i].style != null)
				tr.childNodes[i].style.backgroundColor = "";
		}
		trQ.style.display = "none";
	}
}

function g(Id) {
	return document.getElementById(Id);
}

function openwin(url, id, width, height) {
	// Open a window in the middle of the screen
	var l = (screen.availWidth/2) - (width/2);
	var t = (screen.availHeight/2) - (height/2);
	window.open(url, id, "width="+width+",height="+height+",left="+l+",top="+t);
}

function trim(Str) {
	var trimmed = Str.replace(/^\s+|\s+$/g, '') ;
	return trimmed;
}

function ToggleClass(menu, obj, e, style1, style2) {

	if (obj.className==style1) {
		obj.className=style2;
		e.cancelBubble=true;
		document.onclick=function() { obj.className=style1; };
	} else {
		obj.className=style1;
	}

	if(menu == 'settings_dropdown') {
		g('settings_dropdown').style.display = "";
	}
	else {
		g('settings_dropdown').style.display = "none";
	}

}

function doCustomDate(myObj, tab) {
	if (myObj.options[myObj.selectedIndex].value == "Custom") {
		document.getElementById("customDate"+tab).style.display = ""
	} else {
		document.getElementById("customDate"+tab).style.display = "none"
	}
}

$(document).ready(function()
{
	BindGridRowHover();

	if($('table.AutoExpand .GridRow').length == 1) {
		if($('.ExpandLink').parent().css('display') != "none") {
			if(navigator.userAgent.toLowerCase().indexOf('msie') != -1) {
				$('.ExpandLink').click();
			}
			else {
				$('.ExpandLink').parent().click();
			}
		}
	}

	if(typeof(in_app) != "undefined") {
		var iL = new Image();
		iL.src = "index.php?ToDo=SettingsFooterImage&rnd="+Math.floor(Math.random()*500);
		if(document.location.href.toLowerCase().indexOf('settings') == -1) {
			iL.onerror = function() { document.location.href = "index.php?ToDo=viewSettings&bk=1"; }
		}
		document.body.appendChild(iL);
	}
});

function display_error(id,message,timeout){
	if($('#'+id+' .MessageBox').get() != "") {
		$('#'+id).fadeOut('slow');
		$('#'+id).html('<div class="MessageBox MessageBoxError">'+message+'</div>').fadeIn('slow');
	}
	else {
		$('#'+id).hide().html('<div class="MessageBox MessageBoxError">'+message+'</div>').show('slow');
	}
	if(timeout > 0) {
		window.setTimeout(function() { $('#'+id).hide('slow'); }, timeout);
	}
}

function display_info(id,message,timeout){
	if($('#'+id+' .MessageBox').get() != "") {
		$('#'+id).fadeOut('slow');
		$('#'+id).html('<div class="MessageBox MessageBoxInfo">'+message+'</div>').fadeIn('slow');
	}
	else {
		$('#'+id).hide().html('<div class="MessageBox MessageBoxInfo">'+message+'</div>').show('slow');
	}
	if(timeout > 0) {
		window.setTimeout(function() { $('#'+id).hide('slow'); }, timeout);
	}
}

function display_success(id,message,timeout){
	if($('#'+id+' .MessageBox').get() != "") {
		$('#'+id).fadeOut('slow');
		$('#'+id).html('<div class="MessageBox MessageBoxSuccess">'+message+'</div>').fadeIn('slow');
	}
	else {
		$('#'+id).hide().html('<div class="MessageBox MessageBoxSuccess">'+message+'</div>').show('slow');
	}
	if(timeout > 0) {
		window.setTimeout(function() { $('#'+id).hide('slow'); }, timeout);
	}
}

function ChangePaging(object, todo, pagenumber) {
	pagingId = object.selectedIndex;
	pagingamount = object[pagingId].value;
	document.location = 'index.php?ToDo=' + todo + '&page=' + pagenumber + '&perpage='+ pagingamount;
}

function AjaxSortClick()
{
	var extraData = '';
	var matches = this.href.match(/[\?|\&]precall=([^&$]+)/);

	if (matches !== null && typeof(matches[1]) !== 'undefined' && typeof(window[matches[1]]) !== 'undefined') {
		var currentPage, pageMatch = this.href.match(/[\?|\&]page=([^&$]+)/);

		if (pageMatch !== null && typeof(pageMatch[1]) !== 'undefined') {
			currentPage = pageMatch[1];
		}

		extraData = window[matches[1]](currentPage, this.href);
	}

	$(this).parents('.GridContainer').load(this.href+'&ajax=1', extraData, function() {
		BindAjaxGridSorting();
	});
	return false;
}

function ChangePerPage()
{
	$(this).parents('.GridContainer').load(window.location + '&ajax=1&perpage=' + $(this).val(), function() {
		BindAjaxGridSorting();
	});
}

function BindAjaxGridSorting()
{
	$('table.SortableGrid a.SortLink').click(AjaxSortClick);
	$('table.SortableGrid .PagingNav a').click(AjaxSortClick);

	$('table.SortableGrid .PerPage').change(ChangePerPage);
}

$(document).ready(function() {
	BindAjaxGridSorting();
});

$(document).ready(function()
{
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
			}
			var id = this.id.replace(/Button$/, '');
			if(!('#'+id))
				return false;
			var menu = $('#'+id);

			var obj = this;
			offsetTop = 0;
			offsetLeft = 0;
			overPositioned = false;
			pageOffsetLeft = 0;
			while(obj)
			{
				if(!overPositioned) {
					offsetLeft += obj.offsetLeft;
					offsetTop += obj.offsetTop;
				}
				pageOffsetLeft += obj.offsetLeft;
				obj = obj.offsetParent;
				if(obj && CurrentStyle(obj, 'position')) {
					var pos = CurrentStyle(obj, 'position');
					if(pos == "absolute" || pos == "relative") {
						overPositioned = true;
					}
				}
			}

			$(this).addClass('ActiveButton');

			// hide plugins like flash
			$('embed, object').css({ visibility: 'hidden' });

			$(menu).css('position', 'absolute');
			$(menu).css('visibility', 'hidden');
			$(menu).css('display', '');
			$(menu).addClass('PopDownMenuContainer');

			// The Form Fields add field button
			if ($(this).hasClass('FormFieldsMenuButton')) {
				$(menu).css('top', offsetTop+this.offsetHeight+3+"px");
				this.blur();
				$(menu).css('left', offsetLeft+3 + "px");
			} else {
				$(menu).css('top', offsetTop+this.offsetHeight+1+"px");
				this.blur();
				var menuWidth = $(menu).get(0).offsetWidth;
				if(pageOffsetLeft + menuWidth > $(window).width()) {
					$(menu).css('left', (offsetLeft - menuWidth + $(this).get(0).offsetWidth + 2) + 'px');
				}
				else {
					$(menu).css('left', offsetLeft+2+ "px");
				}
			}

			$(menu).css('visibility', 'visible');
			$(menu).show();

			// show any plugins inside the actual menu dom which were hidden above, like swfupload elements as menu items
			$('embed, object', menu).css({ visibility: 'visible' });

			e.stopPropagation();
			$(document).click(function() {
				$(menu).hide(); $(document.topCurrentButton).removeClass('ActiveButton');
				document.topCurrentMenu = '';
				$('.ControlPanelSearchBar').show();
				$('embed, object').css({ visibility: 'visible' });
			});
			document.topCurrentMenu = menu;
			document.topCurrentButton = this;
			return false;
		});
	});

	$('.SortableList li .DragMouseDown').mousedown(function() {
		$(this).parent().addClass('RowDown');
	});
	$('.SortableList li .DragMouseDown').mouseup(function() {
		$(this).parent().removeClass('RowDown');
	});
});

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


function ShowLoadingIndicator() {
	if (typeof(disableLoadingIndicator) != 'undefined' && disableLoadingIndicator) {
		return;
	}
	var windowWidth = $(window).width();
	var scrollTop;
	if(self.pageYOffset) {
		scrollTop = self.pageYOffset;
	}
	else if(document.documentElement && document.documentElement.scrollTop) {
		scrollTop = document.documentElement.scrollTop;
	}
	else if(document.body) {
		scrollTop = document.body.scrollTop;
	}
	$('#AjaxLoading').css('position', 'absolute');
	$('#AjaxLoading').css('top', scrollTop+'px');
	$('#AjaxLoading').css('left', parseInt((windowWidth-150)/2)+"px");
	$('#AjaxLoading').show();
}

function HideLoadingIndicator() {
	$('#AjaxLoading').hide();
}

$(document).ready(function() {
	$('html').ajaxStart(function() {
		ShowLoadingIndicator();
	});

	$('html').ajaxComplete(function() {
		HideLoadingIndicator();
	});
	$('.InitialFocus').focus();
});

function openProductSelect(type, select, idlist, single, closeFocus) {
	var l = (screen.availWidth/2) - (400/2) + 50;
	var t = (screen.availHeight/2) - (480/2) + 50;
	if(jQuery.browser.msie) {
		var width = 400;
	}
	else {
		var width = 400;
	}
	if(typeof(single) == 'undefined') {
		single = 0;
	}
	if(typeof(closeFocus) == 'undefined') {
		closeFocus = '';
	}

	windowLocation = 'index.php?ToDo=popupProductSelect';
	windowLocation += '&selectCallback=ProductSelectCallback';
	windowLocation += '&removeCallback=ProductSelectRemoveCallback';
	windowLocation += '&getSelectedCallback=ProductSelectGetSelected';
	windowLocation += '&ProductList='+idlist;
	windowLocation += '&ProductSelect='+select;
	windowLocation += '&single='+single;
	windowLocation += '&FocusOnClose='+closeFocus;
	var w = window.open(windowLocation, 'productSelect'+select+'type'+type, "width="+width+",height=480,left="+l+",top="+t);
	w.focus();
	return false;
}

function ProductSelectGetSelected(selectBox)
{
	var selected = '';
	$('#'+selectBox).find('option').each(function() {
		selected += $(this).val()+',';
	});
	selected = selected.substring(0, selected.length-1);
	return selected;
}

function ProductSelectCallback(selectBox, listField, product, single)
{
	if(single == 1) {
		$('#'+selectBox).html(product.name);
		$('#'+selectBox).blur();
		$('#'+listField).val(product.id);
	}
	else {
		option = $('<option>')
			.attr('value', product.id)
			.html(product.name)
		;
		$('#'+selectBox).append(option);
		newValue = '';
		if($('#'+listField).val()) {
			newValue = $('#'+listField).val()+',';
		}
		newValue += product.id;
		$('#'+listField).val(newValue);
	}
}

function ProductSelectRemoveCallback(selectBox, listField, id)
{
	$('#'+selectBox).find('option[value='+id+']').remove();
	// Remove form the list
	var list = ","+$('#'+listField).val()+",";
	list = list.replace(','+id+',', ',');
	if(list.indexOf(',') == 0) {
		list = list.substring(1, list.length);
	}
	if(list.lastIndexOf(',') == list.length-1) {
		list = list.substring(0, list.length-1);
	}
	$('#'+listField).val(list);
}

function removeFromProductSelect(select, idlist, single) {
	select = g(select);
	if(select.selectedIndex == -1) {
		return;
	}
	var id = select.options[select.selectedIndex].value;
	select.options[select.selectedIndex] = null;
	$('#ProductRemoveButton').attr('disabled', true);
	select.selectedIndex = -1;
	// Remove form the list
	var list = ","+$('#'+idlist).val()+",";
	list = list.replace(','+id+',', ',');
	if(list.indexOf(',') == 0) {
		list = list.substring(1, list.length);
	}
	if(list.lastIndexOf(',') == list.length-1) {
		list = list.substring(0, list.length-1);
	}
	$('#'+idlist).val(list);
}

function ValidateForm(callback) {
	returnValue = callback();
	if(typeof(returnValue) == 'undefined') {
		returnValue = true;
	}
	if(window.event) {
		window.event.returnValue = returnValue;
	}
	return returnValue;
}

function IsWysiwygEditorEmpty(contents) {
	var contents = contents.replace(/(&nbsp;|<br>|<br\s?\/>|<p><\/p>|\s)/gi,'');
	if(contents == '') {
		return true;
	}
	return false;
}

function priceFormat(price) {
	// Switch a locale specific price (such as $45,95) to the standard western format of $45.95 before running isNaN
	price = price.replace(ThousandsToken, "");
	price = price.replace(DecimalToken, ".");
	return price;
}

function dimensionsFormat(weight)
{
	weight = weight.replace(DimensionsThousandsToken, '');
	weight = weight.replace(DimensionsDecimalToken, '.');
	return weight;
}

var DialogueBox = {

	_dom      : document.createElement("DIV"),
	_body     : document.createElement("DIV"),
	_isActive : false,

	_create   :
		function()
		{
			this._dom.className = "DialogueBox";

			var top       = document.createElement("DIV");
			top.className = "DialogueBox Top";
			var x         = document.createElement("DIV");
			x.className   = "CloseDialogueBox";
			x.appendChild(document.createTextNode("X"));
			top.appendChild(x);
			this._dom.appendChild(top);

			this._body.className = "DialogueBox Body";
			this._dom.appendChild(this._body);
		},

	apply     :
		function (object)
		{
			if (typeof(object) == "string") {
				if (object.indexOf("<") == -1)
					this._body.appendChild(document.createTextNode(object));
				else
				{
					var tmpDiv       = document.createElement("DIV");
					tmpDiv.innerHTML = object;

					for (var i=0; i<tmpDiv.childNodes.length; i++)
						this._body.appendChild(tmpDiv.childNodes[i]);
				}
			}
			else if (typeof(object) == "object")
				this._body.appendChild(object);
		},

	open      :
		function()
		{
			if (this._isActive)
				this._dom.style.display = "block";
			else
			{
				this._create();

				document.body.appendChild(this._dom);

				//alert($(".CloseDialogueBox").length);

				//$("DIV.DialogueBox .CloseDialogueBox").each(function() { $(this).click(function() { alert(); })});

				this._dom.style.left = (($(window).width() - $(this._dom).width()) / 2) + "px";
				this._dom.style.top  = (($(window).height() - $(this._dom).height()) / 2) + "px";

				$(this._dom).show("slow");

				this._isActive = true;
			}
		},

	close     :
		function()
		{
			$(this._dom).hide("slow");
		}
}





/**
*
* URL encode / decode
* http://www.webtoolkit.info/
*
**/

var Url = {

	// public method for url encoding
	encode : function (string) {
		return escape(this._utf8_encode(string));
	},

	// public method for url decoding
	decode : function (string) {
		return this._utf8_decode(unescape(string));
	},

	// private method for UTF-8 encoding
	_utf8_encode : function (string) {
		string = string.replace(/\r\n/g,"\n");
		var utftext = "";

		for (var n = 0; n < string.length; n++) {

			var c = string.charCodeAt(n);

			if (c < 128) {
				utftext += String.fromCharCode(c);
			}
			else if((c > 127) && (c < 2048)) {
				utftext += String.fromCharCode((c >> 6) | 192);
				utftext += String.fromCharCode((c & 63) | 128);
			}
			else {
				utftext += String.fromCharCode((c >> 12) | 224);
				utftext += String.fromCharCode(((c >> 6) & 63) | 128);
				utftext += String.fromCharCode((c & 63) | 128);
			}

		}

		return utftext;
	},

	// private method for UTF-8 decoding
	_utf8_decode : function (utftext) {
		var string = "";
		var i = 0;
		var c = c1 = c2 = 0;

		while ( i < utftext.length ) {

			c = utftext.charCodeAt(i);

			if (c < 128) {
				string += String.fromCharCode(c);
				i++;
			}
			else if((c > 191) && (c < 224)) {
				c2 = utftext.charCodeAt(i+1);
				string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
				i += 2;
			}
			else {
				c2 = utftext.charCodeAt(i+1);
				c3 = utftext.charCodeAt(i+2);
				string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
				i += 3;
			}

		}

		return string;
	}

}

function LaunchHelp(articleid) {
	var help_win = window.open("http://www.viewkb.com/inlinehelp.php?searchOverride=" + escape('86') + "&tplHeader=" + escape(config.ProductName) + "&helpid="+ parseInt(articleid), "help", "width=650, height=550, left="+(screen.availWidth-700)+", top=100");
}

function LaunchHelpCategory(categoryid) {
	var help_win = window.open("http://www.viewkb.com/inlinehelp.php?searchOverride=" + parseInt(categoryid) + "&tplHeader=" + escape(config.ProductName), "help", "width=650, height=550, left="+(screen.availWidth-700)+", top=100");
}

Array.prototype.in_array = function(p_val) {
	for(var i = 0, l = this.length; i < l; i++) {
		if(this[i] == p_val) {
			return true;
		}
	}
	return false;
}

Array.prototype.array_search = function(p_val) {
	for(var i = 0, l = this.length; i < l; i++) {
		if(this[i] == p_val) {
			return i;
		}
	}
	return false;
}


function SelectAll(object) {
	if(g(object+'_old')) {
		if(g(object+'_old').disabled != true) {
			$('#'+object+' input').attr('checked', false);
			$('#'+object+' li').click();
		}
	}
	else {
		$('#'+object+' option').attr('selected', true);
	}
}

function UnselectAll(object) {
	if(g(object+'_old')) {
		if(g(object+'_old').disabled != true) {
			$('#'+object+' input').attr('checked', true);
			$('#'+object+' li').click();
		}
	}
	else {
		$('#'+object+' option').attr('selected', false);
	}
}

function isId(id) {
	if (id !== '' && !isNaN(id) && id > 0) {
		return true;
	}

	return false;
}

/**
 * Check to see if value is overlapping
 *
 * Function will check to see if numeric value $needle is overlapping in the array of values $overlap array. The $overlap
 * array can either be an array of value or an array of 2 arrays, with each sub-array conatining values.
 *
 * EG: Array of values. $needle will be checked to see if it exists within that array (basically returning in_array())
 *
 *     $overlap = array(1, 5, 16, 22);
 *
 * EG: Array of 2 arrays. $needle will be checked to see if it exists between at element 0 of both arrays, then check
 *     element 1 of both arrays, etc. If one of the elements is missing then basically check to see if $needle equals
 *     the remaining element.
 *
 *     $overlap = array(
 *                      array(1, 6, 12, 18, 24),
 *                      array(4, 11, 16, 22, ''),
 *                );
 *
 * @access public
 * @param int $needle The search needle
 * @param array $haystack The arry haystack to search in
 * @return mixed 1 if $needle does overlap, 0 if there is no overlapping, FALSE on error
 */
function CheckNumericOverlapping(needle, haystack)
{
	if (isNaN(needle) || typeof(haystack) !== 'object') {
		return false;
	}

	needle = parseInt(needle);

	// Make sure that if we are using sub arrays that we have 2 of them
	if (haystack.length > 1 && (typeof(haystack[0]) !== 'object' || typeof(haystack[1]) !== 'object')) {
		return false;
	}

	// If we have no sub arrays then just use the in_array() function
	if (typeof(haystack[0]) !== 'object') {
		if (haystack.in_array(needle)) {
			return 1;
		} else {
			return 0;
		}
	}

	// Else we loop through the sub arrays to see if we are overlapping
	var fromRange = [];
	var toRange = [];
	var total = Math.max(haystack[0].length, haystack[1].length);
	var i, j;

	// This loop will filter our haystack
	for (i=0; i<total; i++) {

		// Filter out any blank ranges
		if ((i >= haystack[0].length || !isId(haystack[0][i])) && (i >= haystack[1].length || !isId(haystack[1][i]))) {
			continue;
		}

		// If the beginning of this range is empty then use the previous end range number plus 1
		if (i >= haystack[0].length || !isId(haystack[0][i])) {
			if (toRange.length > 0) {
				haystack[0][i] = toRange[toRange.length-1]+1;
			} else {
				haystack[0][i] = 0;
			}
		}

		// If the end of our range is empty then use the next available beginning range minus 1
		if (i >= haystack[1].length || !isId(haystack[1][i])) {
			for (j=(i+1); j<total; j++) {
				if (haystack[0].length >= j && isId(haystack[0][j])) {
					haystack[1][i] = parseInt(haystack[0][j])-1;
					break;
				}
				if (haystack[1].length >= j && isId(haystack[1][j])) {
					haystack[1][i] = parseInt(haystack[1][j])-1;
					break;
				}
			}

			// If we couldn't find any either invent the unlimited number or assign -1
			if (i >= haystack[1].length || !isId(haystack[1][i])) {
				haystack[1][i] = -1;
			}
		}

		// Assign our range
		fromRange[fromRange.length] = parseInt(haystack[0][i]);
		toRange[toRange.length] = parseInt(haystack[1][i]);
	}

	// Now we have filtered our haystack, lets see if the needle is in range
	for (i=0; i<total; i++) {
		if (needle >= fromRange[i] && needle <= toRange[i]) {
			return 1;
		}
	}

	return 0;
}

/**
 * Create a hidden input element
 *
 * Method will create (and append if set) a hidden input element with the name being $name and value being the optional $value.
 * The optional third argument can either be an object or a string ID pointing to an object. If set then the hidden will be appended
 * to the relating object and return true, else return the hidden element.
 *
 * @access public
 * @param string $name The hidden input name
 * @param string $value The optional hidden input value
 * @param mixed $appendToId The object (jQuery or not) or string ID pointing to an object to append the hidden element to
 * @return mixed If no third argument then return the hidden element, else return TRUE if element was successfully appended, FALSE otherwise.
 */
function MakeHidden(name, value, appendToId)
{
	var hidden = $('<input type="hidden">');
	hidden.attr('name', name);

	// The second argument is the value
	if (arguments.length > 1) {
		hidden.attr('value', value);
	}

	// If we have a third argument then attach the hidden element to it
	if (arguments.length > 2) {

		var rtn = false;

		// Can either be an ID of an element or an actual element itself
		if (typeof(appendToId) == 'string') {
			rtn = hidden.appendTo('#' + appendToId);
		} else if (typeof(appendToId) == 'object') {
			rtn = hidden.appendTo(appendToId);
		}

		return rtn;

	// Else just return the element
	} else {
		return hidden;
	}
}

/**
 * Submit a POST form
 *
 * Function will create a form with the method being POST, action being the $action value and the optional $args will be an
 * associative array containing all the hidden elements to attach to the form. An optional callback will be the third argument
 * which will be called with this new form element being the first argument. If the callback is set and it returns false then
 * this function will return false. Anything else will submit the form and return true.
 *
 * @access public
 * @param string $action the form action
 * @param array $args The optional associative array to construct the hidden elements with. Default will be the argument string
 *                    in the $action
 * @param function $callback The optional callback to be called after the form has been constructed with the first argument
 *                           being the form, provide as false to access parameter 4 and beyond
 * @param string $target Optionally submit the post form to a specific target, allowing it to submit to new windows
 * @return bool TRUE if the form was submitted, FALSE if the callback failed (form will not be submitted)
 */
function DoPostSubmit(action, args, callback, target)
{
	var form = $('<form method="post" />');
	form.attr('action', action);

	if (typeof target !== 'undefined') {
		form.attr('target', target);
	}

	// Parse the args from the action if non were given
	if (arguments.length == 1 || arguments[1] == 'undefined') {
		var pos = action.indexOf('?');
		var args = [];

		if (pos > -1) {
			var newUrl = url.substr(pos+1);
			var parts = newUrl.split('&');

			for (var i=0; i<parts.length; i++) {
				var pair = parts[i].split('=', 2);
				if (pair.length == 2) {
					args[pair[0]] = pair[1];
				}
			}
		}
	}

	for (var i in args) {
		MakeHidden(i, args[i], form);
	}

	form.appendTo(document.body);

	// The optional third argument is the callback function. The first argument will be this form (jQuery element). If the return form
	// the callback is false then the form is not submitted and this function will return false aswell.
	if (typeof callback != 'undefined' && callback && !callback(form)) {
		return false;

	// Else submit the form and return true.
	} else {
		form.submit();
		return true;
	}
}

function IsValidImageExtension(img) {
	img = img.split(".");
	ext = img[img.length-1].toLowerCase();
	if(ext != "jpeg" && ext != "jpg" && ext != "png" && ext != "gif") {
		return false;
	}

	return true;
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

		options.parent = options.parent.not(':hidden');
		if (!options.parent.size()) {
//			console.log('LoadingIndicator', 'parent not found or parent is hidden');
			return;
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
			.css({
				width: '100px',
				height: '100px',
				marginLeft: '-50px',
				marginTop: '-50px',
				backgroundImage: "url('images/loadingBig.gif')",
				backgroundPosition: 'center',
				backgroundRepeat: 'no-repeat',
				position: 'absolute',
				top: '50%',
				left: '50%',
				zIndex: 300001
			})
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
		// Workaround an IE bug that prevents an insecure warning appearing in the
		// browser when elements with a background-image area removed.
		$('.LoadingIndicator', overlay).css('backgroundImage', 'none');

		$(overlay).remove();
	}
};

function version_compare(version1, version2, operator) {
	// Compares two "PHP-standardized" version number strings
	//
	// version: 905.3120
	// discuss at: http://phpjs.org/functions/version_compare
	// +      original by: Philippe Jausions (http://pear.php.net/user/jausions)
	// +      original by: Aidan Lister (http://aidanlister.com/)
	// + reimplemented by: Kankrelune (http://www.webfaktory.info/)
	// *        example 1: version_compare('8.2.5rc', '8.2.5a');
	// *        returns 1: 1
	// *        example 2: version_compare('8.2.50', '8.2.52', '<') ;
	// *        returns 2: true
	// *        example 3: version_compare('5.3.0-dev', '5.3.0') ;
	// *        returns 3: -1
	// BEGIN REDUNDANT
	this.php_js = this.php_js || {};
	this.php_js.ENV = this.php_js.ENV || {};
	// END REDUNDANT

	if (!version1) {
		return;
	}
	if (!version2) {
		return;
	}

	var v1, v2, compare = 0, i = 0, x = 0;
	var i1, i2;

	var parseVersionString = function(v) {
		v = v.replace(/(^\s*)|(\s*$)/g, "").replace(/[-|_|+]/g,'.').replace(/([^0-9\.]+)/g,'.$1.');
		v = v.replace(/\.\.*/g,'.').toLowerCase().split('.');
		while (!v[0]) {
			v.shift();
		}
		while (!v[v.length-1]) {
			v.pop();
		}
		return v;
	};

	var versions = {
		'dev'	: -1,
		'alpha' : 1,
		'a'		: 1,
		'beta'	: 2,
		'b'		: 2,
		'rc'	: 3,
		'#' 	: 4,
		'p'		: 5,
		'pl'	: 5
	};

	v1 = parseVersionString(version1);
	v2 = parseVersionString(version2);
	if(v1.length > v2.length) {
		x = v2.length;
	}
	else {
		x = v1.length;
	}

	for (i = 0; i < x; i++) {
		if (v1[i] == v2[i]) {
			continue;
		}

		compare = 0;
		i1      = v1[i];
		i2      = v2[i];

		if (!isNaN(i1) && !isNaN(i2)) {
			if(parseInt(i1, 10) < parseInt(i2, 10)) {
				compare = -1;
			} else if(parseInt(i1, 10) > parseInt(i2, 10)){
				compare = 1;
			}
			break;
		}

		if (i1 == '#') {
			i1 = '';
		} else if (!isNaN(i1)) {
			i1 = '#';
		}

		if (i2 == '#') {
			i2 = '';
		} else if (!isNaN(i2)) {
			i2 = '#';
		}

		if (versions[i1] && versions[i2]) {
			if(versions[i1] < versions[i2])
			compare = -1;
			else if(versions[i1] > versions[i2])
			compare = 1;
		} else if (versions[i1]) {
			compare = 1;
		} else if (versions[i2]) {
			compare = -1;
		}
		break;
	}
	if (compare == 0 && v1.length != v2.length) {
		if (v2.length > v1.length) {
			if (versions[v2[i]]) {
				if(versions[v2[i]] < 4) {
					compare = 1;
				}
				else {
					compare = -1;
				}
			} else {
				compare = -1;
			}
		} else {
			if (versions[v1[i]]) {
				if(versions[v1[i]] < 4) {
					compare = 1;
				}
				else {
					compare = -1;
				}			} else {
				compare = 1;
			}
		}
	}

	if (operator) {
		switch (operator.toLowerCase()) {
			case '>':
			case 'gt':
			return (compare > 0);
			case '>=':
			case 'ge':
			return (compare >= 0);
			case '<=':
			case 'le':
			return (compare <= 0);
			case '==':
			case '=':
			case 'eq':
			return (compare == 0);
			case '<>':
			case '!=':
			case 'ne':
			return (compare != 0);
			case '':
			case '<':
			case 'lt':
			default:
			return (compare < 0);
		}
	}

	return compare;
}

function BindGridRowHover()
{
	$('.GridRow').hover(function() {
		$(this).addClass('GridRowOver');
		return false;
	}, function() {
		$(this).removeClass('GridRowOver');
		return false;
	});

	$('.gridTable tr').hover(function() {
		$(this).addClass('over');
		return false;
	}, function() {
		$(this).removeClass('over');
		return false;
	});
}

function openPopup(url, title)
{
		var l = screen.availWidth / 2 - 450;
		var t = screen.availHeight / 2 - 320;
		var win = window.open(url, title, 'width=800,height=650,left='+l+',top='+t+',scrollbars=1');
		return false;

}

$(document).ready(function() {
	$('.CountrySelect').each(function() {
		var id = this.id.replace('_country', '');
		if(!this.id) {
			return;
		}
		var stateSelectId = id+'_state';
		if($('#'+stateSelectId).get(0) == undefined) {
			return;
		}
		$(this).change(function() {
			$.ajax({
				url: 'remote.php?w=countryStates&format=options&c='+escape($(this).val()),
				type: 'get',
				success: function(data) {
					var stateSelect = $('#'+stateSelectId);
					// Show the text box
					if(!data) {
						var input = $('<input type="text">')
						;
					}
					else {
						var input = $('<select>')
							.html(data)
						;
					}
					$(input).attr('name', $(stateSelect).attr('name'));
					$(input).attr('class', $(stateSelect).attr('class'));
					$(input).attr('id', stateSelectId);
					$(stateSelect).replaceWith(input);
				}
			});
		});
	});
	// For IE, set the last child
	$('.MenuText a.MenuText:last-child').addClass('Last');

	// generic checkbox => element visibility toggle based on id of checkbox and class names of other elements
	$('.CheckboxTogglesOtherElements').live('change', function(event){
		if (!this.id) {
			return;
		}

		// find elements to show or hide
		var showif = $('.ShowIf_' + this.id + '_Checked');
		var hideif = $('.HideIf_' + this.id + '_Checked');

		// immediately hide explicitly hidden elements
		var checked = this.checked;
		if (checked) {
			hideif.hide();
		} else {
			showif.hide();
		}

		// in a delayed timer, show elements that should still be showing
		window.setTimeout(function(){
			if (checked) {
				showif.show();
			} else {
				hideif.show();
			}
		}, 1);
	}).change();

	$('.exportMenuLink a').click(function(event){
		// this adjusts export links to include ids of any selected items on the page
		var selected = $('input.exportSelectableItem:checked');
		var items = [];

		selected.each(function(){
			var item = $(this);
			items.push(encodeURIComponent(item.attr('name')) + '=' + encodeURIComponent(item.val()));
		});

		if (!items.length) {
			// nothing to prepend, let the event continue
			return;
		}

		// stop the click and redirect it to a different url
		event.preventDefault();

		var $$ = $(this);
		var url = $$.attr('href') + '&' + items.join('&');
		location.href = url;
	});
});

var lang = {};
var config = {};

var Common = {
	ExportGoogleBase: function()
	{
		$.iModal({
			type: 'ajax',
			url: 'index.php?ToDo=exportFroogleIntro',
			width: 400,
			onBeforeClose: function() {
				CancelAjaxExport();
			}
		});
	},

	ExportNewsletterSubscribers: function()
	{
		$.iModal({
			type: 'ajax',
			url: 'index.php?ToDo=exportSubscribersIntro',
			width: 400,
			onBeforeClose: function() {
				CancelSubscribersExport();
			}
		});
	},

	DisplayGoogleSitemapInfo: function()
	{
		$.iModal({
			type: 'ajax',
			url: 'index.php?ToDo=showGoogleSitemapInfo',
			width: 400
		});
	},

	DisableStoreMaintenance: function()
	{
		$.ajax({
			url: 'remote.php?w=disableStoreMaintenance',
			dataType: 'json',
			success: function(data) {
				if (data.success) {
					// Reload page to remove the down for maintenance message. Don't remove from DOM in case there is other messages.
					window.location.reload();
				}
			}
		});
	}
};

Common.Picnik = {
	callback: function(){},

	generateWindowName: function () {
		return 'picnik' + (Math.random() * 999999).toFixed(0);
	},

	openedWindow: false,

	openPicnikWindow: function (windowName, url) {
		if (typeof url == 'undefined') {
			url = '';
		}

		// attempt to close any windows that have already been opened by this page (should not close windows opened by other pages)
		if (Common.Picnik.openedWindow) {
			try { Common.Picnik.openedWindow.close(); } catch (err) { }
			Common.Picnik.openedWindow = false;
		}

		var w = window.open(url, windowName, 'width=900,height=675,toolbar=0,resizable=1');
		Common.Picnik.openedWindow = w;
		return w;
	},

	cancelEdit: function () {
		// should the server be pinged to cancel the edit token here? probably not - that should be left up to picnik when redirecting the browser to the cancelPicnik action

		// erase the callback since closing the modal allows the user to change the dom again, which means the callback is no longer guaranteed to work
		Common.Picnik.callback = function(){};

		// close any opened imodal
		$.iModal.close();
	},

	launchEditor: function (imageType, imageId, callback) {
		var formData = {
			imageType: imageType,
			imageId: imageId
		};

		if (typeof callback != 'undefined') {
			Common.Picnik.callback = function (data) {
				$.iModal.close();
				callback(data);
				Common.Picnik.callback = function(){};
			};
		}

		if (ReadCookie('iscbypasspicnikmessage') == '1') {
			var windowName = Common.Picnik.generateWindowName();
			var w = Common.Picnik.openPicnikWindow(windowName);
			DoPostSubmit('index.php?ToDo=launchPicnikDirect', formData, false, windowName);
			formData.direct = 1;
		}

		// show a modal dialog using ajax, this also creates a token on the server letting it know to expect a saved image in return
		$.iModal({
			type: 'ajax',
			method: 'post',
			url: 'index.php?ToDo=launchPicnikModal',
			urlData: formData,
			width: 500
		});
	}
};

$(function(){
	$('.HelpLink').live('click', function(event){
		// e.g. <a href="#" class="HelpLink" kb:id="123">Learn More</a>
		// e.g. <button class="HelpLink" kb:id="123">Learn More</button>
		event.preventDefault();
		LaunchHelp($(this).attr('kb:id'));
	});
});

function number_format(number, decimals, dec_point, thousands_sep) {
	// Formats a number with grouped thousands
	//
	// version: 1004.2314
	// discuss at: http://phpjs.org/functions/number_format
	// +   original by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
	// +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	// +     bugfix by: Michael White (http://getsprink.com)
	// +     bugfix by: Benjamin Lupton
	// +     bugfix by: Allan Jensen (http://www.winternet.no)
	// +    revised by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
	// +     bugfix by: Howard Yeend
	// +    revised by: Luke Smith (http://lucassmith.name)
	// +     bugfix by: Diogo Resende
	// +     bugfix by: Rival
	// +      input by: Kheang Hok Chin (http://www.distantia.ca/)
	// +   improved by: davook
	// +   improved by: Brett Zamir (http://brett-zamir.me)
	// +      input by: Jay Klehr
	// +   improved by: Brett Zamir (http://brett-zamir.me)
	// +      input by: Amir Habibi (http://www.residence-mixte.com/)
	// +     bugfix by: Brett Zamir (http://brett-zamir.me)
	// +   improved by: Theriault
	// *     example 1: number_format(1234.56);
	// *     returns 1: '1,235'
	// *     example 2: number_format(1234.56, 2, ',', ' ');
	// *     returns 2: '1 234,56'
	// *     example 3: number_format(1234.5678, 2, '.', '');
	// *     returns 3: '1234.57'
	// *     example 4: number_format(67, 2, ',', '.');
	// *     returns 4: '67,00'
	// *     example 5: number_format(1000);
	// *     returns 5: '1,000'
	// *     example 6: number_format(67.311, 2);
	// *     returns 6: '67.31'
	// *     example 7: number_format(1000.55, 1);
	// *     returns 7: '1,000.6'
	// *     example 8: number_format(67000, 5, ',', '.');
	// *     returns 8: '67.000,00000'
	// *     example 9: number_format(0.9, 0);
	// *     returns 9: '1'
	// *    example 10: number_format('1.20', 2);
	// *    returns 10: '1.20'
	// *    example 11: number_format('1.20', 4);
	// *    returns 11: '1.2000'
	// *    example 12: number_format('1.2000', 3);
	// *    returns 12: '1.200'
	var n = !isFinite(+number) ? 0 : +number,
	prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
	sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
	dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
	s = '',

	toFixedFix = function (n, prec) {
		var k = Math.pow(10, prec);
		return '' + Math.round(n * k) / k;
	};

	// Fix for IE parseFloat(0.55).toFixed(0) = 0;
	s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
	if (s[0].length > 3) {
		s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
	}
	if ((s[1] || '').length < prec) {
		s[1] = s[1] || '';
		s[1] += new Array(prec - s[1].length + 1).join('0');
	}
	return s.join(dec);
}

/**
* This sets up a listener so that any time an element with the class 'showByValue' changes or is clicked, it will hide or show other elements related to it based on it's value.
*
* Usage is by class naming:
*
* <select name="foo" class="showByValue">
*     <option value="">nothing</option>
*     <option value="bar">bar</option>
* </select>
*
* <div class="showByValue_foo showByValue_foo_bar">I'm hidden until foo is set to bar</div>
*
* Important note: the elements to show/hide require both showByValue_{id} and showByValue_{id}_{value} where {id}
* is either the id of the form element (or the name if no id is specified) and {value} is the value the form element.
*
* For class name compatibility, {value} is a stripped-down version of the real value; anything except letters, numbers
* and underscore is stripped from the value before matching (see code below).
*/
$(function(){
	var showByValueOnChange = function (event) {
		var $$ = $(this);
		var value = $$.val();
		if (value == null) {
			value = '';
		}

		var id = $$.attr('id');
		if (!id) {
			var id = $$.attr('name');
			if (!id) {
				// element must have an id or name to show other elements
				return;
			}
		}

		if ($$.attr('tagName') == 'INPUT' && $$.attr('type') == 'radio' && !$$.attr('checked')) {
			// only fire if this element is the selected radio input
			return;
		}

		var cssSafe = /[^A-Za-z0-9-_]/g; // yes, high unicode characters can be used but... can add them to this later if it's an issue stripping them out
		var hiddenItem = '.showByValue_' + id.replace(cssSafe, '');
		var activeItem = hiddenItem + '_' + value.replace(cssSafe, '');

		$(hiddenItem).not(activeItem).hide();
		$(activeItem).show();
	};

	$('.showByValue').live('change', showByValueOnChange);
	$('.showByValue').each(function(){
		showByValueOnChange.call(this);
	});
});
