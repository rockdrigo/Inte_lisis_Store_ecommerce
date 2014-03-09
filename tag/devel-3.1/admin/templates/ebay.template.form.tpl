{% import "macros/util.tpl" as util %}
{% import "macros/forms.tpl" as form %}

<link rel="stylesheet" href="Styles/ebay.css" type="text/css" media="screen" />

<div id="content">
	<form action="index.php?ToDo=" method="post" id="templateForm" onsubmit="return false;" accept-charset="utf-8">
		<!-- Titles for each step -->
		<h1>
			{{ formTitle }}
			<span class="TemplateMachine_State_SelectEbaySite TemplateMachine_State_CheckTemplateName" style="display: none;">
				{% lang 'StepNumOfTotal' with ['step': 1, 'totalSteps': 2] %}
			</span>
			<span class="TemplateMachine_State_LoadTemplateDetails TemplateMachine_State_TemplateDetails TemplateMachine_State_TemplateDetailsFormNotLoaded" style="display: none;">
				{% lang 'StepNumOfTotal' with ['step': 2, 'totalSteps': 2] %}
			</span>
		</h1>

		<p>
			{{ lang.EbayCreateTemplateIntro|safe }}
		</p>

		{% if templateId %}
			<p class="MessageBox MessageBoxInfo TemplateMachine_State_SelectEbaySite TemplateMachine_State_CheckTemplateName" style="display: none;">
				{% lang 'ChangeEbaySiteWarning' %}
			</p>
		{% endif %}

		{% if templateId %}
			<p class="MessageBox MessageBoxInfo TemplateMachine_State_SelectCategories" style="display: none;">
				{% lang 'ChangeEbayCategoryWarning' %}
			</p>
		{% endif %}

		{{ Message|safe }}

		<!--Top navigation buttons -->
		<div class="TemplateMachine_State_SelectEbaySite TemplateMachine_State_CheckTemplateName TemplateMachine_State_TemplateDetails TemplateMachine_State_TemplateDetailsFormNotLoaded">
			{{ formBuilder.startButtonRow }}
				<button class="TemplateMachine_BackButton" disabled="disabled" accesskey="b">&lt; {% lang 'Back' %}</button>
				<button class="TemplateMachine_NextButton" accesskey="n">{% lang 'Next' %} &gt;</button>
				<button class="TemplateMachine_SaveButton" style="display:none;" accesskey="s">{% lang 'Save' %}</button>
				or <a href="#" class="TemplateMachine_CancelButton">{% lang 'Cancel' %}</a>
			{{ formBuilder.endButtonRow }}
		</div>

		<br />

		<!-- Actual content for each step -->

		<div class="TemplateMachine_State_SelectEbaySite TemplateMachine_State_CheckTemplateName" style="display: none;">
			<!-- Basic template details -->
			{{ form.startForm }}

			{{ form.heading(lang.EbayListingGeneralDetails) }}

			{{ form.startRow([ 'label': lang.EbayTemplateName, 'required': true ]) }}
				<input type="text" id="templateName" name="templateName" class="Field250" value="{{ templateName }}">
				<br />
				<small class="note">{{ lang.EbayTemplateNameEg }}</small>
			{{ form.endRow }}

			{{ form.startRow([ 'label': lang.EbayListProductOn, 'required': true ]) }}
				{{ form.select('siteId', ebaySites, siteId, ['class': 'Field250']) }}
				{{ util.tooltip('EbaySite', 'EbaySiteHelp') }}
			{{ form.endRow }}

			{{ form.startRow([ 'label': lang.PrivateListing ]) }}
				<input type="checkbox" id="privateListing" name="privateListing" value="checked" {% if isPrivateListing %}checked="checked"{% endif %} />
				<label for="privateListing">{{ lang.PrivateListingText }}</label>
				{{ util.tooltip('PrivateListing', 'PrivateListingHelp') }}
			{{ form.endRow }}

			{{ form.startRow([ 'label': lang.EbayTemplateAsDefault]) }}
				<input id="templateAsDefault" name="templateAsDefault" {% if templateIsDefault %}checked="checked"{% endif %} type="checkbox" value="1" />
				<label for="templateAsDefault">{{ lang.EbayTemplateYesDefault }}</label>
				{{ util.tooltip('EbayTemplateAsDefault', 'EbayTemplateDefaultHelp') }}
			{{ form.endRow }}

			{{ form.endForm }}


			<!-- Categories -->
			{{ form.startForm }}

			{{ form.heading(lang.EbaySelectCategories) }}

			{{ form.startRow([ 'label': lang.EbayPrimaryCategory, 'required': true ]) }}
				<div>
					<span id="primaryCategoryLabel">{% if primaryCategory %}{{ primaryCategory }}{% else %}{{ lang.CategoryText }}{% endif %}</span>&nbsp;<a href="#" id="primaryCategoryLink" onClick="EbayTemplate.ShowAddCategoryDialog('ebay', true);">{% lang 'Change' %}</a>
					{{ util.tooltip('EbayCategory', 'EbayCategoryHelp') }}
				</div>
			{{ form.endRow }}

			{{ form.startRow([ 'label': lang.EbaySecondaryCategory ]) }}
				<div style="padding-top: 5px;">
					<span id="secondaryCategoryLabel">{% if secondaryCategory %}{{ secondaryCategory }}{% else %}{{ lang.CategoryText }}{% endif %}</span>&nbsp;<a href="#" id="secondaryCategoryLink" onClick="EbayTemplate.ShowAddCategoryDialog('ebay', false);">{% lang 'Change' %}</a>
					{{ util.tooltip('EbayCategory', 'EbaySecondaryCategoryHelp') }}
				</div>
			{{ form.endRow }}

			{% if hasStore %}
				{{ form.startRow([ 'label': lang.EbayStoreCategory1 ]) }}
					<div style="padding-top: 5px;">
						<span id="primaryStoreCategoryLabel">{% if primaryStoreCategory %}{{ primaryStoreCategory }}{% else %}{{ lang.CategoryText }}{% endif %}</span>&nbsp;<a href="#" id="primaryStoreCategoryLink" onClick="EbayTemplate.ShowAddCategoryDialog('store', true);">{% lang 'Change' %}</a>
					</div>
				{{ form.endRow }}

				{{ form.startRow([ 'label': lang.EbayStoreCategory2 ]) }}
					<div style="padding-top: 5px;">
						<span id="secondaryStoreCategoryLabel">{% if secondaryStoreCategory %}{{ secondaryStoreCategory }}{% else %}{{ lang.CategoryText }}{% endif %}</span>&nbsp;<a href="#" id="secondaryStoreCategoryLink" onClick="EbayTemplate.ShowAddCategoryDialog('store', false);">{% lang 'Change' %}</a>
					</div>
				{{ form.endRow }}
			{% endif %}

			{{ form.endForm }}

			<!-- Category Features -->
			<div {% if not templateId %}style="display: none"{% endif %}>
				{{ form.startForm }}

				{{ form.heading(lang.EbaySupportedFeatures) }}

				<div id="categoryFeaturesList">
					{{ categoryFeaturesList|safe }}
				</div>

				{{ form.endForm }}
			</div>
		</div>

		<div class="TemplateMachine_State_LoadTemplateDetails" style="display: none; height: 292px; text-align: center;">
			<img width="100" height="100" src="../javascript/jquery/plugins/imodal/loading.gif" style="margin-top: 96px;" />
		</div>

		<div class="TemplateMachine_State_TemplateDetails" style="display: none;" id="templateDetailsContainer">
		</div>

		<div class="TemplateMachine_State_TemplateDetailsFormNotLoaded" style="display: none;">
			{{ form.startForm }}

			{{ form.heading(lang.EbayTemplateDetails) }}

			{{ form.startRow }}

			<br />
			{% lang 'ErrorLoadingEbayTemplateDetails' %}
			<br />
			<p id="templateLoadError" class="intro MessageBox MessageBoxInfo" style="display: none;"></p>
			{% lang 'MayTryAgainMessage' %}<br />

			{{ form.endRow }}

			{{ form.endForm }}
		</div>

		<!-- Bottom navigation buttons -->
		<div style="background-color: white; padding-left: 194px;" class="TemplateMachine_State_SelectEbaySite TemplateMachine_State_CheckTemplateName TemplateMachine_State_TemplateDetails TemplateMachine_State_TemplateDetailsFormNotLoaded">
			{{ formBuilder.startButtonRow }}
				<button class="TemplateMachine_BackButton" disabled="disabled" accesskey="b">&lt; {% lang 'Back' %}</button>
				<button class="TemplateMachine_NextButton" accesskey="n">{% lang 'Next' %} &gt;</button>
				<button class="TemplateMachine_SaveButton" style="display:none;" accesskey="s">{% lang 'Save' %}</button>
				or <a href="#" class="TemplateMachine_CancelButton">{% lang 'Cancel' %}</a>
			{{ formBuilder.endButtonRow }}
		</div>
	</form>
</div>

<script type="text/javascript">//<![CDATA[
	lang.ConfirmCancel = '{% lang 'ConfirmCancel' %}';
	lang.EnterTemplateName = '{% jslang  'EnterTemplateName' %}';
	lang.NoneSelected = '{% jslang 'NoneSelected' %}';
	lang.ProductCondMapOptional = '{% jslang 'ProductCondMapOptional' %}';
	lang.ProductCondMapMandatory = '{% jslang 'ProductCondMapMandatory' %}';
	lang.MappedFinished = '{% jslang 'MappedFinished' %}';
	lang.EbayCategorySelected = '{% jslang 'EbayCategorySelected' %}';
	lang.FinishAddCat = '{% jslang 'FinishAddCat' %}';
	lang.NextMapCond = '{% jslang 'NextMapCond' %}';
	lang.LoadingEbayCategoriesFailure = '{% jslang 'LoadingEbayCategoriesFailure' %}';
	lang.TryAgainMessage = '{% jslang 'TryAgainMessage' %}';
	lang.UnknownTemplateNameError = '{% jslang 'UnknownTemplateNameError' %}';
	lang.EnterPrimaryCategory = '{% jslang 'EnterPrimaryCategory' %}';
	lang.UnknownSavingTemplateError = '{% jslang 'UnknownSavingTemplateError' %}';
	lang.EbayProductsWithVariationsNotAllowed = '{% jslang 'EbayProductsWithVariationsNotAllowed' %}';
	lang.EbayProductsWithVariationsAllowed = '{% jslang 'EbayProductsWithVariationsAllowed' %}';
//]]></script>

<script type="text/javascript" src="../javascript/jquery/plugins/disabled/jquery.disabled.js?{{ JSCacheToken }}"></script>
<script type="text/javascript" src="../javascript/fsm.js?{{ JSCacheToken }}"></script>
<script type="text/javascript" src="script/ebay.js?{{ JSCacheToken }}"></script>
<script type="text/javascript" src="script/ebay.template.js?{{ JSCacheToken }}"></script>
<script type="text/javascript" src="script/ebay.selectcategory.js?{{ JSCacheToken }}"></script>

<script type="text/javascript">//<![CDATA[
	$(document).ready(function() {
		// intialize the template
		EbayTemplate.siteId = {{ siteId }};

		{% if templateId %}
			EbayTemplate.templateId = {{ templateId }};
			EbayTemplate.primaryCategoryOptions = {{ primaryCategoryOptions|safe }};
			EbayTemplate.secondaryCategoryOptions = {{ secondaryCategoryOptions|safe }};
			EbayTemplate.primaryStoreCategoryOptions = {{ primaryStoreCategoryOptions|safe }};
			EbayTemplate.secondaryStoreCategoryOptions = {{ secondaryStoreCategoryOptions|safe }};
		{% endif %}

		$("#siteId").change(function() {
			var newSiteId = parseInt($(this).val());

			if (newSiteId != EbayTemplate.siteId) {
				EbayTemplate.ResetTemplate();
				EbayTemplate.siteId = newSiteId;
			}
		});
		$("#siteId").change();

		{% if updateCache %}
			Ebay.StartAjaxEbayUpdate();
		{% endif %}
	});
//]]></script>
