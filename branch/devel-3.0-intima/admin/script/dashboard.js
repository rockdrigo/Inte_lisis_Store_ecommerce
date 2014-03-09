var Dashboard = {
	activeRecentOrdersRequest: false,
	currentDisplay: '',

	Init: function()
	{
		Dashboard.CheckLatestVersion();
		Dashboard.LoadVideoWalkthroughs();
		if($('.DashboardPanelHelpArticles').css('display') != 'none') {
			$('.HelpArticlesList').load('index.php?ToDo=HelpRSS');
		}

		$('.GettingStartedToggleLink').click(function() {
			if($('.DashboardGettingStarted:hidden').length == 0) {
				var mode = 'overview';
				var hide = '.DashboardGettingStarted';
				var show = '.DashboardCommonTasks';
			}
			else {
				var mode = 'gettingstarted';
				var hide = '.DashboardCommonTasks';
				var show = '.DashboardGettingStarted';
			}
			SetCookie('DashboardMode', mode, 365);
			$(hide).hide();
			$(show).show();
			Dashboard.currentDisplay = show;
		});

		if($('.DashboardGettingStarted:hidden').length != 0) {
			Dashboard.currentDisplay = '.DashboardCommonTasks';
		}
		else {
			Dashboard.currentDisplay = '.DashboardGettingStarted';
		}

		$('.DashboardPanelContentHelpUsing .DashboardHideThis').click(function() {
			$('.DashboardPanelContentHelpUsing').fadeOut('normal');
		});

		$('.DashboardRecentOrdersToggle li a').click(function() {
			if(Dashboard.activeRecentOrdersRequest) {
				return;
			}
			$('.DashboardRecentOrdersToggle li.Active').removeClass('Active');
			$(this).blur();
			$(this).css('width', $(this).width()+'px');
			$(this).parent('li').addClass('Active');
			$(this).addClass('Loading');
			link = this;
			args = $(this).attr('rel');
			Dashboard.activeRecentOrdersRequest = true;
			$('.DashboardRecentOrderList').load('remote.php?remoteSection=dashboard&w=LoadRecentOrders&'+args, '', function() {
				$(link).removeClass('Loading').css('width', '');
				Dashboard.activeRecentOrdersRequest = false;
			});
			return false;
		});

		$('#DashboardPanelPerformanceIndicators .DashboardPerformanceIndicatorsPeriodButton .Buttons a').click(function() {
			$('.DashboardPerformanceIndicatorsPeriodButton .Buttons a.Active').removeClass('Active');
			$(this).addClass('Active');
			link = this;
			args = $(this).attr('rel');
			indicator = LoadingIndicator.Show({background: '#fff', parent: '#DashboardPerformanceIndicators'});
			$('#DashboardPerformanceIndicators').load('remote.php?remoteSection=dashboard&w=GetPerformanceIndicators&'+args, '', function() {
				LoadingIndicator.Destroy(indicator);
			});
			return false;
		});

		$('#DashboardVersionCheck .HideLink').click(function() {
			period = $(this).attr('rel');
			if(period == undefined) {
				period = '';
			}
			Dashboard.HideVersionCheckMessage(period);
		});

		$('.DashboardHelpArticlesSearchForm').submit(function() {
			if($('.DashboardHelpSearchQuery', Dashboard.currentDisplay).val() == '') {
				alert('Please enter a search term.');
				$('.DashboardHelpSearchQuery', Dashboard.currentDisplay).focus();
				return false;
			}

			searchVal = $('.DashboardHelpSearchQuery', Dashboard.currentDisplay).val();
			searchUrl = $(this).attr('action').replace('%query%', escape(searchVal));
			window.open(searchUrl, 'help', 'width=650,height=550,left='+(screen.availWidth-700)+',top=100,resizable=1,scrollbars=1');
			return false;
		});

		$('.DashboardHelpSearchQuery')
			.focus(function() {
				$(this).removeClass('DashboardHelpSearchHasImage');
			})
			.blur(function() {
				if(!$(this).val()) {
					$(this).addClass('DashboardHelpSearchHasImage');
				}
			})
			.keypress(function(e) {
				if(e.keyCode == 14) {
					return $('.DashboardHelpArticlesSearchForm').submit();
				}
			})
		;

		$('.DashboardHelpArticlesSearchForm .DashboardActionButton').click(function() {
			$('.DashboardHelpArticlesSearchForm', Dashboard.currentDisplay).submit();
			return false;
		});

		$('.EmailIntegration_Export_Abort').click(function(event){
			event.preventDefault();

			if (!confirm(lang.ConfirmCancel)) {
				return;
			}

			var id = this.id.replace(/^EmailIntegration_Export_Abort_/, '');
			Dashboard.EmailIntegration_Export_Abort(id);
		});

		$('.Ebay_Export_Abort').click(function(event){
			event.preventDefault();

			if (!confirm(lang.ConfirmCancel)) {
				return;
			}

			var id = this.id.replace(/^Ebay_Export_Abort_/, '');
			Dashboard.Ebay_Export_Abort(id);
		});
	},

	versionCheckAttempts: 30,

	EmailIntegration_Export_Abort: function(id) {
		$.ajax({
			type: 'POST',
			url: 'remote.php',
			data: {
				remoteSection: 'emailintegration',
				w: 'moduleExport',
				exportStep: 'abort',
				exportId: id
			},
			complete: function () {
				location.href = 'index.php?_=' + (new Date()).getTime();
			}
		});
	},

	Ebay_Export_Abort: function(id) {
		$.ajax({
			type: 'POST',
			url: 'remote.php',
			data: {
				remoteSection: 'ebay',
				w: 'abortProductListing',
				jobId: id
			},
			complete: function () {
				location.href = 'index.php?_=' + (new Date()).getTime();
			}
		});
	},

	CheckLatestVersion: function()
	{
		// If the version details haven't loaded, carry on
		if(typeof(latest_version) == 'undefined' || latest_version == '') {
			--Dashboard.versionCheckAttempts;
			if(Dashboard.versionCheckAttempts >= 0) {
				window.setTimeout(Dashboard.CheckLatestVersion, 150);
			}
			return;
		}

		// New version doesn't match the current version we're running
		if(version_compare(currentVersionNumber, latest_version) == -1) {
			// Version checking has been disabled, return.
			if($('#DashboardVersionCheck').length == 0) {
				return;
			}

			// Update the version message
			$('#DashboardVersionCheck .LatestVersionNumber').html(latest_version);

			// Show the message
			$('#DashboardVersionCheck').show('fast');
		}
	},

	LoadVideoWalkthroughs: function()
	{
		if($('.DashboardPanelVideoWalkthrough').length < 1 || $('.DashboardPanelVideoWalkthrough').css('display') == 'none') {
			return false;
		}

		indicator = LoadingIndicator.Show({background: '#fff', parent: '.DashboardPanelVideoWalkthrough .VideoList'});
		$.ajax({
			url: 'remote.php?remoteSection=dashboard&w=loadVideoWalkthroughs',
			success: function(data) {
				if(!data) {
					$('.DashboardPanelVideoWalkthrough .VideoList').html('<p style="font-size: 130%; margin-top: 40px;">'+lang.VideosUnavailable+'</p><p>'+lang.VideosUnavailableLater+'</p>');
				}
				$('.DashboardPanelVideoWalkthrough .VideoList ul').html(data);
				LoadingIndicator.Destroy(indicator);
			},
			error: function() {
				$('.DashboardPanelVideoWalkthrough .VideoList').html('<p style="font-size: 130%; margin-top: 40px;">'+lang.VideosUnavailable+'</p><p>'+lang.VideosUnavailableLater+'</p>');
				LoadingIndicator.Destroy(indicator);
			}
		});
	},

	PlayVideo: function(url, width, height)
	{
		window.open(url, 'supportVideo', 'width='+width+', height='+height);
	},

	HideVersionCheckMessage: function(period)
	{
		if(period == undefined || period == 0) {
			var period = 365;
		}
		SetCookie('HideVersionCheck', $('#DashboardVersionCheck .LatestVersionNumber').html(), period);
		$('#DashboardVersionCheck').hide('fast');
	}
};

$(document).ready(function() {
	Dashboard.Init();
});