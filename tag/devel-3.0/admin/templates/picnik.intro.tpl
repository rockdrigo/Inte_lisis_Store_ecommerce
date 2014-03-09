<div class="ModalTitle">
	{% lang 'PicnikIntroTitle' %}
</div>
<div class="ModalContent">
	<div id="picnikIntroMessage"><p>
		<img src="images/picnik_logo.jpg" style="float:right; margin:0 0 4px 4px;" />
		{% lang 'PicnikIntro' %}<br />
		<br clear="all" />
	</p></div>

	<div id="picnikLoadingMessage" style="display:none;"><p>
		<img src="images/picnik_logo.jpg" style="float:right; margin:0 0 4px 4px;" />
		{% lang 'PicnikLoading' %}
		<br clear="all" />
	</p></div>

	<form method="post" id="picnikLaunchForm" action="{{ PicnikServiceUrl }}">
		<input type="hidden" name="_apikey" value="{{ PicnikApiKey }}" />
		<input type="hidden" name="_import" value="{{ PicnikImageUrl }}" />
		<input type="hidden" name="_export" value="{{ PicnikSaveHandler }}" />
		<input type="hidden" name="_export_title" value="{% lang 'PicnikSaveTitle' with [ 'storename': StoreName ] %}" />
		<input type="hidden" name="_export_agent" value="browser" />
		<input type="hidden" name="_close_target" value="{{ PicnikCloseHandler }}" />
		<input type="hidden" name="_exclude" value="out" />
	</form>

	<script language="javascript" type="text/javascript">//<![CDATA[
		var goPicnik = function () {
			// display loading message
			$('#picnikLoadingMessage').show();

			// hide intro message
			$('#picnikIntroMessage').hide();

			// hide checkbox
			$('#picnikShowMessageContainer').hide();
		};

		$('#picnikLaunchForm').submit(function(evt){
			goPicnik();

			// open a new window with a set size to submit the picnik form to
			var windowName = Common.Picnik.generateWindowName();
			var w = Common.Picnik.openPicnikWindow(windowName);

			// alter target window for submission
			$('#picnikLaunchForm').attr('target', windowName);
		});

		$(function(){
			$('#picnikCancelButton').click(function(){
				Common.Picnik.cancelEdit();
			});

			$('#picnikContinueButton').click(function(){
				// determine if message should be disabled in future
				if ($('#picnikShowMessageContainer').is(':visible') && $('#picnikShowMessage').is(':checked')) {
					// set a cookie to bypass the message in future
					SetCookie('iscbypasspicnikmessage', '1', 365);
				}

				$('#picnikLaunchForm').submit();
			});

			if (ReadCookie('iscbypasspicnikmessage') == '1') {
				goPicnik();
			}
		});
	//]]></script>
</div>
<div class="ModalButtonRow">
	<div style="float:left;" id="picnikShowMessageContainer">
		<input type="checkbox" id="picnikShowMessage" /> <label for="picnikShowMessage">{% lang 'PicnikShowMessage' %}</label>
	</div>
	<div style="float:right;">
		<input type="button" value="{% lang 'Cancel' %}" id="picnikCancelButton" class="SubmitButton" />
		<input type="button" value="{% lang 'PicnikContinue' %}" id="picnikContinueButton" class="SubmitButton" />
	</div>
	<br clear="all" />
</div>
