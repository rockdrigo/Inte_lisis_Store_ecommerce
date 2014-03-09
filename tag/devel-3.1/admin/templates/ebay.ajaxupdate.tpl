<div class="ModalTitle">
	{% lang 'CacheUpdateName' %}
</div>
<div class="ModalContent" style="padding:5px;">
	<div id="cachingStatus">
		<div style="width: 208px; padding: 0px; margin: 10px auto 10px auto; position: relative; background: url('images/loadingAnimation.gif') no-repeat;">
			<div id="ProgressBarPercentage" style="margin: 0; padding: 0; height: 13px; width: 0%; background: url('images/progressbar.gif') no-repeat; background-color: transparent;">
				&nbsp;
			</div>
			<div style="position: absolute; top: 0; text-align: center; width: 208px; font-weight: bold;" id="ProgressPercent">&nbsp;</div>
		</div>
	</div>
</div>

<script type="text/javascript">//<![CDATA[
	var totalSites = {{ totalSites }};
	var currentProgress = 0;

	function UpdateAjaxEbayProgress(percentage) {
		$('#ProgressBarPercentage').css('width', percentage + "%");
		$('#ProgressPercent').html(Math.round(percentage) + "%");
	}
	$(document).ready(function() {
		UpdateAjaxEbayProgress(0);

		updateNextSite();
	});

	function updateNextSite() {
		$.ajax({
			url: 'remote.php',
			type: 'post',
			dataType: 'json',
			data: {
				remoteSection: 'ebay',
				w: 'ContinueEbayCacheUpdate'
			},
			success: function(response) {
				if (response.success) {
					if (response.done) {
						$.iModal.close();
						return;
					}

					currentProgress++;

					var currentPercent = currentProgress / totalSites * 100;
					UpdateAjaxEbayProgress(currentPercent);

					updateNextSite();
				}
				else {
					window.parent.location = 'index.php?ToDo=viewEbay&currentTab=2';
				}
			}
		});
	}
//]]></script>
