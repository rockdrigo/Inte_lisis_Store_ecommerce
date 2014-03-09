ProcessProductImages = {
	lang: []
};

ProcessProductImages.runProcess = function (start) {
	if(typeof start == 'undefined') {
		var start = 0;
	}

	$.post('remote.php?remoteSection=products&w=processimages',
		{'start': start},
		ProcessProductImages.handleResponse,
		'json'
	);
};

ProcessProductImages.handleResponse = function (json) {
	if(json.success) {
		var newPercent = Math.round(((parseInt(json.completed + json.start) / parseInt(json.total)) * 100));
		ProcessProductImages.updateProgress(newPercent, ProcessProductImages.lang['ProcessProgress'].replace('{complete}', (json.completed + json.start)).replace('{total}', json.total));
		if((json.completed + json.start) < json.total) {
			ProcessProductImages.runProcess((json.completed + json.start));
		} else {
			ProcessProductImages.finish();
		}
	}
};

ProcessProductImages.launch = function () {
	$.iModal({
		type: 'ajax',
		title: ProcessProductImages.lang['ModalTitle'],
		onOpen: function () {
				$('.closeModalButton').bind('click', function() {
					$.iModal.close();
				});
				$('#ModalContainer').show();
				$('.closeModalButton, .modalClose').hide();
			},
		buttons: '<input type="button" class="closeModalButton" value="Close"/>',
		url: 'remote.php?remoteSection=products&w=showprocessimages',
		width: 450
	});
	return false;
};

ProcessProductImages.finish = function (start) {
	ProcessProductImages.updateProgress(100, ProcessProductImages.lang['ProcessFinished']);
	$('.closeModalButton, .modalClose').show();
};

ProcessProductImages.updateProgress = function (newPercent, newStatus) {
	$('#progressPercent').html(parseInt(newPercent) + "%");
	$('#progressBarPercentage').css('width', parseInt(newPercent) + "%");
	$('#progressBarStatus').html(newStatus);
};