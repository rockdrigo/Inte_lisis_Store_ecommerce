<link rel="stylesheet" href="Styles/tax.settings.css" type="text/css" charset="utf-8" />
{% import "macros/util.tpl" as util %}
{% import "macros/forms.tpl" as formBuilder %}

<div id="content">
	<h1>{{ lang.TaxSettings }}</h1>
	<p class="intro">
		{{ lang.TaxSettingsIntro }}
	</p>

	{{ flashMessages|safe }}

	{% if pendingChangesToApply %}
		<p class="MessageBox MessageBoxInfo">{% lang 'ApplyPendingTaxChanges' %}</p>
	{% endif %}

	<div id="taxSettings">
		{{ util.tabs([
			'generalTab': lang.General,
			'taxClassesTab': lang.TaxClasses,
			'taxZonesTab': lang.TaxRatesAndZones
		]) }}

		<div id="generalTab">
			<form action="index.php" method="post" accept-charset="utf-8" id="taxSettingsForm">
				<input type="hidden" name="ToDo" value="saveTaxSettings" />
				{{ formBuilder.startForm }}
					{{ formBuilder.heading(lang.ConfigureTaxOptions) }}

					{{ formBuilder.startRow([
						'label': lang.TaxLabel ~ ':',
						'required': true
					]) }}
						{{ formBuilder.input('text', 'taxLabel', settings.taxLabel, [
							'class': 'Field300'
						]) }}
						{{ util.tooltip('TaxLabel', 'TaxLabelHelp') }}
					{{ formBuilder.endRow }}

					{{ formBuilder.startRow([
						'label': lang.PricesEnteredWithTax ~ '?',
						'required': true
					]) }}
						{{ formBuilder.radioList('taxEnteredWithPrices', [
							1: lang.PricesEnteredWithTaxYes,
							0: lang.PricesEnteredWithTaxNo
						], settings.taxEnteredWithPrices) }}
						{{ util.tooltip('PricesEnteredWithTax', 'PricesEnteredWithTaxHelp') }}
					{{ formBuilder.endRow }}

					{{ formBuilder.startRow([
						'label': lang.CalculateTaxBasedOn ~ ':',
						'required': true
					]) }}
						{{ formBuilder.select('taxCalculationBasedOn', [
							0: lang.BillingAddress,
							1: lang.ShippingAddress,
							2: lang.StoreAddress
						], settings.taxCalculationBasedOn, [
							'class': 'Field300'
						]) }}
						{{ util.tooltip('CalculateTaxBasedOn', 'CalculateTaxBasedOnHelp') }}
					{{ formBuilder.endRow }}

					{{ formBuilder.startRow([
						'label': lang.ShippingTaxClass ~ ':',
						'required': true
					]) }}
						{{ formBuilder.select('taxShippingTaxClass', taxClasses, settings.taxShippingTaxClass, [
							'class': 'Field300'
						]) }}
						{{ util.tooltip('ShippingTaxClass', 'ShippingTaxClassHelp') }}
					{{ formBuilder.endRow }}

					{{ formBuilder.startRow([
						'label': lang.GiftWrappingTaxClass ~ ':',
						'required': true
					]) }}
						{{ formBuilder.select('taxGiftWrappingTaxClass', taxClasses, settings.taxGiftWrappingTaxClass, [
							'class': 'Field300'
						]) }}
						{{ util.tooltip('GiftWrappingTaxClass', 'GiftWrappingTaxClassHelp') }}
					{{ formBuilder.endRow }}
				{{ formBuilder.endForm }}

				{{ formBuilder.startForm }}
					{{ formBuilder.heading(lang.ConfigureTaxDisplaySettings) }}

					{{ formBuilder.startRow([
						'label': lang.ShowPricesOnProductListings ~ ':',
						'required': true
					]) }}
						{{ formBuilder.select('taxDefaultTaxDisplayCatalog', [
							0: lang.PricesShouldBeInclusive,
							1: lang.PricesShouldBeExclusive,
							2: lang.PricesShouldBeBoth
						], settings.taxDefaultTaxDisplayCatalog, [
							'class': 'Field300'
						]) }}
						{{ util.tooltip('ShowPricesOnProductListings', 'ShowPricesOnProductListingsHelp') }}
					{{ formBuilder.endRow }}

					{{ formBuilder.startRow([
						'label': lang.ShowPricesOnProductDetailPages ~ ':',
						'required': true
					]) }}
						{{ formBuilder.select('taxDefaultTaxDisplayProducts', [
							0: lang.PricesShouldBeInclusive,
							1: lang.PricesShouldBeExclusive,
							2: lang.PricesShouldBeBoth
						], settings.taxDefaultTaxDisplayProducts, [
							'class': 'Field300'
						]) }}
						{{ util.tooltip('ShowPricesOnProductDetailPages', 'ShowPricesOnProductDetailPagesHelp') }}
					{{ formBuilder.endRow }}

					{{ formBuilder.startRow([
						'label': lang.ShowPricesInCart ~ ':',
						'required': true
					]) }}
						{{ formBuilder.select('taxDefaultTaxDisplayCart', [
							0: lang.PricesShouldBeInclusive,
							1: lang.PricesShouldBeExclusive,
						], settings.taxDefaultTaxDisplayCart, [
							'class': 'Field300'
						]) }}
						{{ util.tooltip('ShowPricesInCart', 'ShowPricesInCartHelp') }}
					{{ formBuilder.endRow }}

					{{ formBuilder.startRow([
						'label': lang.ShowTaxChargesInCart ~ ':',
						'required': true
					]) }}
						{{ formBuilder.select('taxChargesInCartBreakdown', [
							0: lang.ShowTaxChargesSummarized,
							1: lang.ShowTaxChargesBrokenDown
						], settings.taxChargesInCartBreakdown, [
							'class': 'Field300'
						]) }}
						{{ util.tooltip('ShowTaxChargesInCart', 'ShowTaxChargesInCartHelp') }}
					{{ formBuilder.endRow }}

					{{ formBuilder.startRow([
						'label': lang.ShowPricesOnOrdersInvoices ~ ':',
						'required': true
					]) }}
						{{ formBuilder.select('taxDefaultTaxDisplayOrders', [
							0: lang.PricesShouldBeInclusive,
							1: lang.PricesShouldBeExclusive,
						], settings.taxDefaultTaxDisplayOrders, [
							'class': 'Field300'
						]) }}
						{{ util.tooltip('ShowPricesOnOrdersInvoices', 'ShowPricesOnOrdersInvoicesHelp') }}
					{{ formBuilder.endRow }}

					{{ formBuilder.startRow([
						'label': lang.ShowTaxChargesOnOrders ~ ':',
						'required': true
					]) }}
						{{ formBuilder.select('taxChargesOnOrdersBreakdown', [
							0: lang.ShowTaxChargesSummarized,
							1: lang.ShowTaxChargesBrokenDown
						], settings.taxChargesOnOrdersBreakdown, [
							'class': 'Field300'
						]) }}
						{{ util.tooltip('ShowTaxChargesOnOrders', 'ShowTaxChargesOnOrdersHelp') }}
					{{ formBuilder.endRow }}
				{{ formBuilder.endForm }}

				{{ formBuilder.startForm }}
					<div class="header">
						{{ lang.ConfigureDefaultTaxAddress }}
						{{ util.tooltip('ConfigureDefaultTaxAddress', 'ConfigureDefaultTaxAddressHelp') }}
					</div>

					{{ formBuilder.startRow([
						'label': lang.Country ~ ':',
						'required': true
					]) }}
						{{ formBuilder.select('taxDefaultCountry', countryList, settings.taxDefaultCountry, [
							'class': 'Field250'
						]) }}
					{{ formBuilder.endRow }}

					{{ formBuilder.startRowGroup([
						'class': 'defaultStateRow',
						'hidden': (stateList|length <= 1)
					]) }}
						{{ formBuilder.startRow([
							'label': lang.State ~ ':',
							'required': true
						]) }}
							{{ formBuilder.select('taxDefaultState', stateList, settings.taxDefaultState, [
								'class': 'Field250'
							]) }}
						{{ formBuilder.endRow }}
					{{ formBuilder.endRowGroup }}

					{{ formBuilder.startRow([
						'label': lang.ZipCode ~ ':'
					]) }}
						{{ formBuilder.input('text', 'taxDefaultZipCode', settings.taxDefaultZipCode, [
							'class': 'Field50'
						]) }}
					{{ formBuilder.endRow }}

					{{ formBuilder.startButtonRow }}
						<input type="submit" class="saveButton" value="{{ lang.Save }}" />
						{{ lang.Or }}
						<a href="#" class="cancelLink">{{ lang.Cancel }}</a>
					{{ formBuilder.endButtonRow }}
				{{ formBuilder.endForm }}
			</form>
		</div>

		<div id="taxClassesTab">
			<form action="index.php" method="post" accept-charset="utf-8">
				<input type="hidden" name="ToDo" value="saveTaxClasses" />
				{{ formBuilder.startForm(['type':'vertical']) }}

					{{ formBuilder.heading(lang.CreateOrModifyTaxClasses) }}
					{{ formBuilder.intro(lang.CreateOrModifyTaxClassesIntro) }}

					{% for id, name in taxClasses %}
						{{ formBuilder.startRow }}
							<input type="text" name="taxClass[existing][{{ id }}]" value="{{ name }}" class="Field400 taxClassLabel"
								{% if loop.first %}readonly="readonly"{% endif %} />
							<a class="addButton">+</a>
							<a class="removeButton" {% if loop.first %}style="display: none"{% endif %}>-</a>
						{{ formBuilder.endRow }}
					{% endfor %}

					{{ formBuilder.startButtonRow }}
						<input type="submit" class="saveButton" value="{{ lang.Save }}" />
						{{ lang.Or }}
						<a href="#" class="cancelLink">{{ lang.Cancel }}</a>
					{{ formBuilder.endButtonRow }}
				</div>
			</form>
		</div>

		<div id="taxZonesTab">
			<form action="index.php" method="post" accept-charset="utf-8">
				<input type="hidden" name="ToDo" value="deleteTaxZones" />
				<p class="intro">
					<input type="button" value="{{ lang.AddATaxZoneButton }}" class="addTaxZoneButton" />
					<input type="submit" value="{{ lang.DeleteSelected }}" class="deleteTaxZonesButton" />
				</p>

				<div class="GridContainer">
					{{ taxZoneGrid|safe }}
				</div>
			</form>
		</div>
	</div>
</div>
<script src="script/tax.settings.js" type="text/javascript" charset="utf-8"></script>
<script type="text/javascript" charset="utf-8">
	{% if activeTab %}
		Tax_Settings.activeTab = '{{ activeTab }}';
	{% endif %}

	lang.ConfirmDeleteTaxClass = "{% lang 'ConfirmDeleteTaxClass' %}";
	lang.ConfirmCancel = "{{ lang.ConfirmCancel }}";
	lang.TaxClassMissingName = "{% jslang 'TaxClassMissingName' %}";
	lang.ConfirmDeleteTaxZones = "{% lang 'ConfirmDeleteTaxZones' %}";
	lang.SelectTaxZonesToDelete = "{% jslang 'SelectTaxZonesToDelete' %}";
	lang.DefaultZoneWhatDoesThisMean = "{{ lang.DefaultZoneWhatDoesThisMean|js }}";
	lang.InvalidTaxSettingTaxLabel = "{% jslang 'InvalidTaxSettingTaxLabel' %}";
	lang.InvalidTaxSettingTaxDefaultCountry = "{% jslang 'InvalidTaxSettingTaxDefaultCountry' %}";
	lang.InvalidTaxSettingTaxDefaultState = "{% jslang 'InvalidTaxSettingTaxDefaultState' %}";
</script>