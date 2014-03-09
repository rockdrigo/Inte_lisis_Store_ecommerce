	<p class="HelpInfo">{% lang 'EbayHelp' %}</p>
	<div class="BodyContainer">
		<input id="currentTab" name="currentTab" value="0" type="hidden">
		<input id="ebaystore" name="EbayStore" value="" type="hidden">
		<table class="OuterPanel">
		<tr>
			<td class="Heading1">
				{% lang 'Ebay' %}
			</td>
		</tr>
		<tr>
			<td>
				<table width="100%" border="0" cellpadding="0" cellspacing="0">
					<tr>
						<td class="Intro">
							{{ Message|safe }}
						</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td>
				<ul id="tabnav">
					<li style="{{ ShowTab|safe }}"><a href="#" id="tab0" onclick="ShowTab(0);return false;">{% lang 'LiveEbayListing' %}</a></li>
					<li style="{{ ShowTab|safe }}"><a href="#" id="tab1" onclick="ShowTab(1);return false;">{% lang 'EbayListingTemplate' %}</a></li>
					<li><a href="#" class="active" id="tab2" onclick="ShowTab(2);return false;">{% lang 'EbaySellingSettings' %}</a></li>
				</ul>
			</td>
		</tr>
		<tr>
			<td>
				<div id="div0" style="padding-top: 10px;{{ ShowTab|safe }}">
					<table id="IntroTable" cellspacing="0" cellpadding="0" width="100%">
						<tr>
							<td colspan="2" class="Intro">
								{{ ManageEbayLiveListingIntro|safe }}
								{{ EbayLiveListingMessage|safe }}
							</td>
						</tr>
						<tr>
							<td class="Intro" style="{{ ShowListingOptions|safe }}">
								<form id="ebaylistingaction" method="post">
									<select class="Field250" name="ListingActionSelect" id="ListingActionSelect" {{ DisableListingActionDropdown|safe }}>
										<option value="">{% lang 'ChooseAnAction' %}</option>
										<option value="removelistingref">{% lang 'RemoveListingsAction' %}</option>
										<option value="endlistingfromebay">{% lang 'EndListingsFromEbayAction' %}</option>
									</select>
									<input type="button" style="width: 40px;" class="FormButton" value="Go" name="EbayLiveListingActionButton" id="ebayLiveListingActionButton" {{ DisableListingActionDropdown|safe }}>
								</form>
							</td>
							<td class="SmallSearch" align="right">
								<table id="Table16" style="{{ DisplayListingSearch|safe }}">
									<tr>
										<form action="index.php?ToDo=viewEbay&currentTab=0{{ SortURL|safe }}" method="get" onsubmit="return ValidateForm(CheckSearchForm('listing'))">
										<td nowrap>
											<select class="Field150" name="listingType" id="ListingType">
												<option value="">{% lang 'AllListingTypes' %}</option>
												<option value="FixedPriceItem">{% lang 'FixedPriceItem' %}</option>
												<option value="Chinese">{% lang 'Chinese' %}</option>
											</select>
										</td>
										<td nowrap>
											<select class="Field150" name="listingStatus" id="ListingStatus">
												<option value="">{% lang 'AllListingStatuses' %}</option>
												<option value="active">{% lang 'Active' %}</option>
												<option value="pending">{% lang 'Pending' %}</option>
												<option value="sold">{% lang 'Sold' %}</option>
												<option value="unsold">{% lang 'Unsold' %}</option>
												<option value="won">{% lang 'Won' %}</option>
											</select>
										</td>
										<td nowrap>
											<input type="hidden" name="ToDo" value="viewEbay">
											<input type="hidden" name="currentTab" value="0">
											<input name="searchQueryListing" id="searchQueryListing" type="text" value="{{ ListingQuery|safe }}" id="SearchQuery" class="Button" size="20" />&nbsp;
											<input type="image" name="SearchButton" id="SearchButton" style="padding-left: 10px; vertical-align: top;" src="images/searchicon.gif" border="0" />
										</td>
										</form>
									</tr>
									<tr>
										<td align="right" style="padding-right:55pt" colspan="3">
											<a id="SearchClearButton" href="index.php?ToDo=viewEbay&currentTab=0">{% lang 'ClearResults' %}</a>
										</td>
									</tr>
									<tr>
										<td></td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
					<form id="deleteebaylivelisting" method="post" action="index.php?ToDo=deleteLocalEbayListing">
						<div class="GridContainer">
							{{ EbayListingDataGrid|safe }}
						</div>
					</form>
				</div>
				<div id="div1" style="padding-top: 10px;{{ ShowTab|safe }}">
					<table id="IntroTable" cellspacing="0" cellpadding="0" width="100%">
						<tr>
							<td colspan="2" class="Intro" >
								<div style="padding-bottom:5px;">{% lang 'ManageEbayTemplateIntro' %}</div>
								{{ EbayListingTemplateMessage|safe }}
							</td>
						</tr>
						<tr>
							<td>
								<input type="button" name="IndexAddButton" value="{% lang 'AddEbayTemplate' %}" id="IndexCreateButton" class="SmallButton" onclick="document.location.href='index.php?ToDo=addEbayTemplate'" /> &nbsp;
								<input type="button" name="IndexDeleteButton" value="{% lang 'DeleteSelected' %}" id="IndexDeleteButton" class="SmallButton" {{ DisableTemplateDelete|safe }} />
							</td>
							<td class="SmallSearch" align="right">
								<table id="Table16" style="{{ DisplayTemplateSearch|safe }}">
									<tr>
										<form action="index.php?ToDo=viewEbay&currentTab=1{{ SortURL|safe }}" method="get" onsubmit="return ValidateForm(CheckSearchForm('Template'))">
										<td nowrap>
											<input type="hidden" name="ToDo" value="viewEbay">
											<input type="hidden" name="currentTab" value="1">
											<input name="searchQueryTemplate" id="searchQueryTemplate" type="text" value="{{ TemplateQuery|safe }}" id="SearchQuery" class="Button" size="20" />&nbsp;
											<input type="image" name="SearchButton" id="SearchButton" style="padding-left: 10px; vertical-align: top;" src="images/searchicon.gif" border="0" />
											&nbsp;<a id="SearchClearButton" href="index.php?ToDo=viewEbay&currentTab=1" style="position:relative; top:-3px">{% lang 'ClearResults' %}</a>
										</td>
										</form>
									</tr>
								</table>
							</td>
						</tr>
					</table>
					<form id="deleteebaytemplate" action="index.php?ToDo=DeleteEbayTemplate" method="post">
						<div class="GridContainer">
							{{ EbayTemplateDataGrid|safe }}
						</div>
					</form>
				</div>
				<div id="div2" style="padding-top: 10px;">
					<form action="index.php?ToDo={{ FormActionEbayConf|safe }}" method="post" onsubmit="return Ebay.CheckManageEbayForm();">
						<table width="100%" class="IntroTable">
							<tr>
								<td class="Intro">
									{% lang 'EbaySettingsIntro' %}
									{{ EbayConfigMessage|safe }}
								</td>
							</tr>
							<tr>
								<td style="padding-top: 3px;">

										<input type="submit" name="SubmitButton1" value="{% lang 'Save' %}" class="FormButton" />&nbsp;
										<input type="button" name="CancelButton1" value="{% lang 'Cancel' %}" class="FormButton" />

								</td>
							</tr>
						</table>
						<table width="100%" class="Panel">
							<tr>
								<td class="Heading2" colspan="2">{% lang 'Ebay' %}</td>
							</tr>
							<tr>
								<td class="FieldLabel">
									<span class="Required">*</span>&nbsp;{% lang 'EbayDevId' %}:
								</td>
								<td>
									<input type="text" id="ebaydevid" name="EbayDevId" class="Field250" value="{{ EbayDevId|safe }}">
									<img onmouseout="HideHelp('d0');" onmouseover="ShowHelp('d0', '{% lang 'EbayDevId' %}', '{% lang 'EbayDevIdHelp' %}')" src="images/help.gif" width="24" height="16" border="0" style="margin-top: 5px;" />
									<div style="display:none" id="d0"></div>
								</td>
							</tr>
							<tr>
								<td class="FieldLabel">
									<span class="Required">*</span>&nbsp;{% lang 'EbayAppId' %}:
								</td>
								<td>
									<input type="text" id="ebayappid" name="EbayAppId" class="Field250" value="{{ EbayAppId|safe }}">
									<img onmouseout="HideHelp('d1');" onmouseover="ShowHelp('d1', '{% lang 'EbayAppId' %}', '{% lang 'EbayAppIdHelp' %}')" src="images/help.gif" width="24" height="16" border="0" style="margin-top: 5px;" />
									<div style="display:none" id="d1"></div>
								</td>
							</tr>
							<tr>
								<td class="FieldLabel">
									<span class="Required">*</span>&nbsp;{% lang 'EbayCertId' %}:
								</td>
								<td>
									<input type="text" id="ebaycertid" name="EbayCertId" class="Field250" value="{{ EbayCertId|safe }}">
									<img onmouseout="HideHelp('d2');" onmouseover="ShowHelp('d2', '{% lang 'EbayCertId' %}', '{% lang 'EbayCertIdHelp' %}')" src="images/help.gif" width="24" height="16" border="0" style="margin-top: 5px;" />
									<div style="display:none" id="d2"></div>
								</td>
							</tr>
							<tr>
								<td class="FieldLabel">
									<span class="Required">*</span>&nbsp;{% lang 'EbayUserToken' %}:
								</td>
								<td>
									<input type="text" id="ebayusertoken" name="EbayUserToken" class="Field250" value="{{ EbayUserToken|safe }}">
									<img onmouseout="HideHelp('d3');" onmouseover="ShowHelp('d3', '{% lang 'EbayUserToken' %}', '{% lang 'EbayUserTokenHelp' %}')" src="images/help.gif" width="24" height="16" border="0" style="margin-top: 5px;" />
									<div style="display:none" id="d3"></div>
								</td>
							</tr>
							<tr>
								<td class="FieldLabel">
									<span class="Required">*</span>&nbsp;{% lang 'EbayDefaultSite' %}:
								</td>
								<td>
									<select name="EbayDefaultSite" id="ebaydefaultsite" class="Field250">
										{{ EbayDefaultSite|safe }}
									</select>
									<img onmouseout="HideHelp('d4');" onmouseover="ShowHelp('d4', '{% lang 'EbayDefaultSite' %}', '{% lang 'EbayDefaultSiteHelp' %}')" src="images/help.gif" width="24" height="16" border="0" style="margin-top: 5px;" />
									<div style="display:none" id="d4"></div>
								</td>
							</tr>
							<tr>
								<td class="FieldLabel">
									<span class="Required">*</span>&nbsp;{{ lang.EbayTestMode }}:
								</td>
								<td>
									<select name="EbayTestMode" id="EbayTestMode" class="Field250">
										<option id="productionSite" {{ DisableProd|safe }} value="production" {% if EbayTestMode == 'production' %}selected="selected"{% endif %}>{{ lang.EbayModeProduction }}</option>
										<option value="sandbox" {% if EbayTestMode == 'sandbox' %}selected="selected"{% endif %}>{{ lang.EbayModeSandbox }}</option>
									</select>
									<img onmouseout="HideHelp('d4');" onmouseover="ShowHelp('d4', '{% jslang 'EbayTestMode' %}', '{% jslang 'EbayTestModeHelp' %}')" src="images/help.gif" width="24" height="16" border="0" style="margin-top: 5px;" />
									<div style="display:none" id="d4"></div>
								</td>
							</tr>
							<tr>
								<td class="FieldLabel">
									&nbsp;&nbsp;&nbsp;{% lang 'EbayStore' %}:
								</td>
								<td>
									{{ EbayStoreDisplay|safe }}
									<img onmouseout="HideHelp('d5');" onmouseover="ShowHelp('d5', '{% lang 'EbayStore' %}', '{% lang 'EbayStoreHelp' %}')" src="images/help.gif" width="24" height="16" border="0" style="margin-top: 5px;" />
									<div style="display:none" id="d5"></div>
								</td>
							</tr>
							<tr>
								<td class="Gap"></td>
							</tr>
						</table>
					</form>
				</div>
			</td>
		</tr>
	</table>
</div>
</form>
<script type="text/javascript" src="../javascript/jquery/plugins/disabled/jquery.disabled.js?{{ JSCacheToken }}"></script>
<script type="text/javascript" src="../javascript/fsm.js?{{ JSCacheToken }}"></script>
<script type="text/javascript" src="script/ebay.js?{{ JSCacheToken }}"></script>
<script type="text/javascript" src="script/ebay.endlisting.js?{{ JSCacheToken }}"></script>
<script type="text/javascript">//<![CDATA[
$(document).ready(function() {
	ShowTab({{ CurrentTab|safe }});

	$('#checkstore').click(function() {
		Ebay.GetEbayStore();
		return false;
	});

	$('#IndexDeleteButton').click(function() {
		if ($('.EbayTemplate:checked').length < 1) {
			alert ("{% lang 'ChooseTemplate' %}")
			return false;
		}

		if (confirm("{% lang 'ConfirmDeleteTemplate' %}")) {
			$('#deleteebaytemplate').submit();
		}
	});
	$("#ListingType").val('{{ListingType}}');
	$("#ListingStatus").val('{{ListingStatus}}');


	{% if updateCache %}
		Ebay.StartAjaxEbayUpdate();
	{% endif %}
});

Ebay.EventsInit();

lang.EbayEnterDevId = '{% jslang 'EbayEnterDevId' %}';
lang.EbayEnterAppID = '{% jslang 'EbayEnterAppID' %}';
lang.EbayEnterCertID = '{% jslang 'EbayEnterCertID' %}';
lang.EbayEnterUserToken = '{% jslang 'EbayEnterUserToken' %}';
lang.EbayEnterDefaultSiteId = '{% jslang 'EbayEnterDefaultSiteId' %}';
lang.ChooseActionFirst = '{% jslang 'ChooseActionFirst' %}';
lang.LoadDialogFailed = '{% jslang 'LoadDialogFailed' %}';
lang.UnknownErrorRetrieveData = '{% jslang 'UnknownErrorRetrieveData' %}';
lang.ChooseListing = '{% jslang 'ChooseListing' %}';
lang.ConfirmRemoveListing = '{% jslang 'ConfirmRemoveListing' %}';
lang.ConfirmRemoveListings = '{% jslang 'ConfirmRemoveListings' %}';
lang.ConfirmEndListing = '{% jslang 'ConfirmEndListing' %}';
lang.ConfirmCancelEbaySettings = '{% lang 'ConfirmCancelEbaySettings' %}';
lang.SelectAllEndReason = '{% jslang 'SelectAllEndReason' %}';
lang.EbaySandboxOnly = '{% jslang 'EbaySandboxOnly' %}';

function CheckSearchForm(section) {
	if ($('#searchQuery' + section).val() == '') {
		alert("{% lang 'EnterSearchTerm' %}");
		$('#searchQuery' + section).focus()
		return false;
	}
	return true;
}
//]]></script>