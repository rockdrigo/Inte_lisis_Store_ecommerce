<link rel="stylesheet" type="text/css" href="Styles/ebay.css" />

<div id="ModalTitle">
	<span>
		{% lang 'EndListingsFromEbayTitle' with ['totalSelected': itemCount] %}
	</span>
</div>

<div id="ModalContent">
	<div class="ListingMachine_StateContainer">
		<div class="ListingMachine_State_InputReason">
			<div class="ListingMachine_State_InputReasonIntro"><p>{% lang 'EndEbayListingIntro' %}</p></div>
			<div class="ListingMachine_State_EndSuccess" style="display:none;"><p>{% lang 'EndResultSuccessIntro' with ['totalSelected': itemCount] %}</p></div>
			<div class="ListingMachine_State_EndPartialSuccess" style="display:none;"><p>{% lang 'EndResultPartialSuccessIntro' %}</p></div>
			<div class="ListingMachine_State_EndFailure" style="display:none;"><p>{% lang 'EndResultFailureIntro' %}</p></div>
			<div class="ListingMachine_State_EndResultFailureNote" style="display:none;"><p>{% lang 'EndResultFailureNote' %}</p></div>
			<div id="listingContainer">
			</div>
		</div>
	</div>
</div>

<div class="ModalButtonRow CategoryMachine_ButtonRow">
	<button class="ListingMachine_CloseButton" accesskey="c" style="display:none;">{% lang 'Close' %}</button>
	&nbsp;
	<button class="ListingMachine_CancelButton" accesskey="c">{% lang 'Cancel' %}</button>
	&nbsp;
	<button class="ListingMachine_FinishButton" accesskey="s">
		{% lang 'EndListingsFromEbayButton' with ['totalSelected': itemCount] %}
	</button>
</div>
