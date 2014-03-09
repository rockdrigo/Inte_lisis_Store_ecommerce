Layout.GiftCertificates = {
	/**
	 * Gift Certificates management table
	 * jQuery selector
	 **/
	tableSelector: '#GiftCertificates',

	/**
	 * Called by action links to prevent default behaviour
	 * and fetch the gift certificate id for the action.
	 */
	actionLink: function(e)
	{
		e.preventDefault();

		var certificate = $(this).parents('.GiftCertificate');
		return certificate.attr('giftcertificate:id');
	},

	/**
	 * Edit gift certificate action link
	 */
	editLink: function(e)
	{	
		var id = Layout.GiftCertificates.actionLink.call(this, e);

		// Check we are not already editing this gift certificate
		if( id === Layout.GiftCertificates.editGiftCertificateId ) {
			return;
		}

		// Create the ajax request and response handler
		var editUrl = Layout.GiftCertificates.Urls.edit + '&id=' + id;
		var row = $(this).parents('.GiftCertificate');
	
		var editHandler = function(response) {
			if(typeof response.editor === 'undefined') {
				return;
			}

			editor = response.editor;
			loadFunc = response.loadFunc;

			Layout.GiftCertificates.openEditor(row, editor, loadFunc);
		};

		$.ajax({
			url: editUrl,
			success: editHandler
		});
	},

	/**
	 * Preview gift certificate action link
	 */
	previewLink: function(e)
	{
		var id = Layout.GiftCertificates.actionLink.call(this, e);

		var previewUrl = Layout.GiftCertificates.Urls.preview + '&id=' + id;

		$.ajax({
			url: previewUrl,
			success: Layout.GiftCertificates.previewHandler
		});
	},

	/**
	 * Restore gift certificate action link
	 */
	restoreLink: function(e)
	{
		var id = Layout.GiftCertificates.actionLink.call(this, e);

		if(!confirm(lang.GiftCertificateRestoreConfirmation)) {
			return;
		}

		var restoreUrl = Layout.GiftCertificates.Urls.restore + '&id=' + id;
		window.location.replace(restoreUrl);
	},

	/**
	 * Creates and attaches a dynamic iframe with the given
	 * html contents to a DOM container element. 
	 */
	createFrame: function(container, html)
	{
    	var frame = $('<iframe />').width('100%').attr('frameBorder', '0').appendTo(container)[0];
  
		// Grab the frame's document object
    	var frameDoc = frame.contentWindow ? frame.contentWindow.document : frame.contentDocument;
    	frameDoc.open(); frameDoc.write(html); frameDoc.close();

		// Calculate max height for the iframe
		var maxheight = Math.max(($(window).height() - 300), 300);

		// Auto adjust the iframe's height once its document is ready
		$(frameDoc).ready(function(){
			var height = Math.min(frameDoc.body.scrollHeight + 20, maxheight);

			$(frame).height(height);
		});
	},

	/**
	 * Opens the gift certificate preview modal frame and displays
	 * the given html.
	 */
	openPreviewModal: function(html){
		$.iModal({
			type: 'inline',
			inline: '#giftCertificatePreviewModal',
			width: 900,
			height: 400
		});

		// Add close button handler
		$('.closeGiftCertificatePreviewButton').click(
			function(){
				$.iModal.close();
			}
		);

		var container = $('#giftCertificatePreviewFrame');
		var f = Layout.GiftCertificates.createFrame(container, html);
	},

	/**
	 * Toggle gift certificate enabled action link
	 */
	toggleEnabledLink: function(e)
	{
		var id = Layout.GiftCertificates.actionLink.call(this, e);
		var toggleUrl = Layout.GiftCertificates.Urls.toggleEnabled + '&id=' + id;
		var enabledImage = $('img', this);

		var toggleHandler = function(response) {
			if(typeof response.enabled === 'undefined') {
				return;
			}

			if(response.enabled) {
				enabledImage.attr('src', 'images/tick.gif');
			}
			else {
				enabledImage.attr('src', 'images/cross.gif');
			}
		};

		$.ajax({
			url : toggleUrl,
			success: toggleHandler
		});
	},

	/**
	 * Sets up the edit form row and moves it below the row
	 * of the gift certificate being edited.
	 */
	openEditor: function(row, editor, loadFunc){
		this.editGiftCertificateId = row.attr('giftcertificate:id');

		var editFormRow = $('.giftCertificateEditForm');
		var editBox = $('.editBox', editFormRow).html(editor);
		
		row.after(editFormRow);
		editFormRow.show();

		if(typeof window[loadFunc] === 'function') {
			window[loadFunc]();
			this.editorId = editBox.children()[0].id;
		}
	},

	closeEditor: function(){
		var editFormRow = $('.giftCertificateEditForm').hide();
		this.editGiftCertificateId = null;
		this.editorId = null;
	},

	editFormLink: function(e){
		e.preventDefault();

		var editorId = Layout.GiftCertificates.editorId;

		return {
			certificateId: Layout.GiftCertificates.editGiftCertificateId,
			editor: tinyMCE.get(editorId)};
	},

	cancelEditForm: function(e){
		var data = Layout.GiftCertificates.editFormLink.call(this, e);
		Layout.GiftCertificates.closeEditor();
	},
	
	saveEditForm: function(e){
		var data = Layout.GiftCertificates.editFormLink.call(this, e);

		var saveUrl = Layout.GiftCertificates.Urls.save;

		$.ajax({
			url: saveUrl,
			type: 'POST',
			data: {
				'id' : data.certificateId,
				'html' : data.editor.getContent()
			},
			success: function(response){
				alert(response.message);
				Layout.GiftCertificates.closeEditor();
			}
		});
	},

	previewEditForm: function(e){
		var data = Layout.GiftCertificates.editFormLink.call(this, e);
		
		var previewUrl = Layout.GiftCertificates.Urls.preview;

		$.ajax({
			url: previewUrl,
			type: 'POST',
			data: {'html' : data.editor.getContent()},
			success: Layout.GiftCertificates.previewHandler
		});
	},

	/**
	 * Handles the response for gift certificate preview calls
	 */
	previewHandler : function(response) {
		if(typeof response.html === 'undefined') {
			return;
		}
		
		Layout.GiftCertificates.openPreviewModal(response.html);
	},
	
	/**
	 * Initialize the gift certificate management controls
	 */
	init: function()
	{
		var table = $(this.tableSelector);

		// action links
		$('.editLink', table).click(this.editLink);
		$('.previewLink', table).click(this.previewLink);
		$('.restoreLink', table).click(this.restoreLink);

		// enable / disable toggle
		$('.toggleEnabledLink', this.table).click(this.toggleEnabledLink);

		// edit form controls
		$('.cancelLink', table).click(this.cancelEditForm);
		$('.previewButton', table).click(this.previewEditForm);
		$('.saveButton', table).click(this.saveEditForm);
	}
};
