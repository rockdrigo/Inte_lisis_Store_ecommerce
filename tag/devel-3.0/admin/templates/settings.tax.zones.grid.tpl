{{ util.paging(numTaxZones, taxZonesPerPage, currentPage, 'index.php?ToDo=manageTaxSettings&page={page}#taxZonesTab') }}
<table class="taxZonesGrid gridTable SortableGrid" cellspacing="0" cellpadding="0">
	<thead>
		<tr>
			<th class="check checkAll"><input type="checkbox" /></th>
			<th class="icon">&nbsp;</th>
			<th>{{ lang.ZoneName }}</th>
			<th>{{ lang.ZoneType }}</th>
			<th>{{ lang.CustomerGroupOrGroups}}</th>
			<th style="width: 100px">{{ lang.Status }}</th>
			<th style="width: 180px">{{ lang.Action }}</th>
		</tr>
	</thead>
	<tbody>
		{% for taxZone in taxZones %}
			<tr class="{% if taxZone.default %}highlight{% endif %}">
				<td class="check">
					<input type="checkbox" name="taxZone[]" value="{{ taxZone.id }}" {% if taxZone.default %}disabled="disabled"{% endif %} />
				</td>
				<td><img src="images/zone.gif" alt="" /></td>
				<td>
					{{ taxZone.name }}
					{% if taxZone.default %}
						 <strong>({{ lang.EverywhereElse }} - <a href="#" class="defaultZoneDefinition">{{ lang.WhatDoesThisMean }}</a>)</strong>
					{% endif %}
				</td>
				<td>
					{% if taxZone.default %}
						{{ lang.TaxZoneTypeShortGlobal }}
					{% else %}
						{% lang 'TaxZoneTypeShort' ~ taxZone.type|capitalize %}
					{% endif %}
				</td>
				<td>
					{% for groupName in taxZone.customerGroups %}
						{{ groupName }}
					{% else %}
						{{ lang.NA }}
					{% endfor %}
				</td>
				<td>
					{% if taxZone.default == false %}
						<a href="#toggle{{ taxZone.id }}" class="toggleTaxZoneStatusLink statusToggle{{ taxZone.enabled }}">
							Toggle
						</a>
					{% else %}
						&nbsp;
					{% endif %}
				</td>
				<td>
					<a href="index.php?ToDo=editTaxZone&amp;id={{ taxZone.id }}">{{ lang.EditSettings }}</a>
					<a href="index.php?ToDo=editTaxZone&amp;id={{ taxZone.id }}#taxRatesTab">{{ lang.EditRates }}</a>
					<a href="#copy{{ taxZone.id }}" class="copyTaxZoneLink">{{ lang.Copy }}</a>
					{% if taxZone.default == false %}
						<a href="#" class="deleteTaxZoneLink">{{ lang.Delete }}</a>
					{% endif %}
				</td>
			</tr>
		{% endfor %}
	</tbody>
</table>
{{ util.paging(numTaxZones, taxZonesPerPage, currentPage, 'index.php?ToDo=manageTaxSettings&page={page}#taxZonesTab') }}