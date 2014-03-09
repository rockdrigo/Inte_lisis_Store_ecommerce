{% import "macros/util.tpl" as util %}
{% import "macros/forms.tpl" as formBuilder %}
<div id="content">
	<form action="index.php" method="post" id="taxRateForm" accept-charset="utf-8">
		{% if taxRate.id %}
			<input type="hidden" name="ToDo" value="saveUpdatedTaxRate" />
			<input type="hidden" name="id" value="{{ taxRate.id }}" />
			<h1>{% lang 'EditTaxRateTitle' with [
				'name': taxRate.name,
				'zone': taxZone.name
			] %}</h1>
			<p class="intro">
				{{ lang.EditTaxRateIntro }}
			</p>
		{% else %}
			<input type="hidden" name="ToDo" value="saveNewTaxRate" />
			<h1>{% lang 'AddTaxRateTitle' with [
				'zone': taxZone.name
			] %}</h1>
			<p class="intro">
				{{ lang.AddTaxRateIntro }}
			</p>
		{% endif %}
		<input type="hidden" name="tax_zone_id" value="{{ taxZone.id }}" />
		
		{{ flashMessages|safe }}

		<p class="intro">
			<input type="submit" value="{{ lang.Save }}" class="saveButton" />
			or <a href="#" class="cancelLink">{{ lang.Cancel }}</a>
		</p>
		
		{{ formBuilder.startForm }}
			{{ formBuilder.heading(lang.TaxRateSettings) }}
		
			{{ formBuilder.startRow([
				'label': lang.TaxRateName ~ ':',
				'required': true
			]) }}
				{{ formBuilder.input('text', 'name', taxRate.name, [
					'class': 'Field300'
				]) }}
				{{ util.tooltip('TaxRateName', 'TaxRateNameHelp') }}
			{{ formBuilder.endRow }}
			
			{{ formBuilder.startRow([
				'label': lang.TaxClassRates ~ ':',
				'required': true
			]) }}
				<div class="taxClassRate">
					{{ formBuilder.input('text', 'default_rate', taxRate.default_rate, [
						'class': 'Field50'
					]) }}
					% {{ lang.ForProductsMarkedAsDefault }}
				</div>
				{% for id, name in taxClasses %}
					<div class="taxClassRate">
						{{ formBuilder.input('text', 'rates[' ~ id ~ ']', taxRate.rates[id], [
							'class': 'Field50 taxClassRate'
						]) }}
						% {% lang 'ForProductsMarkedAsX' with [
							'name': name
						] %}
					</div>
				{% endfor %}
				<p class="note">{{ lang.AddMoreTaxClassesNote }}</p>
			{{ formBuilder.endRow }}
			
			{{ formBuilder.startRow([
				'label': lang.CalculationPriority ~ ':',
				'required': true
			]) }}
				{{ formBuilder.input('text', 'priority', taxRate.priority, [
					'class': 'Field50'
				]) }}
				{{ util.tooltip('CalculationPriority', 'CalculationPriorityHelp') }}
				<a href="#" class="priorityHelpLink">{{ lang.ReadThisArticleForMoreInformation }}</a>
				{% if existingTaxRates %}
					<div class="existingPriorityReference">
						<p>
							{{ lang.ForYourReferenceExistingPriorities }}
						</p>
						<ul>
							{% for name, priority in existingTaxRates %}
								<li>{{ name}}: {{ priority }}</li>
							{% endfor %}
						</ul>
					</div>
				{% endif %}
			{{ formBuilder.endRow }}
		
			{{ formBuilder.startRow([
				'label': lang.EnableTaxRate ~ '?',
				'required': true
			]) }}
				{{ formBuilder.input('hidden', 'enabled', 0) }}
				<label>
					<input type="checkbox" name="enabled" value="1" {% if taxRate.enabled %}checked="checked"{% endif %} />
					{{ lang.YesEnableTaxRate }}
				</label>
			{{ formBuilder.endRow }}
			
			{{ formBuilder.startButtonRow }}
				<input type="submit" value="{{ lang.Save }}" class="saveButton" />
				or <a href="#" class="cancelLink">{{ lang.Cancel }}</a>
			{{ formBuilder.endButtonRow }}
		</div>
	</form>
</div>
<script type="text/javascript">
	lang.ConfirmCancel = "{{ lang.ConfirmCancel }}";
	lang.TaxRateMissingName = "{{ lang.TaxRateMissingName }}";
	lang.InvalidTaxRatePriority = "{{ lang.InvalidTaxRatePriority }}";
	lang.InvalidTaxRateClassRate = "{{ lang.InvalidTaxRateClassRate }}";
</script>
<script src="script/tax.rate.js" type="text/javascript" charset="utf-8"></script>