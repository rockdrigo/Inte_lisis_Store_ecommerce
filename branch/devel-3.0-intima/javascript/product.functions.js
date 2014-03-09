/* Product Variations */
var baseProduct = {};

function updateSelectedVariation(parent, variation, id) {
	if(typeof(parent) == 'undefined') {
		parent = $('body');
	}
	else {
		parent = $(parent);
	}

	if(typeof(baseProduct.price) == 'undefined') {
		if($('.AddCartButton', parent).css('display') == "none") {
			var cartVisible = false;
		}
		else {
			var cartVisible = true;
		}

		var stockMessageVisible = false;
		if($('.OutOfStockMessage', parent).css('display') != 'none') {
			stockMessageVisible = true;
		}

		var price;
		$('.VariationProductPrice', parent).each(function(){
			var $$ = $(this);
			if ($$.is('input')) {
				price = $$.val();
			} else {
				price = $$.html();
			}
		});

		baseProduct = {
			saveAmount: $.trim($('.YouSaveAmount', parent).html()),
			price: $.trim(price),
			sku: $.trim($('.VariationProductSKU', parent).html()),
			weight: $.trim($('.VariationProductWeight', parent).html()),
			thumb: $.trim($('.ProductThumbImage img', parent).attr('src')),
			cartButton: cartVisible,
			stockMessage: stockMessageVisible,
			stockMessageText: $('.OutOfStockMessage', parent).html()
		};
	}

	// Show the defaults again
	if(typeof(variation) == 'undefined') {
		$('.WishListVariationId', parent).val('');
		$('.CartVariationId', parent).val('');
		if(baseProduct.saveAmount) {
			$('.YouSave', parent).show();
			$('.YouSaveAmount').html(baseProduct.saveAmount);
		} else {
			$('.YouSave', parent).hide();
		}
		$('.VariationProductPrice', parent).each(function(){
			var $$ = $(this);
			if ($$.is('input')) {
				$$.val(baseProduct.price);
			} else {
				$$.html(baseProduct.price);
			}
		});
		$('.VariationProductSKU', parent).html(baseProduct.sku);
		$('.VariationProductWeight', parent).html(baseProduct.weight);
		$('.ProductThumbImage img', parent).attr('src', baseProduct.thumb);
		$(parent).attr('currentVariation', '');
		$(parent).attr('currentVariationImage', '')
		if(baseProduct.sku == '') {
			$('.ProductSKU', parent).hide();
		}
		if(baseProduct.cartButton) {
			$('.AddCartButton', parent).show();
			if(typeof ShowAddToCartQtyBox != 'undefined' && ShowAddToCartQtyBox=='1') {
				$('.QuantityInput', parent).show();
			}
		}

		if(baseProduct.stockMessage) {
			$('.OutOfStockMessage', parent)
				.show()
				.html(baseProduct.stockMessageText)
			;
		}
		else {
			$('.OutOfStockMessage').hide();
		}

		$('.InventoryLevel', parent).hide();
	}
	// Otherwise, showing a specific variation
	else {
		$('.WishListVariationId', parent).val(id);
		$('.CartVariationId', parent).val(id);
		$('.VariationProductPrice', parent).each(function(){
			var $$ = $(this);
			if ($$.is('input')) {
				$$.val(variation.price.replace(/[^0-9\.,]/g, ''));
			} else {
				$$.html(variation.price);
			}
		});
		if(variation.sku != '') {
			$('.VariationProductSKU', parent).html(variation.sku);
			$('.ProductSKU', parent).show();
		}
		else {
			$('.VariationProductSKU', parent).html(baseProduct.sku);
			if(baseProduct.sku) {
				$('.ProductSKU', parent).show();
			}
			else {
				$('.ProductSKU', parent).hide();
			}
		}
		$('.VariationProductWeight', parent).html(variation.weight);
		if(variation.instock == true) {
			$('.AddCartButton', parent).show();
			if(typeof ShowAddToCartQtyBox != 'undefined' && ShowAddToCartQtyBox=='1') {
				$('.QuantityInput', parent).show();
			}
			$('.OutOfStockMessage', parent).hide();
		}
		else {
			$('.AddCartButton, .QuantityInput', parent).hide();
			$('.OutOfStockMessage', parent).html(lang.VariationSoldOutMessage);
			$('.OutOfStockMessage', parent).show();
		}
		if(variation.thumb != '') {
			ShowVariationThumb = true;
			$('.ProductThumbImage img', parent).attr('src', variation.thumb);
			$(parent).attr('currentVariation', id);
			$(parent).attr('currentVariationImage', variation.image);

			$('.ProductThumbImage a').attr("href", variation.image);
		}
		else {
			$('.ProductThumbImage img', parent).attr('src', baseProduct.thumb);
			$(parent).attr('currentVariation', '');
			$(parent).attr('currentVariationImage', '')
		}
		if(variation.stock && parseInt(variation.stock)) {
			$('.InventoryLevel', parent).show();
			$('.VariationProductInventory', parent).html(variation.stock);
		}
		else {
			$('.InventoryLevel', parent).hide();
		}
		if(variation.saveAmount) {
			$('.YouSave', parent).show();
			$('.YouSaveAmount').html(variation.saveAmount);
			$('.RetailPrice').show();
		} else {
			$('.YouSave', parent).hide();
			$('.RetailPrice').hide();
		}
		
		if(variation.deliveryDateFromStatus){
			$('.DeliveryDateFromStatus').show();
			$('.DeliveryDateFromStatusText').html(variation.deliveryDateFromStatus);
		}
		else {
			$('.DeliveryDateFromStatus').hide();
		}
	}
}

function ChangeLayerImage(parent, variation, id) {
	if(typeof(parent) == 'undefined') {
		parent = $('body');
	}
	else {
		parent = $(parent);
	}
	
	if(variation.thumb != '') {
		ShowVariationThumb = true;
		$('.ProductThumbImage img', parent).attr('src', variation.thumb);
		$(parent).attr('currentVariation', id);
		$(parent).attr('currentVariationImage', variation.image);

		$('.ProductThumbImage a').attr("href", variation.zoom);
	}
	else {
		$('.ProductThumbImage img', parent).attr('src', baseProduct.thumb);
		$(parent).attr('currentVariation', '');
		$(parent).attr('currentVariationImage', '')
	}
}

function GenerateProductTabs()
{
	var ActiveTab = 'Active';
	var ProductTab = '';
	var TabNames = new Array();

	TabNames['ProductDescription'] = lang.Description;
	TabNames['ProductWarranty'] = lang.Warranty;
	TabNames['ProductOtherDetails'] = lang.OtherDetails;
	TabNames['SimilarProductsByTag'] = lang.ProductTags;
	TabNames['ProductByCategory'] = lang.SimilarProducts;
	TabNames['ProductReviews'] = lang.Reviews;
	TabNames['ProductVendorsOtherProducts'] = lang.OtherProducts;
	TabNames['ProductVideos'] = lang.ProductVideos;
	TabNames['SimilarProductsByCustomerViews'] = lang.SimilarProductsByCustomerViews;
	$('.Content .Moveable').each (

		function() {
			if(this.id != 'ProductBreadcrumb' &&  this.id != 'ProductDetails') {

				if (TabNames[this.id]) {

					TabName = TabNames[this.id];
					ProductTab += '<li id="'+this.id+'_Tab" class="'+ActiveTab+'"><a onclick="ActiveProductTab(\''+this.id+'_Tab\'); return false;" href="#">'+TabName+'</a></li>';

					if (ActiveTab == '')
					{
						$('#'+this.id).hide();
					}
					$('#'+this.id).removeClass('Moveable');
					ActiveTab = "";
				}
			}
		}
	);

	if (ProductTab != '')
	{
		$('#ProductTabsList').html(ProductTab);
	}
}

function ActiveProductTab(TabId)
{
	var CurTabId = $('#ProductTabs .Active').attr('id');
	var CurTabContentId = CurTabId.replace('_Tab','');

	$('#ProductTabs .Active').removeClass('Active');

	$('#'+CurTabContentId).hide();

	$('#'+TabId).addClass('Active');

	var NewTabContentId = TabId.replace('_Tab','');
	$('#'+NewTabContentId).show();

}

function CheckEventDate() {

	var result = true;

	if(typeof(eventDateData) == 'undefined') {
		return true;
	}

	if ($('#EventDateDay').val() == -1 || $('#EventDateMonth').val() == -1 || $('#EventDateYear').val() == -1) {
		alert(eventDateData['invalidMessage']);
		return false;
	}

	if (eventDateData['type'] == 1) {
		if (new Date($('#EventDateYear').val()+'/'+$('#EventDateMonth').val()+'/'+$('#EventDateDay').val()) > new Date(eventDateData['compDateEnd'])
		 || new Date($('#EventDateYear').val()+'/'+$('#EventDateMonth').val()+'/'+$('#EventDateDay').val()) < new Date(eventDateData['compDate'])
		) {
			result = false;
		}

	} else if (eventDateData['type'] == 2) {
		if (new Date($('#EventDateYear').val()+'/'+$('#EventDateMonth').val()+'/'+$('#EventDateDay').val()) < new Date(eventDateData['compDate'])) {
			result = false;
		}

	} else if (eventDateData['type'] == 3) {
		if (new Date($('#EventDateYear').val()+'/'+$('#EventDateMonth').val()+'/'+$('#EventDateDay').val()) > new Date(eventDateData['compDate'])) {
			result = false;
		}
	} else {
		result = true;
	}

	if (!result) {
		alert(eventDateData['errorMessage']);
	}
	return result;
}

function selectCurrentVideo (videoId) {
	$('.currentVideo').removeClass('currentVideo');
	$('#video_' + videoId).addClass('currentVideo');
}

function showVideoPopup(videoId) {
	var l = (screen.availWidth/2)-250;
	var t = (screen.availHeight/2)-200;
	window.open(config.ShopPath + "/productimage.php?video_id="+videoId, "imagePop", "toolbar=0,scrollbars=1,location=0,statusbar=1,menubar=0,resizable=1,width=530,height=430,top="+t+",left="+l);
}


function showProductThumbImage(ThumbIndex) {
	$('.ProductThumbImage img').attr('src', ThumbURLs[ThumbIndex]);
	$('.ProductThumbImage img').attr('alt', ProductImageDescriptions[ThumbIndex]);


	CurrentProdThumbImage = ThumbIndex;
	ShowVariationThumb = false;
	highlightProductTinyImage(ThumbIndex);
	if(ShowImageZoomer) {
		$('.ProductThumbImage a').attr("href", ZoomImageURLs[ThumbIndex]);
		$('.ProductThumbImage a').css({'cursor':'pointer'});
	}
}

function highlightProductTinyImage(ThumbIndex) {
	$('.ProductTinyImageList li').css('border', '1px solid gray');
	$('.ProductTinyImageList li .TinyOuterDiv').css('border', '2px solid white');

	$('#TinyImageBox_'+ThumbIndex).css('border', '1px solid #075899');
	$('#TinyImageBox_'+ThumbIndex+' .TinyOuterDiv').css('border', '2px solid #075899');
}


function initiateImageCarousel() {

	if(!$('.ImageCarouselBox').is(':visible')) {
		var seeMoreImageHeight = $("#ProductDetails .SeeMorePicturesLink").height();
		$("#ProductDetails .ProductThumb").width(ProductThumbWidth+20);
		$("#ProductDetails .ProductThumb").height(ProductThumbHeight+seeMoreImageHeight+10);
		return false;
	}

	highlightProductTinyImage(0);

	var carouselHeight = $("#ProductDetails .ProductTinyImageList").height();
	$("#ProductDetails .ProductThumb").width(ProductThumbWidth+20);
	$("#ProductDetails .ProductThumb").height(ProductThumbHeight+carouselHeight+10);

	var CarouselImageWidth = $('#ProductDetails .ProductTinyImageList > ul > li').outerWidth(true);

	$("#ImageScrollPrev").show();
	var CarouselButtonWidth =  $("#ProductDetails #ImageScrollPrev").outerWidth(true);
	$("#ImageScrollPrev").hide();

	var MaxCarouselWidth = $("#ProductDetails .ProductThumb").width() - (CarouselButtonWidth * 2);
	var MaxVisibleTinyImages = Math.floor(MaxCarouselWidth/CarouselImageWidth);

	if (MaxVisibleTinyImages<=0) {
		MaxVisibleTinyImages = 1;
	}

	var visible = MaxVisibleTinyImages;

	if (ThumbURLs.length <= MaxVisibleTinyImages) {
		visible = ThumbURLs.length;
		CarouselButtonWidth = 0;
	} else {
		$("#ImageScrollPrev").show();
		$("#ImageScrollNext").show();
	}

	var scroll = Math.round(visible/2);

	if($('#ProductDetails .ProductTinyImageList li').length > 0) {
		$("#ProductDetails .ProductTinyImageList").jCarouselLite({
			btnNext: ".next",
			btnPrev: ".prev",
			visible: visible,
			scroll: scroll,
			circular: false,
			speed: 200
		});
	}

	// end this floating madness
	$('#ImageScrollNext').after('<br clear="all" />');

	// pad the carousel box to center it
	$('#ProductDetails .ImageCarouselBox').css('padding-left', Math.floor(($("#ProductDetails .ProductThumb").width() - (visible * CarouselImageWidth) - (2 * CarouselButtonWidth)) / 2));

	// IE 6 doesn't render the carousel properly, the following code is the fix for IE6
	if($.browser.msie && $.browser.version.substr(0,1) == 6) {
		$("#ProductDetails .ProductTinyImageList").width($("#ProductDetails .ProductTinyImageList").width()+4);
		var liHeight = $("#ProductDetails .ProductTinyImageList li").height();
		$("#ProductDetails .ProductTinyImageList").height(liHeight+2);
	}
}


function initiateImageZoomer(){
	if(ShowImageZoomer != 1) {
		return false;
	}
	var options = {
		zoomWidth: 380,
		zoomHeight: 300,
		xOffset: 10,
		position: "right",
		preloadImages: false,
		showPreload:false,
		title: false,
		preloadImages: false,
		showPreload: false,
		cursor: 'pointer'

	};

	$('.ProductThumbImage a').jqzoom(options);
}

function variationActivateExtraFields(numFields) {
	$('.LayerField').hide();
	$('.SelectBoxLayer').each(function() {
		this.value = "";
	});

	if(isNaN(numFields)) return;
	var DetailRowSelects = $('.LayerField');
	
	var i;
	for(i=0;i<DetailRowSelects.length;i++)
	{
		if(DetailRowSelects[i].childNodes[1].innerHTML.search(/ingrediente extra/i)) {
			DetailRowSelects[i].style.display="";
			numFields--;
		}
		if(numFields == 0) break;
	}
}

//REQ10046 - Se actualiza la tabla de existencias por sucursal si es que se presiono el boton, o si se cambiaron las opciones
function updateProductDetailStock(data) {
	$('#ProductStockDetailTable').children().remove();
	//$('.OutOfStockMessage').append('<table id="ProductStockDetailTable" class="ProductStockDetailTable"></table>');
	if(!data.error){
		$('.ProductStockDetailTable').append('<th width="20%">Sucursal</th><th width="20%">Cantidad</th><th width="60%">Contacto</th>');
		$.each(data, function(key, val) {
			$('.ProductStockDetailTable').append('<tr><td>'+val.SucursalDetalles.Nombre+'</td><td>'+val.Cantidad+'</td><td>'+val.SucursalDetalles.Contacto+'</td></tr>');
		}
		);
	}
	else $('.ProductStockDetailTable').append('<tr><td>'+data.error+'</td></tr>');
}

function activateFieldsByName(fieldId, fieldValue){
	
	$.getJSON(
			config.AppPath + '/remote.php?w=GetFieldsActivatedByName&productId=' + productId + '&fieldId=' + fieldId + '&fieldValue=' + fieldValue,
			function(data) {
				$('div.ActivatedByName'+data.fieldId).show();
			}
		);
}