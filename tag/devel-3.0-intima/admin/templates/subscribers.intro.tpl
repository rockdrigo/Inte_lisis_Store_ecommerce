<div class="ModalTitle">
	{% lang 'NewsletterSubscribers' %}
</div>
<div class="ModalContent">
	<div id="exportIntro" style="display: {{ HideExportIntro|safe }}">
		<p>
			{% lang 'SubscribersListIntro' %}
		</p>
		<table border="0">
			<tr>
				<td width="1"><img src="images/subscriber.gif" height="16" width="16" hspace="5" alt="" /></td>
				<td><a href="#" onclick="StartSubscribersExport(); return false;"  style="color:#005FA3; font-weight:bold">{% lang 'GenerateSubscribersList' %}</a></td>
			</tr>
		</table>
	</div>
	<div id="exportStatus" style="display: none;">
		<p>
			{% lang 'SubscribersListGeneratingIntro' %}
		</p>
		<div style="border: 1px solid #ccc; width: 300px; padding: 0px; margin: 0 auto; position: relative;">
			<div id="subscriberProgressBarPercentage" style="margin: 0; padding: 0; background: url(images/progressbar.gif) no-repeat; height: 20px; width: 0%;">
				&nbsp;
			</div>
			<div style="position: absolute; top: 0; left: 0; text-align: center; width: 300px; font-weight: bold;" id="subscriberProgressPercent">&nbsp;</div>
		</div>
		<div id="subscriberProgressBarStatus" style="text-align: center; font-size: 11px; font-family: Tahoma;">{% lang 'GeneratingSubscribersList' %}</div>
	</div>
	<div id="exportComplete" style="display: none;">
		<p>
			{% lang 'SubscribersListGeneratedIntro' %}
		</p>
		<table border="0">
			<tr>
				<td width="1"><img src="images/save.gif" height="16" width="16" hspace="5" alt="" /></td>
				<td><a href="#" onclick="DownloadSubscribersList(); return false;"  style="color:#005FA3; font-weight:bold">{% lang 'DownloadSubscribersList' %}</a></td>
			</tr>
		</table>
	</div>
	<div id="exportNoSubscribers" style="display: {{ HideNoSubscribers|safe }};">
		<p>
			{% lang 'SubscribersListIntro' %}
		</p>
		<table border="0">
			<tr>
				<td width="1" valign="top"><img src="images/error.gif" height="16" width="16" hspace="5" alt="" /></td>
				<td style="font-weight: bold;">{% lang 'NoSubscribers' %}</td>
			</tr>
		</table>
		<br />
	</div>
</div>
<div class="ModalButtonRow">
	<input type="button" value="{% lang 'Close' %}" onclick="$.iModal.close()" class="FormButton" />
</div>
<script type="text/javascript">
	function StartSubscribersExport() {
		$('#exportStatus').show();
		$('#exportIntro').hide();
		if(g('subscriberExportFrame')) {
			$('#subscriberExportFrame').remove();
		}
		$('#exportStatus').append('<iframe src="index.php?ToDo=exportSubscribers" border="0" frameborder="0" height="1" width="1" id="subscriberExportFrame"></iframe>');
	}

	function SubscribersExportError(msg) {
		alert(msg);
	}

	function UpdateSubscribersExportProgress(percentage) {
		$('#subscriberProgressBarPercentage').css('width', parseInt(percentage) + "%");
		$('#subscriberProgressPercent').html(parseInt(percentage) + "%");
	}

	function SubscribersExportComplete() {
		$('#exportStatus').hide();
		$('#exportComplete').show();
	}

	function CancelSubscribersExport() {
		if($('#exportStatus').css('display') != "none") {
			window.location = 'index.php?ToDo=cancelSubscribersExport';
		}
	}

	function DownloadSubscribersList() {
		$.iModal.close();
		window.location = 'index.php?ToDo=downloadSubscribersExport';
	}
</script>
