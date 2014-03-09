<table class="gridTable SortableGrid" id="taxRatesGrid" cellspacing="0" cellpadding="0">
	<thead>
		<tr>
			<th class="check checkAll"><input type="checkbox" /></th>
			<th class="icon">&nbsp;</th>
			<th>{{ lang.Name }}</th>
			<th style="width: 300px">{{ lang.Rates }}</th>
			<th style="width: 150px">{{ lang.CalculationPriority }}</th>
			<th style="width: 100px">{{ lang.Status }}</th>
			<th style="width: 150px">{{ lang.Action }}</th>
		</tr>
	</thead>
	<tbody>
		{% for taxRate in taxRates %}
			<tr>
				<td class="check"><input type="checkbox" name="taxRate[]" value="{{ taxRate.id }}" /></td>
				<td><img src="images/tax.gif" alt="" /></td>
				<td>
					{{ taxRate.name }}
				</td>
				<td>
					<table width="100%">
						<tr>
							<td>{{ lang.DefaultTaxClass }}:</td>
							<td style="text-align: right">{{ taxRate.default_rate }}%</td>
						</tr>
						{% for class, rate in taxRate.rates %}
							<tr>
								<td>{{ class }}:</td>
								<td style="text-align: right">{{ rate }}%</td>
							</tr>
						{% endfor %}
					</table>
				</td>
				<td>
					{{ taxRate.priority }}
				</td>
				<td>
					<a href="#toggle{{ taxRate.id }}" class="toggleTaxRateStatusLink statusToggle{{ taxRate.enabled }}">
						{{ lang.Toggle }}
					</a>
				</td>
				<td>
					<a href="index.php?ToDo=editTaxRate&amp;id={{ taxRate.id }}">{{ lang.Edit }}</a>
					<a href="#copy{{ taxRate.id }}" class="copyTaxRateLink">{{ lang.Copy }}</a>
					<a href="#" class="deleteTaxRateLink">{{ lang.Delete }}</a>
				</td>
			</tr>
		{% endfor %}
	</tbody>
</table>