{% import "macros/util.tpl" as util %}

<link rel="stylesheet" type="text/css" href="Styles/ebay.css" />

<div id="ModalTitle">
	<span class="ListMachine_State_SelectTemplate" style="display:none;">
		{% lang 'EbayListingTitle' with ['count': productCount, 'step': 1, 'totalSteps': 4] %}
	</span>
	<span class="ListMachine_State_GetCategoryFeatures ListMachine_State_CategoryFeatures ListMachine_State_CategoryFeaturesFailed ListMachine_State_CategoryFeaturesInvalidProducts" style="display:none;">
		{% lang 'EbayListingTitle' with ['count': productCount, 'step': 2, 'totalSteps': 4] %}
	</span>
	<span class="ListMachine_State_GetEstimatedCosts ListMachine_State_EstimatedCosts ListMachine_State_EstimatedCostsFailed" style="display:none;">
		{% lang 'EbayListingTitle' with ['count': productCount, 'step': 3, 'totalSteps': 4] %}
	</span>
	<span class="ListMachine_State_ListProductsStarted ListMachine_State_ListProductsStartFailed ListMachine_State_ListProductsFinished ListMachine_State_ListProductsCancelled" style="display:none;">
		{% lang 'EbayListingTitle' with ['count': productCount, 'step': 4, 'totalSteps': 4] %}
	</span>
	<span class="ListMachine_State_NoProductsSelected" style="display: none;">
		{{ lang.EbayListingErrorTitle }}
	</span>
</div>

<div id="ModalContent">
	<div class="ListMachine_StateContainer">
		<div class="ListMachine_State_NoProductsSelected" style="display: none;">
			<div class="MessageBox MessageBoxInfo">{{ lang.EbayListingNoValidProductsSelected }}</div>
		</div>

		<div class="ListMachine_State_SelectTemplate" style="display:none;">
			<p>{{ lang.EbayListingChooseOptions }}</p>
			{{ lang.EbayListingListingTemplate }}:
			<br />
			<select class="Field350 ListMachine_SelectTemplate">
				<option value="">{{ lang.EbayListingChooseTemplateOption }}</option>
				{% for id, name in templates %}
					<option value="{{ id }}">{{ name }}</option>
				{% endfor %}
			</select>
			<br />
			<br />
			{{ lang.EbayListingListingDate }}:
			<br />
			<label><input type="radio" name="listingDate" value="now" checked="checked" /> {{ lang.EbayListingSetLive }}</label>
			<br />
			<label><input type="radio" name="listingDate" value="schedule" /> {{ lang.EbayListingSetSchedule }}</label>
			<div class="NodeJoin" style="display: none;">
				<img src="images/nodejoin.gif" style="vertical-align: middle;" alt="" />
				<input type="text" class="Field80" id="scheduleDate" name="scheduleDate" />
				<select name="timeHour" id="timeHour">
					<option value="1">1</option>
					<option value="2">2</option>
					<option value="3">3</option>
					<option value="4">4</option>
					<option value="5">5</option>
					<option value="6">6</option>
					<option value="7">7</option>
					<option value="8">8</option>
					<option value="9">9</option>
					<option value="10">10</option>
					<option value="11">11</option>
					<option value="12" selected="selected">12</option>
				</select>
				<select name="timeMinutes" id="timeMinutes">
					<option value="0" selected="selected">00</option>
					<option value="15">15</option>
					<option value="30">30</option>
					<option value="45">45</option>
				</select>
				<select name="timeAMPM" id="timeAMPM">
					<option value="am">AM</option>
					<option value="pm" selected="selected">PM</option>
				</select>
			</div>

			<br />
			<br />

			<div class="MessageBox MessageBoxInfo">{{ lang.EbayListingProductWarning }}</div>
		</div>

		<div class="ListMachine_State_CategoryFeatures" style="display: none;">
			<p>{{ lang.EbayListingCategoryFeatures|safe }}</p>

			<div class="categoryFeatures">
			</div>
		</div>

		<div class="ListMachine_State_CategoryFeaturesInvalidProducts" style="display: none;">
			<p>{{ lang.EbayListingCategoryFeaturesInvalidProducts|safe }}</p>

			<div class="categoryFeatures">
			</div>
		</div>

		<div class="ListMachine_State_CategoryFeaturesFailed" style="display: none;">
			<p>{{ lang.EbayListingCategoryFeaturesError }}</p>

			<p>{{ lang.EbayListingErrorGoBack }}</p>
		</div>

		<div class="ListMachine_State_GetEstimatedCosts" style="display: none;">
			<br />
			{{ lang.EbayListingRetrievingCosts }}</br>
		</div>

		<div class="ListMachine_State_EstimatedCosts" style="display: none;">
			<p>{% lang 'EbayListingEstimatedCostsIntro' with ['count': productCount] %}</p>
			<div id="estimatedCostsContent"></div>

			<p>
				{{ lang.EbayListingEstimatedCostsNB|safe }}
			</p>
		</div>

		<div class="ListMachine_State_EstimatedCostsFailed" style="display: none;">
			<p>{{ lang.EbayListingEstimatedCostsError }}</p>
			<p id="estimatedCostsMessage" class="intro MessageBox MessageBoxInfo" style="display: none;"></p>
			<p>{{ lang.EbayListingErrorGoBack }}</p>
		</div>

		<div class="ListMachine_State_ListProductsStarted" style="display:none;">
			<p>{{ lang.EbayListingStarted }}</p>
			<div id="listingProgress"><span id="listingProgressLabel">0%</span></div>
			<p id="listingProgressETA"></p>
		</div>

		<div class="ListMachine_State_ListProductsStartFailed" style="display:none;">
			<p>{{ lang.EbayListingIntializingError }}</p>
			<p id="jobFailedMessage" class="intro MessageBox MessageBoxInfo" style="display: none;"></p>
			<p>{{ lang.EbayListingErrorGoBack }}</p>
		</div>

		<div class="ListMachine_State_ListProductsFinished" style="display: none;">
			<div class="MessageBox MessageBoxSuccess">{{ lang.EbayListingFinished }}</div>
		</div>

		<div class="ListMachine_State_ListProductsCancelled" style="display: none;">
			<p>{{ lang.EbayListingCancelled }}</p>
		</div>
	</div>
</div>

<div class="ModalButtonRow ListMachine_ButtonRow">
	<div style="float:left;">
		<button class="ListMachine_CancelButton CancelButton" disabled="disabled" accesskey="c">{{ lang.Cancel }}</button>
	</div>
	<div style="float:right">
		<button class="ListMachine_BackButton" disabled="disabled" accesskey="b">&lt; {{ lang.Back }}</button>
		<button class="ListMachine_NextButton" disabled="disabled" accesskey="n">{{ lang.Next }} &gt;</button>
		<button class="ListMachine_ListButton" style="display:none;" accesskey="l">{{ lang.EbayListingListOnEbay }}</button>
		<button class="ListMachine_AbortButton" style="display:none;" accesskey="a">{{ lang.EbayListingAbort }}</button>
		<button class="ListMachine_CloseButton" style="display:none;" accesskey="o">{{ lang.Close }}</button>
		<button class="ListMachine_LinkToLiveListButton" style="display:none;" accesskey="v">{{ lang.ViewYourEbayListing }}</button>
	</div>
</div>

<script type="text/javascript">//<![CDATA[
	lang.EbayConfirmCancelListing = '{% jslang 'EbayConfirmCancelListing' %}';

	var fsm = Interspire_Ebay_ListProductsMachine;
	fsm.payload.productCount = {{ productCount }};
	fsm.payload.productOptions = {{ productOptions|safe }};
	fsm.payload.originalProductCount = {{ productCount|safe }};

	$(document).ready(function() {
		$("input:radio[name='listingDate']").change(function() {
			if ($(this).val() == 'schedule') {
				$(this).parent('label').nextAll('.NodeJoin:first').show();
			}
			else {
				$(this).parent('label').nextAll('.NodeJoin:first').hide();
			}
		});

		$('#scheduleDate').datepicker({
			showOn: 'both',
			buttonImage: 'images/calendar.gif',
			buttonImageOnly: true,
			minDate: new Date(),
			defaultDate: new Date(),
			firstDay: 1,
			duration: '',
			dateFormat: 'mm/dd/yy'
		});

		$("#scheduleDate").datepicker('setDate', new Date());

		$("#listingProgress").progressbar();
	});

	function showExtraFees() {
		alert(fsm.payload.extraFees);
	}

	$('.ListMachine_AbortButton').click(function() {
		if (!confirm(lang.EbayConfirmCancelListing)) {
			return false;
		}

		$.ajax({
			url: 'remote.php',
			type: 'post',
			dataType: 'json',
			global: false,
			data: {
				remoteSection: 'ebay',
				w: 'abortProductListing',
				jobId: fsm.payload.jobId
			},
			complete: function() {
				fsm.transition('ListingCancelled');
			}
		});
	});
//]]></script>
