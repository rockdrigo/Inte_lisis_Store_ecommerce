/* Common Javascript functions for use throughout Interspire Shopping Cart */

// Fetch the value of a cookie
function get_cookie(name) {
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
}

// Set a cookie
function set_cookie(name, value, expires)
{
	if(!expires) {
		expires = "; expires=Wed, 1 Jan 2020 00:00:00 GMT;"
	} else {
		expire = new Date();
		expire.setTime(expire.getTime()+(expires*1000));
		expires = "; expires="+expire.toGMTString();
	}
	document.cookie = name+"="+escape(value)+expires;
}

/* Javascript functions for the products page */
var num_products_to_compare = 0;
var product_option_value = "";
var CurrentProdTab = "";
function showProductImage(filename, product_id, currentImage) {
	var l = (screen.availWidth/2)-350;
	var t = (screen.availHeight/2)-300;
	var variationAdd = '';
	if(ShowVariationThumb) {
		variationAdd = '&variation_id='+$('body').attr('currentVariation');
		CurrentProdThumbImage = null;
	}
	UrlAddOn = '';

	if(currentImage) {
		UrlAddOn = "&current_image="+currentImage;
	} else if(CurrentProdThumbImage) {
		UrlAddOn = "&current_image="+CurrentProdThumbImage;
	}
	var imgPopup = window.open(filename + "?product_id="+product_id+variationAdd+UrlAddOn, "imagePop", "toolbar=0,scrollbars=1,location=0,statusbar=1,menubar=0,resizable=1,width=700,height=600,top="+t+",left="+l);
	imgPopup.focus();
}

function CheckQuantityLimits (form)
{
	var qty = parseInt($('#qty_').val(), 10);

	if (qty < productMinQty) {
		alert(lang.ProductMinQtyError);
		return false;
	}

	if (qty > productMaxQty) {
		alert(lang.ProductMaxQtyError);
		return false;
	}

	return true;
}

function CheckProductConfigurableFields(form)
{
	var requiredFields = $('.FieldRequired');
	var valid = true;
	requiredFields.each(function() {
		var namePart = this.name.replace(/^.*\[/, '');
		var fieldId = namePart.replace(/\].*$/, '');

		if(this.type=='checkbox' ) {
			if(!this.checked) {
				valid = false;
				alert(lang.EnterRequiredField);
				this.focus();
				return false;
			}
		} else if(this.value == '') {
			if(this.type != 'file' || (this.type == 'file' && document.getElementById('CurrentProductFile_'+fieldId).value == '')) {
				valid = false;
				alert(lang.EnterRequiredField);
				this.focus();
				return false;
			}
		}
	});

	var fileFields = $(form).find('input[type=file]');
	fileFields.each(function() {
		if(this.value != '') {
			var namePart = this.name.replace(/^.*\[/, '');
			var fieldId = namePart.replace(/\].*$/, '');
			var fileTypes = document.getElementById('ProductFileType_'+fieldId).value;

			var ext = this.value.replace(/^.*\./, '').toLowerCase();
			
			if(fileTypes == '*') fileTypes = ','+ext+',';
			else fileTypes = ','+fileTypes.replace(' ', '').toLowerCase()+',';

			if(fileTypes.indexOf(','+ext+',') == -1) {
				alert(lang.InvalidFileTypeJS);
				this.focus();
				this.select();
				valid = false;
			}

		}
	});

	return valid;
}

function check_add_to_cart(form, required) {
	var valid = true;
	var qtyInputs = $(form).find('input.qtyInput');
	qtyInputs.each(function() {
		if(isNaN($(this).val()) || $(this).val() <= 0) {
			alert(lang.InvalidQuantity);
			this.focus();
			this.select();
			valid = false;
			return false;
		}
	});
	if(valid == false) {
		return false;
	}

	if(!CheckProductConfigurableFields(form)) {
		return false;
	}

	if (!CheckQuantityLimits(form)) {
		return false;
	}

	if(required && !$(form).find('.CartVariationId').val()) {
		alert(lang.OptionMessage);
		var select = $(form).find('select').get(0);
		if(select) {
			select.focus();
		}
		var radio = $(form).find('input[type=radio]').get(0);
		if(radio) {
			radio.focus();
		}
		return false;
	}

	if (!CheckEventDate()) {
		return false;
	}

	return true;
}

function compareProducts(compare_path) {
	var pids = "";

	if($('form').find('input:checked[name=compare_product_ids]').size() >= 2) {
		var cpids = document.getElementsByName('compare_product_ids');

		for(i = 0; i < cpids.length; i++) {
			if(cpids[i].checked)
				pids = pids + cpids[i].value + "/";
		}

		pids = pids.replace(/\/$/, "");
		document.location.href = compare_path + pids;
		return false;
	}
	else {
		alert(lang.CompareSelectMessage);
		return false;
	}
}

function product_comparison_box_changed(state) {
	// Increment num_products_to_compare - needs to be > 0 to submit the product comparison form


	if(state)
		num_products_to_compare++;
	else
		if (num_products_to_compare != 0)
			num_products_to_compare--;
}

function remove_product_from_comparison(id) {
	if(num_compare_items > 2) {
		for(i = 1; i < 11; i++) {
			document.getElementById("compare_"+i+"_"+id).style.display = "none";
		}

		num_compare_items--;
	}
	else {
		alert(lang.CompareTwoProducts);
	}
}

function show_product_review_form() {
	document.getElementById("rating_box").style.display = "";
	if(typeof(HideProductTabs) != 'undefined' && HideProductTabs == 0) {
		CurrentProdTab = 'ProductReviews_Tab';
	} else {
		document.location.href = "#write_review";
	}
}

function jump_to_product_reviews() {
	if(typeof(HideProductTabs) != 'undefined' && HideProductTabs == 0) {
		CurrentProdTab = 'ProductReviews_Tab';
	} else {
		document.location.href = "#reviews";
	}
}

function g(id) {
	return document.getElementById(id);
}

function check_product_review_form() {
	var revrating = g("revrating");
	var revtitle = g("revtitle");
	var revtext = g("revtext");
	var revfromname = g("revfromname");
	var captcha = g("captcha");

	if(revrating.selectedIndex == 0) {
		alert(lang.ReviewNoRating);
		revrating.focus();
		return false;
	}

	if(revtitle.value == "") {
		alert(lang.ReviewNoTitle);
		revtitle.focus();
		return false;
	}

	if(revtext.value == "") {
		alert(lang.ReviewNoText);
		revtext.focus();
		return false;
	}

	if(captcha.value == "" && HideReviewCaptcha != "none") {
		alert(lang.ReviewNoCaptcha);
		captcha.focus();
		return false;
	}

	return true;
}

function check_small_search_form() {
	var search_query = g("search_query");

	if(search_query.value == "") {
		alert(lang.EmptySmallSearch);
		search_query.focus();
		return false;
	}

	return true;
}

function setCurrency(currencyId)
{
	var gotoURL = location.href;

	if (location.search !== '')
	{
		if (gotoURL.search(/[&|\?]setCurrencyId=[0-9]+/) > -1)
			gotoURL = gotoURL.replace(/([&|\?]setCurrencyId=)[0-9]+/, '$1' + currencyId);
		else
			gotoURL = gotoURL + '&setCurrencyId=' + currencyId;
	}
	else
		gotoURL = gotoURL + '?setCurrencyId=' + currencyId;

	location.href = gotoURL;
}


// Dummy sel_panel function for when design mode isn't enabled
function sel_panel(id) {}

function inline_add_to_cart(filename, product_id, quantity, returnTo) {
	if(typeof(quantity) == 'undefined') {
		var quantity = '1';
	}
	var html = '<form action="' + filename + '/cart.php" method="post" id="inlineCartAdd">';
	if(typeof(returnTo) != 'undefined' && returnTo == true) {
		var returnLocation = window.location;
		html += '<input type="hidden" name="returnUrl" value="'+escape(returnLocation)+'" />';
	}
	html += '<input type="hidden" name="action" value="add" />';
	html += '<input type="hidden" name="qty" value="'+quantity+'" />';
	html += '<input type="hidden" name="product_id" value="'+product_id+'" />';
	html += '<\/form>';
   $('body').append(html);
   $('#inlineCartAdd').submit();
}

function ShowPopupHelp(content, url, decodeHtmlEntities) {
	var popupWindow = open('', 'view','height=450,width=550');

	if(decodeHtmlEntities) {
		content = HtmlEntityDecode(content);
	}
	if (window.focus) {
		popupWindow.focus();
	}

	var doc = popupWindow.document;
	doc.write(content);
	doc.close();

	return false;
}

function HtmlEntityDecode(str) {
   try {
	  var tarea=document.createElement('textarea');
	  tarea.innerHTML = str; return tarea.value;
	  tarea.parentNode.removeChild(tarea);
   } catch(e) {
	  //for IE add <div id="htmlconverter" style="display:none;"></div> to the page
	  document.getElementById("htmlconverter").innerHTML = '<textarea id="innerConverter">' + str + '</textarea>';
	  var content = document.getElementById("innerConverter").value;
	  document.getElementById("htmlconverter").innerHTML = "";
	  return content;
   }
}

function setProductThumbHeight()
{
	var ImageBoxDiv = $('.Content .ProductList .ProductImage');
	var ImageListDiv = $('.Content .ProductList:not(.List) li');
	var CurrentListHeight = ImageListDiv.height();
	var ProductImageMargin = ImageBoxDiv.css('margin-left')*2;
/*
	ImageBoxDiv.height(ThumbImageHeight);
	ImageBoxDiv.width(ThumbImageWidth);
	ImageBoxDiv.css('line-height', ThumbImageHeight+'px');
*/

	var ImageBoxHeight = ThumbImageHeight;

	if (parseInt(ImageBoxDiv.css("padding-top"), 10)) {
		ImageBoxHeight += parseInt(ImageBoxDiv.css("padding-top"), 10) * 2; //Total Padding Width
	}

	if(parseInt(ImageBoxDiv.css("margin-top"), 10)) {
		ImageBoxHeight += parseInt(ImageBoxDiv.css("margin-top"), 10) * 2; //Total Margin Width
	}

	if (parseInt(ImageBoxDiv.css("borderTopWidth"), 10)) {
		ImageBoxHeight += parseInt(ImageBoxDiv.css("borderTopWidth"), 10) * 2; //Total Border Width
	}

	ImageBoxDiv.height(ImageBoxHeight);
	ImageBoxDiv.width(ThumbImageWidth);
	if ($.browser.msie && $.browser.version >= 7 && $.browser.version < 8) {
		// this is a specific browser check because this fix is only applicable for ie7
		ImageBoxDiv.css('line-height', ImageBoxHeight+'px');
	}


	//calculate the new list container width based on the difference between the thumb image and default thumb size
	var ImageListWidth = ImageListDiv.width() + (ThumbImageWidth-120);
	ImageListDiv.width(ImageListWidth);

//	var ImageListHeight = ImageListDiv.height() + (ImageBoxDiv.height() - 120);
//	ImageListDiv.height(ImageListHeight);



	$('.Content .ProductList.List .ProductDetails').css('margin-left',ThumbImageWidth+2+'px');
	$('.Content .ProductList.List li').height(Math.max(CurrentListHeight, ThumbImageHeight));
}

// Dummy JS object to hold language strings.
var lang = {
};

// IE 6 doesn't support the :hover selector on elements other than links, so
// we use jQuery to work some magic to get our hover styles applied.
if(document.all) {
	var isIE7 = /*@cc_on@if(@_jscript_version>=5.7)!@end@*/false;
	if(isIE7 == false) {
		$(document).ready(function() {
			$('.ProductList li').hover(function() {
				$(this).addClass('Over');
			},
			function() {
				$(this).removeClass('Over');
			});
			$('.ComparisonTable tr').hover(function() {
				$(this).addClass('Over');
			},
			function() {
				$(this).removeClass('Over');
			});
		});
	}
	$('.ProductList li:last-child').addClass('LastChild');
}

function ShowLoadingIndicator() {
	if (typeof(disableLoadingIndicator) != 'undefined' && disableLoadingIndicator) {
		return;
	}
	var width = $(window).width();
	var position = $('#Container').css('position');
	if (position == 'relative') {
		width = $('#Container').width();
	}

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
	$('#AjaxLoading').css('left', parseInt((width-150)/2)+"px");
	$('#AjaxLoading').show();
}

function HideLoadingIndicator() {
	$('#AjaxLoading').hide();
}


var loadedImages = {};

// Ensure that all product lists are the same height
function setProductListHeights(imgName, className) {
	// hack job putting this here but it needs to be reused by search ajax pager
	if (typeof(DesignMode) != 'undefined') {
		return;
	}

	if (typeof imgName != 'undefined') {
		if (typeof loadedImages[imgName] != 'undefined') {
			return;
		}

		loadedImages[imgName] = true;
	}

	setProductThumbHeight();

	/**
	 * Sets the height of the elements passed in to match that of the one that
	 * has the greatest height.
	 *
	 * @param ele The element(s) to adjust the height for.
	 * @return void
	 */
	function setHeight(ele) {
		var ele       = $(ele),
			maxHeight = 0;

		ele
			// reset the height just in case it was set by the stylesheet so
			// we can detect it
			.css('height', 'auto')
			// get the one with the greatest height
			.each(function() {
				if ($(this).height() > maxHeight) {
					maxHeight = $(this).height();
				}
			})
			// and set them all to the greatest height
			.css('height', maxHeight);
	}

	if (!className) {
		className = '.Content';
	}

	setHeight(className + ' .ProductList:not(.List) li .ProductDetails');

	if (typeof imgName != 'undefined') {
		setHeight(className + ' .ProductList:not(.List) li .ProductPriceRating:has(img[src$='+imgName+'])');
	}

	setHeight(className + ' .ProductList:not(.List) li');
}


function fastCartAction(event) {
	var url = '';

	// Supplied URL
	if (typeof(event) == 'string') {
		var url = event;
	}
	// Event raised from a clicked link
	else if (event.type == 'click' && $(event.target).is('a')) {
		event.preventDefault();
		var url = event.target.href;
	}
	// 'Add' button on product details page
	else if (event.type == 'submit') {
		var url = $('#productDetailsAddToCartForm').attr('action') + '?' + $('#productDetailsAddToCartForm').serialize();
	}

	// Make sure a valid URL was supplied
	if (!url || url.indexOf('cart.php') == -1) {
		return false;
	}

	_showFastCart(url + '&fastcart=1');
	return false;

}
function _showFastCart(url) {

	// strip protocol from url to fix cross protocol ajax access denied problem
	url = url.replace(/^http[s]{0,1}:\/\/[^\/]*\/?/, '/');

	$.iModal.close();
	$.iModal({
		type: 'ajax',
		width: 700,
		url: url,
		closeTxt: true,
		onAjaxError: function() {
			window.location.href = url.replace(/&fastcart=1/, '');
		},
		onShow: function() {
			$("#fastCartSuggestive a[href*='cart.php?action=add']").unbind('click');
			$("#fastCartSuggestive a[href*='cart.php?action=add']").click(function(event) {
				// attach action to suggestive products' link
				fastCartAction(event);
			});

			var itemTxt = $('#fastCartNumItemsTxt').html();
			if (itemTxt) {
				// update the view cart item count on top menu
				$('.CartLink span').html('(' + itemTxt + ')');
			}
			setProductListHeights(null, '.fastCartContent');
			$('.fastCartContent .ProductList:not(.List) li').width(ThumbImageWidth);
		},
		onClose: function() {
			if (window.location.href.match(config.ShopPath + '/cart.php')) {
				// reload if we are on the cart page
				$('#ModalContainer').remove();
				window.location = window.location.href
			} else {
				$('#ModalContainer').remove();
			}
		}
	});
}

/* REQ11022 JIB: 
 * Funcion para abrir la ventana emergente que te envia a "ProductStockDetailP.php"
 * y manda como parametro el ID del producto que se esta visualizando
 */
function ProductShowStockDetail(pID, optionIds) {

	$.iModal.close();
	$.iModal({
		type: 'ajax',
		width: 700,  //700
		url: config.ShopPath+'/ProductStockDetailP.php?prodID='+pID+'&optionIds='+optionIds,
		closeTxt: true,
		onAjaxError: function() {
		},
		onShow: function() {
		},
		onClose: function() {
			$('#ModalContainer').remove();
		}
	});
}


/**
* Adds a script tag to the DOM that forces a hit to tracksearchclick. Should be called by a mousedown event as calling it by a click event can sometimes be cancelled by the browser navigating away from the page.
*/
function isc_TrackSearchClick (searchId) {
	if (!searchId) {
		return;
	}

	$('#SearchTracker').remove();

	var trackurl = 'search.php?action=tracksearchclick&searchid=' + encodeURIComponent(searchId) + '&random=' + Math.random();

	var script = document.createElement('script');
	script.type = "text/javascript";
	script.src = trackurl;
	script.id = "SearchTracker";

	window.document.body.appendChild(script);
}

$(document).ready(function() {
	if($('.Rating img').length > 0) {
		$('.Rating img').each(function() {
			if($(this).height() == 0) {
				$(this).load(function() {
					// Load rating img and find the tallest product.
					var imgName = $(this).attr('src').split('/');
					var imgKey = imgName.length-1;
					setProductListHeights(imgName[imgKey]);
				});
			} else {
				setProductListHeights();
				return false;
			}
		});
	} else {
		setProductListHeights();
	}

	$('.InitialFocus').focus();
	$('table.Stylize tr:first-child').addClass('First');
	$('table.Stylize tr:last-child').addClass('Last');
	$('table.Stylize tr td:odd').addClass('Odd');
	$('table.Stylize tr td:even').addClass('Even');
	$('table.Stylize tr:even').addClass('Odd');
	$('table.Stylize tr:even').addClass('Even');

	$('.TabContainer .TabNav li').click(function() {
		$(this).parent('.TabNav').find('li').removeClass('Active');
		$(this).parents('.TabContainer').find('.TabContent').hide();
		$(this).addClass('Active');
		$(this).parents('.TabContainer').find('#TabContent'+this.id).show();
		$(this).find('a').blur();
		return false;
	});

	$('html').ajaxStart(function() {
		ShowLoadingIndicator();
	});

	$('html').ajaxComplete(function() {
		HideLoadingIndicator();
	});

	// generic checkbox => element visibility toggle based on id of checkbox and class names of other elements
	$('.CheckboxTogglesOtherElements').live('change', function(event){
		if (!this.id) {
			return;
		}

		var className = 'ShowIf_' + this.id + '_Checked';
		var elements = $('.' + className);

		if (this.checked) {
			// easy, show matching elements
			elements.show();
			return;
		}

		// if not checked it's a little more tricky -- only hide elements if they are not showing for multiple check boxes
		var classExpression = /^ShowIf_(.+)_Checked$/;
		elements.each(function(){
			var $$ = $(this);

			// before hiding this element, check its classes to see if it has another ShowIf_?_Checked - if it does, see if that class points to a checked box
			var classes = $$.attr('class').split(/\s+/);
			var checked = false;
			$.each(classes, function(key,value){
				if (value === className) {
					// we're processing this class already so we know it's unchecked - ignore it
					return;
				}

				var result = classExpression.exec(value);
				if (result === null) {
					// not a ShowIf_?_Class
					return;
				}

				var id = result[1];
				if ($('#' + id ).attr('checked')) {
					// found a checked box
					checked = true;
					return false;
				}
			});

			if (!checked) {
				// found no checkbox that should be keeping this element visible
				$$.hide();
			}
		});

	}).change();
});

var config = {};
