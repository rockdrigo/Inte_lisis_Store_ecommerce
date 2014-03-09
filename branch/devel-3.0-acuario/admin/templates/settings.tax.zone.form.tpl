<link rel="stylesheet" href="Styles/tax.settings.css" type="text/css" charset="utf-8" />
{% import "macros/util.tpl" as util %}
{% import "macros/forms.tpl" as formBuilder %}

<div id="content">
	<div id="taxZoneSettings">
		{% if taxZone.id %}
			<h1>
				{% if created %}
					{{ lang.AddTaxZoneStep2 }}
				{% else %}
					{{ lang.EditTaxZone }}: {{ taxZone.name }}
				{% endif %}
			</h1>
			<p class="intro">
				{{ lang.EditTaxzoneIntro }}
			</p>

			{{ flashMessages|safe }}

			{% if pendingChangesToApply %}
				<p class="MessageBox MessageBoxInfo">{% lang 'ApplyPendingTaxChanges' %}</p>
			{% endif %}

			{{ util.tabs([
				'settingsTab': lang.ZoneSettings,
				'taxRatesTab': lang.TaxRates
			]) }}

		{% else %}
			<h1>{{ lang.AddTaxZoneStep1 }}</h1>
			<p class="intro">
				{{ lang.AddTaxZoneIntro }}
			</p>

			{{ flashMessages|safe }}

		{% endif %}

		<div id="settingsTab">
			<form action="index.php" method="post" id="taxZoneForm" accept-charset="utf-8">
				{% if taxZone.id %}
					<input type="hidden" name="ToDo" value="saveUpdatedTaxZone" />
					<input type="hidden" name="id" value="{{ taxZone.id }}" />
				{% else %}
					<input type="hidden" name="ToDo" value="saveNewTaxZone" />
				{% endif %}

				<p class="intro">
					<input type="submit" value="{{ lang.Save }}" class="saveButton" />
					or <a href="#" class="cancelLink">{{ lang.Cancel }}</a>
				</p>

				{{ formBuilder.startForm }}
					{{ formBuilder.heading(lang.TaxZoneSettings) }}

					{{ formBuilder.startRow([
						'label': lang.TaxZoneName ~ ':',
						'required': true
					]) }}
						{{ formBuilder.input('text', 'name', taxZone.name, [
							'class': 'Field300'
						]) }}
						{{ util.tooltip('TaxZoneName', 'TaxZoneNameHelp') }}
					{{ formBuilder.endRow }}

					{{ formBuilder.startRow([
						'label': lang.TaxZoneType ~ ':',
						'required': true
					]) }}
						{% if taxZone.default %}
							{{ lang.TaxZoneTypeDefault }}
						{% else %}
							<label class="row">
								<input type="radio" name="type" value="country" {% if taxZone.type == 'country' %}checked="checked"{% endif %} />
								{{ lang.TaxZoneTypeCountry }}
							</label>
							<div class="zoneTypeToggle zoneTypeCountry" {% if taxZone.type != 'country' %}style="display: none"{% endif %}>
								{{ formBuilder.multiselect('countries[]', countryList, taxZone.countries, [
									'size': 15,
									'multiple': 'multiple',
									'class': 'Field250 ISSelectReplacement'
								]) }}
							</div>
							<label class="row">
								<input type="radio" name="type" value="state" {% if taxZone.type == 'state' %}checked="checked"{% endif %} />
								{{ lang.TaxZoneTypeState }}
							</label>
							<div class="zoneTypeToggle zoneTypeState" {% if taxZone.type != 'state' %}style="display: none"{% endif %}>
								{{ formBuilder.startRow([
									'label': lang.Countries ~ ':',
									'required': true
								]) }}
									{{ formBuilder.multiselect('state_countries[]', countryList, taxZone.countries, [
										'size': 10,
										'multiple': 'multiple',
										'class': 'Field250 ISSelectReplacement',
										'id': 'stateCountrySelect',
										'onchange': 'Tax_Zone.toggleStateCountry()'
									]) }}
								{{ formBuilder.endRow }}

								{{ formBuilder.startRow([
									'label': lang.States ~ ':',
									'required': true
								]) }}
									<div class="stateSelectSome" {% if taxZone.states == false %}style="display: none"{% endif %}>
										<select name="states[]" id="stateSelect" size="10" multiple="multiple" class="Field250 ISSelectReplacement">
											{% for country in countryStateList %}
												<optgroup class="country{{ country.id }}" label="{{ country.name }}">
													<option value="{{country.id }}-0" {% if country.id ~ '-' ~ 0 in taxZone.states %}selected="selected"{% endif %}>{{ lang.AllStates }}</option>
													{% for stateId, state in country.states %}
														<option value="{{ country.id }}-{{ stateId }}"
															{% if country.id ~ '-' ~ stateId in taxZone.states %}selected="selected"{% endif %}
														>
															{{ state }}
														</option>
													{% endfor %}
												</optgroup>
											{% endfor %}
										</select>
									</div>
									<div class="stateSelectNone" {% if taxZone.states %}style="display: none"{% endif %}>
										<div>{{ lang.ChooseCountriesBeforeStates }}</div>
									</div>
								{{ formBuilder.endRow }}
							</div>
							<label class="row">
								<input type="radio" name="type" value="zip" {% if taxZone.type == 'zip' %}checked="checked"{% endif %} />
								{{ lang.TaxZoneTypeZip }}
							</label>
							<div class="zoneTypeToggle zoneTypeZip" {% if taxZone.type != 'zip' %}style="display: none"{% endif %}>
								{{ formBuilder.startRow([
									'label': lang.Country ~ ':',
									'required': true
								]) }}
									{{ formBuilder.select('country', countryList, taxZone.country, [
										'class': 'Field250'
									]) }}
								{{ formBuilder.endRow }}

								{{ formBuilder.startRow([
									'label': lang.ZipCodes ~ ':',
									'required': true
								]) }}
									<textarea name="zip_codes" class="Field250" rows="10" cols="10">{{ taxZone.zip_codes|join('\n') }}</textarea>
									<br />
									<a href='#' onclick='LaunchHelp(850); return false;' target="_blank">
										{{ lang.LearnMoreZipCodes }}
									</a>
								{{ formBuilder.endRow }}
							</div>
						{% endif %}
					{{ formBuilder.endRow }}

					{% if taxZone.default == 0 %}
						{{ formBuilder.startRow([
							'label': lang.TaxZoneAppliesTo ~ ':',
							'required': true
						]) }}
							<label class="row">
								<input type="radio" name="applies_to" value="all" {% if taxZone.groups == false %}checked="checked"{% endif %} />
								{{ lang.AllCustomersInStore }}
								{{ util.tooltip('TaxZoneAppliesTo', 'TaxZoneAppliesToHelp') }}
							</label>
							<label class="row">
								<input type="radio" name="applies_to" value="groups" {% if taxZone.groups %}checked="checked"{% endif %} />
								{{ lang.OnlyTheseCustomerGroups }}:
							</label>
							<div class="zoneGroupSelect" {% if taxZone.groups == false %}style="display: none"{% endif %}>
								{% if customerGroupList %}
									{{ formBuilder.multiselect('groups[]', customerGroupList, taxZone.groups, [
										'size': 5,
										'multiple': 'multiple',
										'class': 'Field250 ISSelectReplacement'
									]) }}
								{% else %}
									<p class="note">
										{{ lang.NoCustomerGroupsCreate|safe }}
									</p>
								{% endif %}
							</div>
						{{ formBuilder.endRow }}

						{{ formBuilder.startRow([
							'label': lang.EnableTaxZone ~ '?',
							'required': true
						]) }}
							<label>
								<input type="checkbox" name="enabled" value="1" {% if taxZone.enabled %}checked="checked"{% endif %} />
								{{ lang.YesEnableTaxZone }}
							</label>
						{{ formBuilder.endRow }}
					{% endif %}

					{{ formBuilder.startButtonRow }}
						<input type="submit" value="{{ lang.Save }}" class="saveButton" />
						or <a href="#" class="cancelLink">{{ lang.Cancel }}</a>
					{{ formBuilder.endButtonRow }}
				{{ formBuilder.endForm }}
			</form>
		</div>

		{% if taxZone.id %}
			<div id="taxRatesTab">
				<p class="intro">
					{{ lang.TaxRatesIntro }}
				</p>

				{% if taxRateGrid == false %}
					<p class="MessageBox MessageBoxInfo">
						{{ lang.NoTaxRatesCreateOne }}
					</p>
				{% endif %}

				<form action="index.php" method="post" accept-charset="utf-8">
					<input type="hidden" name="ToDo" value="deleteTaxRates" />

					<p class="intro">
						<input type="button" value="{{ lang.AddATaxRateButton }}" class="addTaxRateButton" />
						<input type="submit" value="{{ lang.DeleteSelected }}" class="deleteTaxRatesButton" />
					</p>

					<div class="GridContainer">
						{{ taxRateGrid|safe }}
					</div>
				</form>
			</div>
		{% endif %}
	</div>
</div>
<script type="text/javascript" charset="utf-8">
	defaultZone = '{{ taxZone.default }}';
	lang.AllStates = "{% jslang 'AllStates' %}";
	lang.ConfirmCancel = "{{ lang.ConfirmCancel }}";
	lang.ConfirmDeleteTaxRates = "{% lang 'ConfirmDeleteTaxRates' %}";
	lang.SelectTaxRatesToDelete = "{% jslang 'SelectTaxRatesToDelete' %}";
	lang.ConfirmDeleteTaxRates = "{% lang 'ConfirmDeleteTaxRates' %}";
	lang.TaxZoneMissingName = "{{ lang.TaxZoneMissingName|js }}";
	lang.TaxZoneSelectOneMoreCountries = "{{ lang.TaxZoneSelectOneMoreCountries|js }}";
	lang.TaxZoneSelectOneMoreStates = "{{ lang.TaxZoneSelectOneMoreStates|js }}";
	lang.TaxZoneSelectCountry = "{{ lang.TaxZoneSelectCountry|js }}";
	lang.TaxZoneEnterOneMoreZipCodes = "{{ lang.TaxZoneEnterOneMoreZipCodes|js }}";
	lang.TaxZoneSelectOneMoreGroups = "{{ lang.TaxZoneSelectOneMoreGroups|js }}";

</script>
<script src="script/tax.zone.js" type="text/javascript" charset="utf-8"></script>