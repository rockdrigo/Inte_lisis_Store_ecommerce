var youtube = {
	searchInProgress: false,
	nextSearchPage: 1,
	videos: '',
	searchTerm: '',

	launchVideo: function() {
		var videoId = $(this).find('img').attr('id');
		var videoTitle = $(this).parent().find('.videoTitleText').html();
		$.iModal({
			type: 'ajax',
			title: videoTitle,
			onOpen: function () {
				$('.closeModalButton').bind('click', function() {
					$.iModal.close();
				});
				$('#ModalContainer').show();
			},
			buttons: '<input type="button" class="closeModalButton" value="Close"/>',
			url: 'remote.php?remoteSection=products&w=watchYouTubeVideo&videoid=' + videoId,
			width: 450
		});

		return false;
	},

	searchVideos: function(json) {
		if(!json.success) {
			alert(json.message);
			$('.youtubeLoading').remove();
			$('#findVideosButton').removeAttr('disabled');
			return;
		}

		$('#youTubeSearchVideos .showMoreRow').remove();

		$('#youTubeSearchVideos').append(json.html);

		if($('#youTubeSearchVideos').html() == ""){
			$('#noSearchVideos').show();
			$('#youTubeSearchVideos').hide();
			$('#addYouTubeVideos').attr('disabled', 'disabled');
		}else {
			if($('#youTubeSearchVideos li.selectedVideo').size() > 0) {
				$('#addYouTubeVideos').removeAttr('disabled');
			}
			$('#youTubeSearchVideos li.videoRow').bind('click', youtube.selectVideo);
			$('#youTubeSearchVideos li:odd').addClass('videoRowFirst');
			$('#youTubeSearchVideos li:even').addClass('videoRowSecond');

			$('#youTubeSearchVideos .viewYouTubeVideo').bind('click', youtube.launchVideo);

			youtube.nextSearchPage = json.nextpage;

			// set up find more videos link
			$('.showMoreRow').bind('click', function () {
				$(this).html(lang.VideoLoading + '<img src="images/loading.gif" />');
				$.getJSON('remote.php?remoteSection=products&w=searchyoutube&page=' + youtube.nextSearchPage + '&keywords=' + escape(youtube.searchTerm), youtube.searchVideos);
				return false;
			});
		}

		$('#findVideosButton').removeAttr('disabled');
		$('.youtubeLoading').remove();
	},

	selectVideo: function () {
		if($(this).hasClass('selectedVideo')) {
			$(this).removeClass('selectedVideo').addClass('unselectedVideo');
		} else {
			$(this).addClass('selectedVideo').removeClass('unselectedVideo');
		}

		if($('#youTubeSearchVideos li.selectedVideo').size() > 0) {
			$('#addYouTubeVideos').removeAttr('disabled');
		} else {
			$('#addYouTubeVideos').attr('disabled', 'disabled');
		}

		if($('#youTubeCurrentVideos li.selectedVideo').size() > 0) {
			$('#removeYouTubeVideos').removeAttr('disabled');
		} else {
			$('#removeYouTubeVideos').attr('disabled', 'disabled');
		}
	},

	init: function () {

		$('#addYouTubeVideos').attr('disabled', 'disabled');
		$('#removeYouTubeVideos').attr('disabled', 'disabled');

		$('#addYouTubeVideos').bind('click', function () {

			if($('#youTubeSearchVideos li.selectedVideo').size() < 1) {
				return;
			}

			$('#youTubeSearchVideos li.selectedVideo').each(function() {
				var videoId = $(this).find('img').attr('id');

				$(this).removeClass('selectedVideo').addClass('unselectedVideo').hide();

				// prevent duplicates
				if($('#youTubeCurrentVideos #' + videoId).size() < 1) {
					$('#youTubeCurrentVideos').append('<li class="unselectedVideo videoRow">' + $(this).html() + '</li>');
					$(this).find('img').attr('id', videoId + '_copy');
				}
			});

			$('#youTubeCurrentVideos').sortable('refresh');

			$('#youTubeCurrentVideos').show();
			$('#noCurrentVideos').hide();

			$('#youTubeCurrentVideos .viewYouTubeVideo').unbind('click');
			$('#youTubeCurrentVideos .viewYouTubeVideo').bind('click', youtube.launchVideo);

			$('#youTubeCurrentVideos li').unbind('click');
			$('#youTubeCurrentVideos li').bind('click', youtube.selectVideo);

			$('#youTubeSearchVideos li, #youTubeCurrentVideos li').removeClass('videoRowFirst').removeClass('videoRowSecond');
			$('#youTubeSearchVideos li:odd, #youTubeCurrentVideos li:odd').addClass('videoRowFirst');
			$('#youTubeSearchVideos li:even, #youTubeCurrentVideos li:even').addClass('videoRowSecond');

			if($('#youTubeSearchVideos li.selectedVideo').size() > 0) {
				$('#addYouTubeVideos').removeAttr('disabled');
			} else {
				$('#addYouTubeVideos').attr('disabled', 'disabled');
			}

			if($('#youTubeCurrentVideos li.selectedVideo').size() > 0) {
				$('#removeYouTubeVideos').removeAttr('disabled');
			} else {
				$('#removeYouTubeVideos').attr('disabled', 'disabled');
			}

		});

		$('#searchYouTube').bind('keypress', function(event) {
			if (event.keyCode == 13) {
				$('#findVideosButton').trigger('click');
				return false;
			}
			return true;
		});

		$('#removeYouTubeVideos').bind('click', function () {
			$('#youTubeCurrentVideos li.selectedVideo').each(function() {
				var videoId = $(this).find('img').attr('id');
				$(this).remove();
				$('#' + videoId + '_copy').attr('id', videoId).parent().parent().show();
			});

			$('#youTubeCurrentVideos').sortable('refresh');

			// we need to refresh the search list as well as we may have re-showen one that was removed from our current list
			$('#youTubeSearchVideos li, #youTubeCurrentVideos li').removeClass('videoRowFirst').removeClass('videoRowSecond');
			$('#youTubeSearchVideos li:odd, #youTubeCurrentVideos li:odd').addClass('videoRowFirst');
			$('#youTubeSearchVideos li:even, #youTubeCurrentVideos li:even').addClass('videoRowSecond');

			if($('#youTubeCurrentVideos li').size() < 1) {
				$('#youTubeCurrentVideos').hide();
				$('#noCurrentVideos').show();
				$('#removeYouTubeVideos').attr('disabled', 'disabled');
			}

			if($('#youTubeCurrentVideos li.selectedVideo').size() > 0) {
				$('#removeYouTubeVideos').removeAttr('disabled');
			} else {
				$('#removeYouTubeVideos').attr('disabled', 'disabled');
			}
		});

		$('#findVideosButton').bind('click', function () {

			if($('#searchYouTube').val().replace(/ /g, '') == '' || $('#searchYouTube').val() == $.data($('#searchYouTube').get(0), "origValue")) {
				alert(lang.VideoPleaseEnterSearchTerm);
				$('#searchYouTube').focus();
				return;
			}

			$('#findVideosButton').attr('disabled', 'disabled');

			$('#youTubeSearchVideos').show();
			$('#useSearchVideos').hide();
			$('#noSearchVideos').hide();

			$('#youTubeSearchVideos').html('');

			youtube.nextSearchPage = 1;

			$('.youtubeLoading').remove();
			$('#findVideosButton').after('<img src="images/loading.gif" class="youtubeLoading" />');
			youtube.searchTerm = $('#searchYouTube').val();

			$.getJSON('remote.php?remoteSection=products&w=searchyoutube&keywords=' + escape($('#searchYouTube').val()), youtube.searchVideos);

			return false;
		});

		$('.SetOriginalValue').each(function() {
			$.data(this, "origValue", $(this).val());
		});

		$('#searchYouTube').bind('focus', function() {
			if($.data(this, "origValue") == $(this).val()) {
				$(this).val('');
				$(this).removeClass('exampleSearchText');
			}
		});

		$('#searchYouTube').bind('blur', function() {
			if($(this).val() == '') {
				$(this).val($.data(this, "origValue"));
				$(this).addClass('exampleSearchText');
			}
		});

		$('#youTubeCurrentVideos').sortable({
			delay: 100,
			forcePlaceholderSize: true,
			placeholder: 'videoPlaceholder',
			stop: function () {
				$('#youTubeCurrentVideos li').removeClass('videoRowFirst').removeClass('videoRowSecond');
				$('#youTubeCurrentVideos li:odd').addClass('videoRowFirst');
				$('#youTubeCurrentVideos li:even').addClass('videoRowSecond');
			}
		});

		// if the browser clears the input field, or uses a different value because it thinks it is being smart (looking at you Firefox!), we want to override it
		$('#youTubeVideos').val(youtube.videos);

		// check if there are videos, if so we want to add them to the current videos side
		if($('#youTubeVideos').val() != '') {
			$('#findVideosButton').after('<img src="images/loading.gif" class="youtubeLoading" />');
			$.getJSON('remote.php?remoteSection=products&w=getyoutubevideos&videos=' + $('#youTubeVideos').val(), function(json) {
				if(!json.success) {
					// alert(json.message);
					$('.youtubeLoading').remove();
					$('#findVideosButton').removeAttr('disabled');
					return;
				}

				if(json.html.length > 0) {
					$('#youTubeCurrentVideos').html(json.html);
					$('#youTubeCurrentVideos').show();
					$('#noCurrentVideos').hide();
				}

				$('#youTubeCurrentVideos li').bind('click', youtube.selectVideo);
				$('#youTubeCurrentVideos li:odd').addClass('videoRowFirst');
				$('#youTubeCurrentVideos li:even').addClass('videoRowSecond');

				$('#youTubeCurrentVideos').sortable('refresh');

				$('#findVideosButton').removeAttr('disabled');
				$('.youtubeLoading').remove();
			});
		}
	}
};