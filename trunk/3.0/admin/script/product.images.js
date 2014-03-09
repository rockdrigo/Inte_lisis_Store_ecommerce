ProductImages = {

	// for storing a list of product images for the current product
	images: []
};

ProductImages.refreshDeleteSelectedButton = function () {
	if (ProductImages.images.length) {
		$('#productImagesDeleteSelected').attr('disabled', false);
	} else {
		$('#productImagesDeleteSelected').attr('disabled', true);
	}
};

ProductImages.deleteImages = function (deleteImages) {

	var data = {
		productimageshandler: 'deleteMultiple',
		'images[]': deleteImages
	};

	var productId = $('#productId').val();
	if (productId) {
		data.product = productId;
	} else {
		data.hash = $('#productHash').val();
	}

	var options = {
		cache: false,
		type: 'POST',
		url: 'remote.php?w=productimages',
		dataType: 'xml',
		data: data,
		success: function (xml) {
			var nodes;

			var errorhtml = '';
			var warninghtml = '';

			// check for images that weren't deleted due to errors
			$('error', xml).each(function(){
				var imageId = parseInt($(this).attr('image'), 10);
				var image = ProductImages.getImageById(imageId);
				if (!image) {
					return;
				}

				image.unfadeRow();

				errorhtml += '<li>' + $.htmlEncode($(this).text()) + '</li>';
			});

			if (errorhtml) {
				errorhtml = '<div>' + $.htmlEncode(lang.ProductImagesDeletedErrors) + '</div><ul>' + errorhtml + '</ul>';
			}

			// check for images that were deleted but had warning messages
			$('warning', xml).each(function(){
				var imageId = parseInt($(this).attr('image'), 10);
				var image = ProductImages.getImageById(imageId);
				if (!image) {
					return;
				}

				warninghtml += '<li>' + $.htmlEncode($(this).text()) + '</li>';

				// image confirmed as deleted, remove it from the dom
				image.removeRow();
			});

			if (warninghtml) {
				warninghtml = '<div>' + $.htmlEncode(lang.ProductImagesDeletedWarnings) + '</div><ul>' + warninghtml + '</ul>';
			}

			// check for confirmed deleted images
			$('delete', xml).each(function(){
				var imageId = parseInt($(this).attr('image'), 10);
				var image = ProductImages.getImageById(imageId);
				if (!image) {
					return;
				}

				// image confirmed as deleted, remove it from the dom
				image.removeRow();
			});

			$('thumbnail', xml).each(function(){
				var imageId = parseInt($(this).attr('image'), 10);
				var image = ProductImages.getImageById(imageId);
				if (!image) {
					return;
				}

				// image reported as new thumbnail by server -- set it but don't update it
				image.setBaseThumbnail(true, false);
			});

			ProductImages.refreshDeleteSelectedButton();

			if (errorhtml || warninghtml) {
				window.scrollTo(0, 0);
				display_error('MainMessage', errorhtml + warninghtml);
			} else {
				display_success('MainMessage', $.htmlEncode(lang.ProductImagesDeleted));
			}
		}
	};

	$.ajax(options);

	// delete each image from the dom without triggering a server update since we've sent one as a batch above
	for (var i = deleteImages.length; i--;) {
		ProductImages.getImageById(deleteImages[i]).deleteImage(false);
	}
};

// returns an instance of ProductImages.Image for the given product image id
ProductImages.getImageById = function (id) {
	for (var i = ProductImages.images.length; i--;) {
		if (ProductImages.images[i].getId() == id) {
			return ProductImages.images[i];
		}
	}
};

// return an array of ProductImages.Image objects in the order they are configured on screen
ProductImages.getImagesInOrder = function () {
	ProductImages.images.sort(function(a,b){
		return a.getSort() - b.getSort();
	});

	return ProductImages.images;
};

// return a array of image ids in the order they are configured on screen
ProductImages.getImageIdsInOrder = function () {
	var images = ProductImages.getImagesInOrder();
	var ids = [];
	for (var i = 0; i < images.length; i++) {
		ids.push(images[i].getId());
	}
	return ids;
};

// handles the selection and insertion of images that already exist for other products and in the store image manager
ProductImages.useImageFromGallery = {
	// store the ID's of selected images
	selectedImages: [],

	searchLength: 0,

	searchRunning: false,

	lastSearchTerm: '',

	// makes an ajax call to load 10 images from other products that can be selected
	loadImagesFromProducts: function () {
		$('.ImageLoading', $('#ModalContainer')).show();
		$('#UseImageFromGalleryImagesList', $('#ModalContainer')).hide();

		$.getJSON(
			'remote.php?remoteSection=products&w=getsourceproductimages',
			ProductImages.useImageFromGallery.loadImagesFromSourceToContainer
		);
	},

	// clear the search field
	clearImageSearchField: function (evt) {
		$('#ProductImagesSearch', $('#ModalContainer')).each(function() {
			$(this).val($.data(this, "origValue"));
			$(this).addClass('exampleSearchText');
		});

		$('#ClearImageSearch', $('#ModalContainer')).hide();

		ProductImages.useImageFromGallery.changeImageSource();
	},

	// function bound to when a character is typed in the search field
	setSearchTimeout: function (evt) {
		if(evt.keyCode == 13) {
			ProductImages.useImageFromGallery.searchRunning = false;
			ProductImages.useImageFromGallery.searchLength = $('#ProductImagesSearch', $('#ModalContainer')).val().length;
			ProductImages.useImageFromGallery.searchProductImages();
		} else {
			ProductImages.useImageFromGallery.searchLength = $('#ProductImagesSearch', $('#ModalContainer')).val().length;
			setTimeout(ProductImages.useImageFromGallery.searchProductImages, 1000);
		}
	},

	searchProductImages: function () {

		ProductImages.useImageFromGallery.selectedImages = [];
		$('#UseSelectedImages', $('#ModalContainer')).attr('disabled', 'disabled');

		$('#ClearImageSearch', $('#ModalContainer')).show();

		if(ProductImages.useImageFromGallery.searchLength != $('#ProductImagesSearch', $('#ModalContainer')).val().length
		|| ProductImages.useImageFromGallery.searchRunning
		|| $('#ProductImagesSearch', $('#ModalContainer')).val().length < 2
		|| $('#ProductImagesSearch', $('#ModalContainer')).val() == ProductImages.useImageFromGallery.lastSearchTerm) {
			return;
		}

		ProductImages.useImageFromGallery.lastSearchTerm = $('#ProductImagesSearch', $('#ModalContainer')).val();
		ProductImages.useImageFromGallery.searchRunning = true;

		var action = 'getsourceproductimages';
		if ($('#ProductImageSource', $('#ModalContainer')).val() == 'imagemanager') {
			action = 'getsourceimagemanager';
		}

		$.getJSON(
			'remote.php?remoteSection=products&w=' + action + '&searchterm=' + encodeURIComponent($('#ProductImagesSearch', $('#ModalContainer')).val()),
			function(json) {
				ProductImages.useImageFromGallery.loadImagesFromSourceToContainer(json);
				ProductImages.useImageFromGallery.searchRunning = false;
			}
		);
	},

	// makes an ajax call to load the the first 10 images from the image manager
	loadImagesFromImageGallery: function () {
		$('.ImageLoading', $('#ModalContainer')).show();
		$('#UseImageFromGalleryImagesList', $('#ModalContainer')).hide();
		$('#ChangeImageSourceButton', $('#ModalContainer')).bind('click', ProductImages.useImageFromGallery.changeImageSource);
		$.getJSON(
			'remote.php?remoteSection=products&w=getsourceimagemanager',
			ProductImages.useImageFromGallery.loadImagesFromSourceToContainer
		);
	},

	// the callback function that is used when retrieving images, used by both the products and image manager sources
	// this function will place the images into the appropriate container and setup all the events
	loadImagesFromSourceToContainer: function(json) {
		if(!json.success) {
			alert(json.message);
			return;
		}

		if(json.images.length == 0) {
			$('#UseImageFromGalleryImagesList', $('#ModalContainer')).show();
			$('#UseImageFromGalleryImagesList', $('#ModalContainer')).addClass('NoProductImagesMessage');
			$('#UseImageFromGalleryImagesList', $('#ModalContainer')).html(json.message);
			$('#ImageGalleryPaging', $('#ModalContainer')).html('');
			$('.ImageLoading', $('#ModalContainer')).hide();
			return;
		} else {
			$('#UseImageFromGalleryImagesList', $('#ModalContainer')).removeClass('NoProductImagesMessage');
		}

		// ideally this HTML would be templated somewhere
		var imageHTML = '<div class="GalleryImageContainer"><div class="galleryImage" id="{image.id}"><img width="{image.thumbwidth}" height="{image.thumbheight}" src="{image.url}" /><div class="overlaySelect" style="margin-top:-{image.margintop}px;"></div></div><span class="ProductName">{image.productname}</span><a class="ViewLarger thickbox" href="{image.zoom}">' + lang.ImageFromGalleryViewLarger + '</a></div>';
		var allImagesHTML = '';
		var image = {};

		for(i=0;i<json.images.length;i++) {
			image = json.images[i];
			allImagesHTML += imageHTML.replace('{image.url}', '../product_images/' + image['url'])
									  .replace(/\{image\.id\}/g, image['id'])
									  .replace('{image.zoom}', image['zoom'])
									  .replace('{image.zoomwidth}', image['zoomwidth'])
									  .replace('{image.zoomheight}', image['zoomheight'])
									  .replace('{image.thumbwidth}', image['thumbwidth'])
									  .replace('{image.thumbheight}', image['thumbheight'])
									  .replace('{image.margintop}', image['thumbheight'])
									  .replace('{image.productname}', image['productname']);
		}

		$('.ImageLoading', $('#ModalContainer')).hide();

		// load the paging and bind all the links so they make an ajax call and do not change the page
		$('#ImageGalleryPaging', $('#ModalContainer')).html(json.paging);
		$('#ImageGalleryPaging a', $('#ModalContainer')).bind('click', ProductImages.useImageFromGallery.onClickPaging);
		$('#UseImageFromGalleryImagesList', $('#ModalContainer')).show();
		$('#UseImageFromGalleryImagesList', $('#ModalContainer')).html(allImagesHTML);
		tb_init('#ModalContainer .thickbox');

		$('#UseImageFromGalleryImagesList .galleryImage', $('#ModalContainer')).bind('click', ProductImages.useImageFromGallery.onClickImage);

		for(i=0;i<ProductImages.useImageFromGallery.selectedImages.length;i++) {
			$('#' + ProductImages.useImageFromGallery.selectedImages[i], $('#ModalContainer')).addClass('galleryImageSelected');
		}
	},

	// this function is called when the user changes the image source -- an be either image manager or other products
	changeImageSource: function () {
		if($('#ProductImageSource', $('#ModalContainer')).val() == 'products') {
			ProductImages.useImageFromGallery.loadImagesFromProducts();
		} else {
			ProductImages.useImageFromGallery.loadImagesFromImageGallery();
		}
	},

	// callback that is activated when the modal window is closed
	onShowModal: function () {

		$('#ChangeImageSourceButton', $('#ModalContainer')).bind('click', ProductImages.useImageFromGallery.changeImageSource);
		$('#UseSelectedImages', $('#ModalContainer')).bind('click', ProductImages.useImageFromGallerySubmit);
		$('#ClearImageSearchLink', $('#ModalContainer')).bind('click', ProductImages.useImageFromGallery.clearImageSearchField);
		$('#ProductImagesSearch', $('#ModalContainer')).bind('keyup', ProductImages.useImageFromGallery.setSearchTimeout);

		$('#ClearImageSearch', $('#ModalContainer')).hide();

		$('#ProductImagesSearch', $('#ModalContainer')).each(function() {
			$.data(this, "origValue", $(this).val());
		});

		$('#ProductImagesSearch', $('#ModalContainer')).bind('focus', function() {
			if($.data(this, "origValue") == $(this).val()) {
				$(this).val('');
				$(this).removeClass('exampleSearchText');
			}
		});

		$('#ProductImagesSearch', $('#ModalContainer')).bind('blur', function() {
			if($(this).val() == '') {
				$(this).val($.data(this, "origValue"));
				$(this).addClass('exampleSearchText');
			}
		});

		ProductImages.useImageFromGallery.loadImagesFromProducts();
	},

	// callback that is activated when the modal window is closed
	onCloseModal: function () {
		ProductImages.useImageFromGallery.selectedImages = [];
		$.iModal.close();
	},

	// callback function for clicking on the paging links, this stops them from acting as normal links and runs an ajax call
	onClickPaging: function (evt) {
		$.getJSON(
			$(this).attr('href'),
			ProductImages.useImageFromGallery.loadImagesFromSourceToContainer
		);
		return false;
	},

	// function called when the user clicks on the 'View Larger' link
	onClickViewLarger: function (imageZoom, imageWidth, imageHeight) {
		var w = window.open('image.htm?margin=50&title=' + encodeURIComponent(lang.ProductImage) + '&image=' + encodeURIComponent(imageZoom), 'productImageZoom' , 'width=' +(imageWidth+110) + ',height=' + (imageHeight+110));
		if (!w) {
			return;
		}
		var windowPositionTop = 0;
		var windowPositionLeft = 0;

		windowPositionTop = (screen.availHeight - (imageHeight+110))/2;
		windowPositionLeft = (screen.availWidth - (imageWidth+110))/2;

		w.moveTo(windowPositionLeft, windowPositionTop);
		w.focus();
	},

	// function called when the user clicks on an image to select it for insertion to the current product
	onClickImage: function (evt) {
		if(ProductImages.useImageFromGallery.selectedImages.in_array($(this).attr('id'))) {
			var newSelected = [];

			for(i=0;i<ProductImages.useImageFromGallery.selectedImages.length;i++) {
				if(ProductImages.useImageFromGallery.selectedImages[i] != $(this).attr('id')) {
					newSelected.push(ProductImages.useImageFromGallery.selectedImages[i]);
				}
			}

			ProductImages.useImageFromGallery.selectedImages = newSelected;
			$(this).removeClass('galleryImageSelected');
		} else {
			$(this).addClass('galleryImageSelected');
			ProductImages.useImageFromGallery.selectedImages.push($(this).attr('id'));
		}

		if(ProductImages.useImageFromGallery.selectedImages.length > 0) {
			$('#UseSelectedImages', $('#ModalContainer')).removeAttr('disabled');
		} else {
			$('#UseSelectedImages', $('#ModalContainer')).attr('disabled', 'disabled');
		}
	},

	// function called when the user clicks 'Use selected images'
	// it sends the list of selected images to the server which ads the images to the current product
	useSelectedImages: function () {

		for(i=0;i<ProductImages.useImageFromGallery.selectedImages.length;i++) {
			sendPOST += 'images[]=' + ProductImages.useImageFromGallery.selectedImages[i] + '&';
		}

		$.post(
			'remote.php?remoteSection=products&w=useselectedimages',
			sendPOST,
			function (json) {
				ProductImages.useImageFromGallery.onCloseModal();
			},
			'JSON'
		);

		return false;
	}

};

ProductImages.useImageFromGallerySubmit = function () {
	var error = false;

	$('#UseSelectedImages', $('#ModalContainer')).attr('disabled', 'disabled');

	if (!ProductImages.useImageFromGallery.selectedImages.length) {
		ProductImages.useImageFromGallery.onCloseModal();
		return false;
	}

	// send urls as ajax
	var data = {
		productimageshandler: 'useSourceImages',
		'images[]': ProductImages.useImageFromGallery.selectedImages
	};

	var productId = $('#productId').val();

	if (productId) {
		data.product = productId;
	} else {
		data.hash = $('#productHash').val();
	}

	var successCallback = function (xml) {
		var nodes;
		var images = 0;

		// the response xml will contain new image information, each separately encoded as json
		$('image', xml).each(function(){
			var image;

			try {
				eval('image = ' + $(this).text() + ';');
			} catch (e) {
				return;
			}

			// if successfully eval'd the image object should be ok to send directly to the ProductImages.Image constructor which will handle displaying of the new image
			new ProductImages.Image(image);
			images++;
		});

		// the response xml will contain error information for each failed url -- combine and display errors
		var errors = [];
		$('error', xml).each(function(){
			errors.push($.htmlEncode($(this).text()));
		});

		var html = '';

		if (images) {
			if (images == 1) {
				html += lang.ProductImageAddedSuccessfully + ' ';
			} else {
				html += lang.ProductImagesAddedSuccessfully.replace(/\%1\$d/g, images) + ' ';
			}
		}

		if (errors.length) {
			html += lang.ProductImageUrlsFailed + '<ul><li>';
			html += errors.join('</li><li>');
			html += '</li></ul>';
			window.scrollTo(0, 0);
			display_error('MainMessage', html);
		} else if (images) {
			display_success('MainMessage', html);
		}
	};

	var completeCallback = function () {
		// close dialog
		ProductImages.useImageFromGallery.onCloseModal();
	};

	var options = {
		cache: false,
		type: 'POST',
		url: 'remote.php?w=productimages',
		dataType: 'xml',
		data: data,
		success: successCallback,
		complete: completeCallback
	};

	$.ajax(options);
};

ProductImages.useImageFromWebDialogSubmit = function () {
	var urlInputs = $('#ModalContentContainer .UseImageFromWebDialogImageUrl');

	var validUrls = [];
	var error = false;

	urlInputs.each(function(){
		var urlInput = $(this);
		var url = String(urlInput.val()).replace(/^\s+|\s+$/, '');

		if (!url) {
			// ignore blank inputs
			return;
		}

		// ideally this would perform some basic client-side validation before sending to server -- but what then? show error instead of sending? field highlighting? there's no room for error presentation in the modal ui design, so the modal is dismissed and errors come back from the server and shown in #MainMessage
		validUrls.push(url);
	});

	if (!validUrls.length) {
		return;
	}

	// send urls as ajax
	var data = {
		productimageshandler: 'newImageWeb',
		'imageurls[]': validUrls
	};

	var productId = $('#productId').val();
	if (productId) {
		data.product = productId;
	} else {
		data.hash = $('#productHash').val();
	}

	var successCallback = function (xml) {
		var nodes;
		var images = 0;

		// the response xml will contain new image information, each separately encoded as json
		$('image', xml).each(function(){
			var image;

			try {
				eval('image = ' + $(this).text() + ';');
			} catch (e) {
				return;
			}

			// if successfully eval'd the image object should be ok to send directly to the ProductImages.Image constructor which will handle displaying of the new image
			new ProductImages.Image(image);
			images++;
		});

		// the response xml will contain error information for each failed url -- combine and display errors
		var errors = [];
		$('error', xml).each(function(){
			var message = $(this).text();
			var url = $(this).attr('url');

			if (url) {
				errors.push($.htmlEncode(url) + ' (<a href="#" onclick="alert(' + $.htmlEncode(tinymce.util.JSON.serialize(message)) + ');return false;">' + $.htmlEncode(lang.ShowErrorMessage) + '</a>)');
			} else {
				errors.push($.htmlEncode(message));
			}
		});

		var html = '';

		if (images) {
			if (images == 1) {
				html += lang.ProductImageAddedSuccessfully + ' ';
			} else {
				html += lang.ProductImagesAddedSuccessfully.replace(/\%1\$d/g, images) + ' ';
			}
		}

		if (errors.length) {
			html += lang.ProductImageUrlsFailed + '<ul><li>';
			html += errors.join('</li><li>');
			html += '</li></ul>';
			window.scrollTo(0, 0);
			display_error('MainMessage', html);
		} else if (images) {
			display_success('MainMessage', html);
		}
	};

	var completeCallback = function () {
		// close dialog
		$.modal.close();
	};

	var options = {
		cache: false,
		type: 'POST',
		url: 'remote.php?w=productimages',
		dataType: 'xml',
		data: data,
		success: successCallback,
		complete: completeCallback
	};

	$.ajax(options);

	$('#ModalContentContainer input').attr('disabled', true);
};

// when a new image is added the sortable element will be refreshed - disable this when adding images in bulk, then enable it again after
ProductImages.refreshSortableOnNewImage = true;

// refresh the jquery ui sortable product image list
ProductImages.refreshSortable = function () {
	if (!ProductImages.refreshSortableOnNewImage) {
		return;
	}

	$('#productImagesList').sortable('refresh');

	// some browsers, like ie7, really stuff around with radio boxes when divs are moving around, so force a refresh of the radios now
	for (var i = ProductImages.images.length; i--;) {
		ProductImages.images[i].setBaseThumbnail(ProductImages.images[i].getBaseThumbnail());
	}
};

// javascript data model used for managing product images
ProductImages.Image = function (id, productId, preview, zoom, original, description, baseThumbnail, sort, hash) {
	if (typeof id == 'object') {
		// initialise using first parameter object key/value pairs instead
		product = id.product;
		preview = id.preview;
		zoom = id.zoom;
		original = id.original;
		description = id.description;
		baseThumbnail = id.baseThumbnail;
		sort = id.sort;
		hash = id.hash;
		id = id.id;
	}

	var self = this;

	var _id, _productId, _preview, _zoom, _original, _description, _baseThumbnail, _sort, _hash, _row;

	var _defaultAjaxSuccessHandler = function (xml) {
		// generic request handler -- looks for the presence of an <error> tag in the response with text content and triggers an error message with it
		var message = $(xml).find('error').text();
		if (message) {
			window.scrollTo(0, 0);
			display_error('MainMessage', $.htmlEncode(message));
			return false;
		}

		// display success message as long as the message is not just "1"
		message = $(xml).find('success').text();
		if (message && message != '1') {
			display_success('MainMessage', message);
		}

		return true;
	};

	// sends data to the server-side ajax handler for product images
	var _ajax = function (handler, data, success, error, complete) {

		complete = complete || function(){};

		success = success || _defaultAjaxSuccessHandler;

		error = error || function(){};

		data.productimageshandler = handler;

		var options = {
			cache: false,
			type: 'POST',
			url: 'remote.php?w=productimages',
			dataType: 'xml',
			data: data,
			complete: complete,
			success: success,
			error: error
		};

		$.ajax(options);
	};

	self.setId = function (id) {
		id = parseInt(id, 10);

		_id = id;

		// update ids in the _row dom
		self.getRow().attr('id', 'productImagesListItem_' + id);

		$.data(self.getRow().find('.productImageCheck input')[0], 'productImageId', id);
	};

	self.getId = function () {
		return _id;
	};

	self.setProductId = function (productId) {
		_productId = productId;
	};

	self.getProductId = function () {
		return _productId;
	};

	self.setHash = function (hash) {
		_hash = hash;
	};

	self.getHash = function () {
		return _hash;
	};

	self.setPreview = function (preview, refresh) {
		if (typeof refresh == 'undefined') {
			var refresh = false;
		}

		if (refresh || _preview !== preview) {
			// update preview urls in the _row dom

			if (!preview) {
				// server has provided invalid preview url which indicates the source image on the server is missing
				$('.productImageThumbDisplay > a', self.getRow())
					.text('?')
					.attr('alt', lang.ProductImagesNoSourceImageNoThumbnail)
					.attr('title', lang.ProductImagesNoSourceImageNoThumbnail)
					.click(function(evt){
						evt.preventDefault();
						alert(lang.ProductImagesNoSourceImageNoThumbnail);
					});

			} else {
				var backgroundUrl = preview;
				if (refresh) {
					backgroundUrl += '?rand=' + Math.random()
				}

				$('.productImageThumbDisplay > a', self.getRow()).css({
					backgroundImage: 'url(' + backgroundUrl + ')'
				});
			}
			_preview = preview;
		}
	};

	self.setZoom = function (zoom) {
		_zoom = zoom;
	};

	self.getZoom = function () {
		return _zoom;
	};

	self.setOriginal = function (original) {
		_original = original
	};

	self.getOriginal = function () {
		return _original;
	};

	self.setDescription = function (description, update) {
		if (typeof update == 'undefined') {
			update = false;
		}

		// trim whitespace from supplied descriptions
		description = String(description).replace(/^\s+|\s+$/, '');

		if (update && _description !== description) {
			_ajax('updateImageDescription', { image: self.getId(), description: description });
		}

		_description = description;

		// update the description in the _row dom
		var textarea = $('.productImageDescription > textarea', self.getRow());

		if (!_description) {
			textarea.val(lang.ClickHereToAddADescription);
		} else {
			textarea.val(_description);
		}
	};

	self.getDescription = function () {
		return _description;
	};

	self.setBaseThumbnail = function (baseThumbnail, update) {
		if (typeof update == 'undefined') {
			update = false;
		}

		baseThumbnail = !!baseThumbnail;

		if (update && _baseThumbnail !== baseThumbnail) {
			_ajax('updateBaseThumbnail', { image: self.getId() });
		}

		_baseThumbnail = baseThumbnail;

		// if necessary, update radio elements in the dom - browser should do this automatically for same named radio buttons though
		var input = $('.productImageBaseThumb input', self.getRow());
		if (input.attr('checked') !== _baseThumbnail) {
			input.attr('checked', _baseThumbnail);
		}
	};

	self.getBaseThumbnail = function () {
		return _baseThumbnail;
	};

	self.setSort = function (sort) {
		// note: this doesn't re-order the dom or change values for other images
		_sort = sort;
	};

	self.getSort = function () {
		return _sort;
	};

	// returns the jquery object for this image, representing it's row in the dom
	self.getRow = function () {
		return _row;
	};

	self.removeRow = function () {
		// remove this image's row from the dom
		self.getRow().remove();

		// remove this image from internal image list
		for (var i = ProductImages.images.length; i--;) {
			if (ProductImages.images[i] === self) {
				ProductImages.images.splice(i, 1);
			}
		}

		ProductImages.refreshDeleteSelectedButton();
	};

	self.fadeRow = function () {
		self.getRow().css({opacity: 0.2});
	};

	self.unfadeRow = function () {
		self.getRow().css({opacity: 1});
	};

	// delete this product image from the server - this method does NOT confirm with the user, other UI methods calling this should obtain confirmation first
	self.deleteImage = function (updateServer) {
		if (typeof updateServer == 'undefined') {
			updateServer = true;
		}

		if (updateServer) {
			// request image removal from server
			ProductImages.deleteImages([self.getId()]);
		} else {
			// deleteImages will call this function again with updateServer as false
			self.fadeRow();
		}
	};

	// generate html for inserting this image as a row in the image management
	self.getRowHtml = function () {
		var html = ProductImages.newRowTemplate;
		// replace placeholders if necessary
		return html;
	};

	self.onBaseThumbClick = function (evt) {
		// should always be true but, just in case...
		var checked = $(evt.target).attr('checked');

		self.setBaseThumbnail(checked, true);

		// set all other images as the opposite - server side code will handle updating all other db records
		for (var i = ProductImages.images.length; i--;) {
			if (ProductImages.images[i] !== self) {
				ProductImages.images[i].setBaseThumbnail(!checked);
			}
		}
	};

	self.onDeleteClick = function (evt) {
		evt.preventDefault();
		if (confirm(lang.ConfirmDeleteProductImage)) {
			self.deleteImage();
		}
	};

	self.onEditClick = function (evt) {
		evt.preventDefault();
		Common.Picnik.launchEditor('productimage', self.getId(), function(data){
			self.setPreview(data.thumbnail, true);
			self.setZoom(data.zoom);
			display_success('MainMessage', $.htmlEncode(lang.ProductImageEdited));
		});
	};

	self.showZoomImage = function () {
		// open a view of the zoom image
		if (!self.getZoom()) {
			// no zoom url supplied, abort
			return;
		}

		window.open(self.getZoom());
	};

	// moves the current image into the sort list after the supplied other image (provided by id) - will update all other affected image instances and also trigger an update on the server - if previousId is false, this image will be moved to the top of the list
	self.moveAfterOtherImage = function (previousId, updateUi) {
		if (typeof updateUi == 'undefined') {
			updateUi = false;
			// re-ordering the ui through jquery etc to simulate a drag+drop is not implemented yet - would need to update the dom manually
		}

		// first push an update to the server to perform the same action in the database, just incase the following js fails for some reason at least the server will update properly
		var data = {
			move: self.getId()
		};

		if (self.getProductId()) {
			data.product = self.getProductId();
		} else {
			data.hash = $('#productHash').val();
		}

		if (previousId) {
			data.after = previousId;
		}

		_ajax('moveImageAfterOtherImage', data);

		// server action has been dispatched, now simulate the change in the local ui
		if (!previousId) {
			// moving to top of list, setting afterSort to -1 forces the following checks to correspond to this
			var afterSort = -1;
		} else {
			var previousImage = ProductImages.getImageById(previousId);
			if (!previousImage) {
				alert('product.images.js: unable to get image by id ' + previousId);
			}
			var afterSort = previousImage.getSort();
		}

		var moveSort = self.getSort();
		var newSort = afterSort;

		var shiftValue, shiftHighpass, shiftLowpass;

		// shift all sort values between the old image location and new location, either up or down, depending on whether the image was moved up or down in the sort
		if (moveSort > afterSort) {
			newSort++;
			shiftValue = 1;
			shiftHighpass = moveSort;
			shiftLowpass = afterSort;
		} else {
			shiftValue = -1;
			shiftHighpass = afterSort + 1;
			shiftLowpass = moveSort;
		}

		var images = ProductImages.getImagesInOrder();
		var image;
		for (var i = images.length; i--;) {
			image = images[i];
			if (image.getSort() > shiftLowpass && image.getSort() < shiftHighpass) {
				image.setSort(image.getSort() + shiftValue);
			}
		}

		self.setSort(newSort);
	};

	self.onPreviewClick = function (evt) {
		evt.preventDefault();
		self.showZoomImage();
	};

	// add this instance to the images array
	ProductImages.images.push(self);

	// generate the html and create a jquery object for it
	_row = $(self.getRowHtml());

	// apply provided default values
	self.setId(id);
	self.setProductId(product);
	self.setPreview(preview);
	self.setZoom(zoom);
	self.setOriginal(original);
	self.setDescription(description);
	self.setBaseThumbnail(baseThumbnail);
	self.setSort(sort);

	// attach events to the input elements
	$('.productImageBaseThumb input', self.getRow()).click(self.onBaseThumbClick);
	$('.productImageDelete', self.getRow()).click(self.onDeleteClick);
	$('.productImageThumbDisplay a', self.getRow()).click(self.onPreviewClick);
	$('.productImageEdit', self.getRow()).click(self.onEditClick);

	// finally, add it to the list
	$('#productImagesList').append(self.getRow());

	// setup editable descriptions

	var _blurTimeout;

	var _descriptionCancelClick = function () {
		window.clearTimeout(_blurTimeout);
		$('.productImageDescription > textarea', self.getRow()).removeClass('editing');
		self.setDescription(self.getDescription());
		$('.productImageDescription > div', self.getRow()).hide();
	};

	var _descriptionSaveClick = function () {
		window.clearTimeout(_blurTimeout);
		var textarea = $('.productImageDescription > textarea', self.getRow());
		textarea.removeClass('editing');
		var description = textarea.val();
		if (description === '') {
			textarea.val(lang.ClickHereToAddADescription);
		}
		self.setDescription(description, true);
		$('.productImageDescription > div', self.getRow()).hide();
	};

	var _descriptionFocus = function () {
		window.clearTimeout(_blurTimeout);
		var textarea = $(this);
		textarea.addClass('editing');
		var description = textarea.val();
		if (description === lang.ClickHereToAddADescription) {
			textarea.val('');
		}
		// delayed select as chrome doesn't like instant select
		window.setTimeout(function(){
			textarea[0].select();
		}, 1);
		$('.productImageDescription > div', self.getRow()).show();
	};

	var _descriptionBlur = function () {
		_blurTimeout = window.setTimeout(_descriptionSaveClick, 1000);
	};

	$('.productImageDescription > textarea', self.getRow()).focus(_descriptionFocus).blur(_descriptionBlur);

	$('.productImageDescription .productImageDescriptionSave', self.getRow()).click(_descriptionSaveClick);

	$('.productImageDescription .productImageDescriptionCancel', self.getRow()).click(_descriptionCancelClick);

	// refresh the sortable
	ProductImages.refreshSortable();

	ProductImages.refreshDeleteSelectedButton();
};

ProductImages.uploader = {

	onStart: function () {
		// upload sequence starting
		ProductImages.uploader.totalCount = 0;
		ProductImages.uploader.successCount = 0;
		ProductImages.uploader.errorFiles = [];
		ProductImages.uploader.generalErrors = [];
	},

	onSuccess: function (data) {
		// file successfully sent to server, parse response for errors & warnings

		if (data.error !== false) {
			// general error that probably means file-specific information isn't available
			ProductImages.uploader.generalErrors.push(data.error);
		} else {
			ProductImages.uploader.totalCount++;
		}

		if (data.files && data.files.length) {
			var file;
			for (var i = 0; i < data.files.length; i++) {
				file = data.files[i];

				if (file.error === false) {
					new ProductImages.Image(file); // will automatically update ui
					ProductImages.uploader.successCount++;
				} else {
					ProductImages.uploader.errorFiles.push(file);
				}
			}
		}
	},

	onFinished: function () {
		// upload sequence finished
		var error = false;
		var html = '';

		if (ProductImages.uploader.generalErrors.length) {
			error = true;
			for (var i = 0; i < ProductImages.uploader.generalErrors.length; i++) {
				html += '<div>' + $.htmlEncode(ProductImages.uploader.generalErrors[i]) + '</div>';
			}

		} else if (ProductImages.uploader.errorFiles.length) {
			error = true;

			html += '<div>' + lang.ProductImagesNotUploadedDueToErrors;

			if (ProductImages.uploader.errorFiles.length < ProductImages.uploader.totalCount) {
				html += lang.ProductImagesAnyImageNotListedHere;
			}

			html += ':</div><ul>';

			for (var i = 0; i < ProductImages.uploader.errorFiles.length; i++) {
				var file = ProductImages.uploader.errorFiles[i];

				html += '<li>';

				html += $.htmlEncode(file.name) + ': ' + $.htmlEncode(file.error);

				html += '</li>';
			}

			html += '</ul>';

		} else if (ProductImages.uploader.successCount == 0) {
			error = true;
			html += lang.ProductImageNoFilesUploaded;

		} else {
			if (ProductImages.uploader.successCount == 1) {
				html += lang.ProductImageAddedSuccessfully;
			} else {
				html += lang.ProductImagesAddedSuccessfully.replace(/\%1\$d/g, ProductImages.uploader.successCount);
			}
		}

		if (error) {
			window.scrollTo(0, 0);
			display_error('MainMessage', html);
		} else {
			display_success('MainMessage', html);
		}
	}
};

//
// on dom-ready setup stuff for product image management

$(function(){
	if (!$('#productImagesList').length) {
		// don't bother running this code if the product images list isn't present
		return;
	}

	//
	// setup the master checkbox

	$('.productImagesTable td.productImageCheck input').click(function(){
		$('#productImagesList .productImageCheck input').attr('checked', this.checked);
	});

	//
	// setup the 'delete selected' button

	$('#productImagesDeleteSelected').click(function(evt){
		evt.preventDefault();

		var deleteImages = [];

		$('#productImagesList .productImageCheck input:checked').each(function(){
			if ($.data(this, 'productImageId')) {
				deleteImages.push($.data(this, 'productImageId'));
			}
		});

		if (!deleteImages.length) {
			// none were checked
			alert(lang.ChooseProductImage);
			return;
		}

		if (!confirm(lang.ConfirmDeleteProductImage)) {
			return;
		}

		// uncheck the master checkbox
		$('.productImagesTable td.productImageCheck input').attr('checked', false);

		ProductImages.deleteImages(deleteImages);
	});

	//
	// setup the 'new image' uploader row

	$('.ProductImageNewUpload').click(function(evt) {
		// handle clicks on the 'Upload an Image' menu item when swfupload support is not available
		evt.preventDefault();

		var url = shop.config.AppPath + '/admin/remote.php?w=productimages&productimageshandler=newImageUpload';
		var data = {};
		var productId = $('#productId').val();
		if (productId) {
			data.product = productId;
			url += '&product=' + encodeURIComponent(productId);
		} else {
			data.hash = $('#productHash').val();
			url += '&hash=' + encodeURIComponent(data.hash);
		}

		var dialog = new MultiUploadDialog({
			action: url,
			data: data,
			titletext: lang.UploadAnImage,
			introtext: lang.ProductImagesNonFlashIntro,
			submittext: lang.ProductImagesUploadImagesElipsis,
			closetext: lang.CancelEdit,
			cleartext: lang.ProductImagesNonFlashRemove,
			noinputsalerttext: lang.ProductImagesChooseAnImage
		});

		ProductImages.uploader.onStart();

		$(dialog).bind('uploadsuccess', function(evt, data){
			ProductImages.uploader.onSuccess(data);
		});

		$(dialog).bind('uploadsfinished', function(evt){
			ProductImages.uploader.onFinished();
		});
	});

	$('#productImageNewWeb').click(function(evt) {
		// handle clicks on the 'Use Image from Web' menu item
		evt.preventDefault();

		$.iModal({
			type: 'inline',
			inline: '#UseImageFromWebDialog',
			width: 390
		});

		$('#ModalContentContainer .UseImageFromWebDialogImageUrl').focus(function(evt){
			var self = this;
			setTimeout(function(){
				// chrome doesn't like an immediate select() -- other browsers are also fine with this method
				if (self.value === self.defaultValue) {
					self.select();
				}
			}, 1);

		}).eq(0).focus();
	});

	$('#productImageNewGallery').click(function(evt) {
		// handle clicks on the 'Choose from Gallery' menu item
		$.iModal({
			type: 'inline',
			inline: '#UseImageFromGallery',
			onShow: ProductImages.useImageFromGallery.onShowModal,
			onClose: ProductImages.useImageFromGallery.onCloseModal,
			width: 780
		});

	});

	//
	// setup new image: upload

	if (DetectFlashVer(8, 0, 0)) {

		// show any notices about alternative upload methods
		$('.SwfUploadAlternativeNotice').show();

		var post_params = {
			PHPSESSID: shop.config.sessionid
		};

		var productId = $('#productId').val();
		if (productId) {
			post_params.product = productId;
		} else {
			post_params.hash = $('#productHash').val();
		}

		// flash uploader
		ProductImages.swfUploader = new SWFUpload({
			// Backend Settings
			upload_url: shop.config.AppPath + "/admin/remote.php?w=productimages&productimageshandler=newImageUpload",	// Relative to the SWF file or absolute
			// File Upload Settings
			file_size_limit : shop.config.maxUploadSize + " B",
			file_types: ProductImages.swfUploadFileTypes,
			file_types_description : " " + lang.Images,
			file_upload_limit : "0",

			post_params: post_params,

			// Event Handler Settings
			file_queue_error_handler : fileQueueError,
			file_dialog_complete_handler : fileDialogComplete,

			upload_progress_handler : function (file, bytesLoaded) {
				var percent = Math.ceil((bytesLoaded / file.size) * 100);

				$('.progressBarPercentage').css('width', parseInt(percent) + "%");
				$('.progressPercent').html(percent+ "%");

				if (bytesLoaded === file.size) {
					//	change the message at 100% to tell the user the server is doing something
					$('.ProgressBarText').text(lang.ProductImagesProcessing);
				}
			},

			upload_error_handler : function (file, errorCode, message) {
				// transport error of some sort (self-signed ssl cert, for example);
				UploadError = lang.ProductImagesTransportError;
				return;
			},

			upload_success_handler : function (file, serverData) {
				// called when the upload http request was successful, even if some files may have failed

				var result;

				try {
					eval('result = ' + serverData + ';');
				} catch (e) {
					// when the server response can't be eval'd it usually means there's a PHP ERROR, WARNING or NOTICE in the response body along with the usual response -- at this point the javascript has no idea what the response really is
					UploadError = lang.ProductImagesUploadError;
					return;
				}

				if (result.error) {
					// general error such as exceeding maximum POST size
					UploadError = result.error;
					return;
				}

				var i, file;

				for (i = 0; i < result.files.length; i++) {
					file = result.files[i];

					if (file.error) {
						// specific file error such as not an acceptable image, file too big
						UploadErrorFiles.push(file);
						continue;
					}

					// file is ok -- instanciate it for the ui (the json data returned from the server is compatible with the Image class constructor)
					new ProductImages.Image(file);
				}
			},

			upload_start_handler : function (file) {
				$('.progressBarPercentage').css('width', "0%");
				$('.progressPercent').html("0%");
				$('.progressBarStatus').text(lang.ProductImagesUploadProgressStatus.replace(/\%1\$d/g, FileCount).replace(/\%2\$d/g, TotalItemsToUpload));
				$('.ProgressBarText').text(lang.ProductImagesUploadProgressFile.replace(/\%1\$s/g, file.name));
			},

			upload_complete_handler : function (file) {
				// this is mostly a copy of the funcionality found in swfupload.handlers uploadComplete function, except the product image upload handler returns more detailed error information on a per-image basis

				if (this.getStats().files_queued > 0) {
					// start the next upload
					FileCount++;
					this.startUpload();
					return;
				}

				// no more uploads, close the dialog
				$.iModal.close();

				var error = false;
				var html = '';

				if (UploadError) {
					error = true;
					html += '<div>' + $.htmlEncode(UploadError) + '</div>';

				} else if (UploadErrorFiles.length) {
					error = true;

					html += '<div>' + lang.ProductImagesNotUploadedDueToErrors;

					if (UploadErrorFiles.length !== TotalItemsToUpload) {
						html += lang.ProductImagesAnyImageNotListedHere;
					}

					html += ':</div><ul>';

					for (var i = 0; i < UploadErrorFiles.length; i++) {
						var file = UploadErrorFiles[i];

						html += '<li>';

						html += $.htmlEncode(file.name) + ': ' + $.htmlEncode(file.error);

						html += '</li>';
					}

					html += '</ul>';

				} else {
					// there's no duplicate checks for product image management, skip it

					if (FileCount == 1) {
						html += lang.ProductImageAddedSuccessfully;
					} else {
						html += lang.ProductImagesAddedSuccessfully.replace(/\%1\$d/g, FileCount);
					}
				}

				if (error) {
					window.scrollTo(0, 0);
					display_error('MainMessage', html);
				} else {
					display_success('MainMessage', html);
				}
			},

			// Button Settings
			button_placeholder_id : "productImageNewUploadPlaceholder",

			// adapt to width and height of upload link since it's text based
			button_width: $('#productImageNewUpload').width(),
			button_height: $('#productImageNewUpload').parent().height(),

			button_window_mode: SWFUpload.WINDOW_MODE.TRANSPARENT,
			button_cursor: SWFUpload.CURSOR.HAND,

			// Flash Settings
			flash_url : "images/swfupload.swf",

			// Debug Settings
			debug: false
		});

	} else {
		// flash upload capability was not detected, hide any notices about alternative upload methods
		$('.SwfUploadAlternativeNotice').hide();
	}

	//
	// setup the sortable image list

	$('#productImagesList').sortable({
		axis: 'y',
		distance: 15,
		start: function (evt, ui) {
			// on drag start, adjust the width of the helper to be the same width as the rest of the rows - necessary on at least firefox
			ui.helper.width($('#productImagesListItemNew').width());
		}
	});

	$('#productImagesList').bind('sortupdate', function(evt, ui){
		// handles when the product image list is reordered

		var rxp = /^productImagesListItem_([0-9]+)$/;

		if (!rxp.test(ui.item.attr('id'))) {
			return;
		}

		var itemId = parseInt(RegExp.$1, 10);
		if (!itemId) {
			return;
		}

		var image = ProductImages.getImageById(itemId);
		if (!image) {
			return;
		}

		var prevId = false;

		var prev = ui.item.prev('.productImagesListItem');
		if (prev.length) {
			if (!rxp.test(prev.attr('id'))) {
				return;
			}
			prevId = parseInt(RegExp.$1, 10);
		}

		// push an update to the server instructing it to reorder the image that was just moved
		image.moveAfterOtherImage(prevId, false);
	});

	$('#productImagesList').bind('sortstop', function (evt, ui){
		// handles when sorting of the product image list stops, whether it's changed or not

		var rxp = /^productImagesListItem_([0-9]+)$/;

		if (!rxp.test(ui.item.attr('id'))) {
			return;
		}

		var itemId = parseInt(RegExp.$1, 10);
		if (!itemId) {
			return;
		}

		var image = ProductImages.getImageById(itemId);
		if (!image) {
			return;
		}

		// on some browsers, radio elements will become cleared after a row is moved - force a refresh of the dom
		image.setBaseThumbnail(image.getBaseThumbnail());
	});
});



$(window).unload(function(){
	try {
		ProductImages.swfUploader.destroy();
	} catch (e) {
		// mute any errors since the user is navigating away anyway
	}
});
