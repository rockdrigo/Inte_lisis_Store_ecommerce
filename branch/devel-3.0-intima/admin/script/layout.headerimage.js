
function AdminHeaderImage() {
	var self = this;

//	this.iscontentmodule = false;

	this.uploadHeaderImage = function () {
		if($('#HeaderImageFile').val() == '') {
			alert(lang['LayoutHeaderImageNoImage']);
			return false;
		}

		$.ajaxFileUpload
		(
			{
				url:'remote.php?w=uploadheaderimage',
				secureuri:false,
				fileElementId:'HeaderImageFile',
				dataType: 'json',
				beforeSend: function (){ },
				success: function (json, status)
				{
					if(json.success){
						$('#currentHeaderImage').html('<img src="' + json.currentImage + '?' + Math.floor(Math.random()*100001) + '" />');
						display_message(json.message, 'message');
						AdminHeaderImage.ShowCurrentHeaderImage(json.currentImage);
					}else{
						display_message(json.message, 'error');
					}

				}
			}
		)
		return false;
	};

	this.getHeaderImage = function () {
		//self.updateLinksDisplay();

		$.getJSON('remote.php?w=getheaderimage', function(json){
			if(!json.success){
				$('#DownloadHeaderImages').hide();
				$('#UploadHeaderImageRow').hide();
				$('#currentHeaderImage').html(lang['LayoutHeaderNoCurrentImage']);
				return;
			}

			$('#DownloadHeaderImages').show();
			$('#UploadHeaderImageRow').show();

			$('#currentHeaderImage').html(json.image);

			if(!json.origBlank){
				$('#HeaderImageBlankLinkContainer').hide();
			}else{
				$('#HeaderImageBlankLink').attr('href', 'remote.php?w=getBlankHeaderImage');
				$('#HeaderImageBlankLinkContainer').show();
			}

			$('#HeaderImageOrigLink').attr('href', 'remote.php?w=getOrigHeaderImage');

			if(json.hasCurrent){
				AdminHeaderImage.ShowCurrentHeaderImage(json.currentImage);
			}else{
				$('#HeaderImageCurrentLinkContainer').hide();
			}
		});
	};

	this.ShowCurrentHeaderImage = function (currentImage) {
		$('#HeaderImageCurrentLinkContainer a').attr('href', 'remote.php?w=getCurrentHeaderImage');
		$('#HeaderImageCurrentLinkContainer').show();

		$('#HeaderImageDeleteLink').unbind('click');

		$('#HeaderImageDeleteLink').bind('click', function() {
			if(confirm(lang['HeaderImageConfirmDelete'])) {
				$.getJSON('remote.php?w=deleteheaderimage', function(json){
					if(json.success){
						display_message(json.message, 'message');
					}else{
						display_message(json.message, 'error');
					}
					AdminHeaderImage.getHeaderImage();
				});
			}
			return false;
		});
	};
}

function ResizeHeaderImageContainer() {
	$('#currentHeaderImage').css('width', '700px');
	$('#currentHeaderImage').css('max-width', '700px');

	$('#currentHeaderImage').css('width', $('#DownloadHeaderImages').width() + 'px');
	$('#currentHeaderImage').css('max-width', $('#DownloadHeaderImages').width() + 'px');
}

AdminHeaderImage = new AdminHeaderImage();

$(document).ready(function() {

	AdminHeaderImage.getHeaderImage();

	$('#SubmitHeaderImageForm').bind('click', function() {
		AdminHeaderImage.uploadHeaderImage();
	});

	ResizeHeaderImageContainer();

});

$(window).bind('resize', function() {
	if (typeof resizeTimer != 'undefined') {
		clearTimeout(resizeTimer);
	}
	resizeTimer = setTimeout(ResizeHeaderImageContainer, 100);
});

$(document).bind('tabSelect5', ResizeHeaderImageContainer);