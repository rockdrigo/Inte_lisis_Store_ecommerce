<div id="emailIntegrationFieldSyncModal" class="emailIntegrationFieldSyncForm">
	{% if listFields|length %}
		<p class="emailIntegrationFieldSyncModalIntro">{% lang 'FieldSyncFormIntro' with [
			'provider': module.name
		] %}</p>

		<table>
			<thead>
				<tr>
					<th>{% lang 'FieldSyncFormMapThisCustomerData' %}</th>
					<th>{% lang 'FieldSyncFormMapThisProviderField' with [
						'provider': module.name
					] %}</th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			<tbody class="emailIntegrationFieldSyncFormContent">
				<tr>
					<td>
						<select class="Field200" disabled="disabled">
							<option>{% lang 'EmailAddress' %}</option>
						</select>
					</td>
					<td>
						<select class="Field200" disabled="disabled">
							<option>{% lang 'EmailAddress' %}</option>
						</select>
					</td>
					<td class="mapActions">
						<a href="#" class="mapAdd"><img src="images/addicon.png" width="16" height="16" alt="{{ lang.Add }}" /></a>
						<img class="mapNoDelete" src="images/delicon_gray.png" width="16" height="16" alt="{{ lang.Delete }}" />
					</td>
				</tr>
			</tbody>
			<tfoot style="display:none;">
				<tr class="emailIntegrationFieldSyncFormTemplate">
					<td>
						<select class="Field200 mapLocal">
							<option value="">{% lang 'FieldSyncFormChooseAField' %}</option>
							{% for groupLabel, fields in formFields %}
								<option value="" disabled="disabled"></option>
								<optgroup label="{{ groupLabel }}">
									{% for fieldId, field in fields %}
										<option value="{{ fieldId }}">{{ field.description }}</option>
									{% endfor %}
								</optgroup>
							{% endfor %}
						</select>
					</td>
					<td>
						<select class="Field200 mapProvider">
							<option value="">{% lang 'FieldSyncFormChooseAField' %}</option>
							<option value="" disabled="disabled"></option>
							{% for field in listFields %}
								<option value="{{ field.provider_field_id }}">{{ field.name }}</option>
							{% endfor %}
						</select>
					</td>
					<td class="mapActions">
						<a href="#" class="mapAdd"><img src="images/addicon.png" width="16" height="16" alt="{{ lang.Add }}" /></a>
						<a href="#" class="mapDelete"><img src="images/delicon.png" width="16" height="16" alt="{{ lang.Delete }}" /></a>
					</td>
				</tr>
			</tfoot>
		</table>
	{% else %}
		<p>{% lang 'FieldSyncFormListNotFound_1' %}</p>
		<p>{% lang 'FieldSyncFormListNotFound_2' %}</p>
		<p>{% lang 'FieldSyncFormListNotFound_3' %}</p>
	{% endif %}
</div>

<script language="javascript" type="text/javascript">//<![CDATA[
(function($){
	//	preloaded mappings
	var mappings = {};
	{% for providerField, localField in mappings %}
		mappings["{{ providerField|js }}"] = "{{ localField|js }}";
	{% endfor %}
	//	end of preloaded mappings

	$(function(){
		var form = $('.emailIntegrationFieldSyncForm');

		form.delegate('.mapNoDelete', 'click', function(){
			alert("{% jslang 'FieldSyncFormEmailNoDelete' %}");
		});

		$.each(mappings, function(providerField, localField){
			// add a new row to work with
			form.find('.mapAdd').eq(0).click();

			var row = $('.emailIntegrationFieldSyncFormContent tr:last');
			row.find('.mapLocal').val(localField);
			row.find('.mapProvider').val(providerField);
		});

		// finally, add a blank row to help the user understand what to do next
		form.find('.mapAdd').eq(0).click();

		if (ReadCookie('fieldSyncFormGuessFields') != 'off') {
			$('#fieldSyncFormGuessFields').attr('checked', 'checked');
		}

		$('#fieldSyncFormGuessFields').click(function(event){
			if (this.checked) {
				SetCookie('fieldSyncFormGuessFields', 'on');
			} else {
				SetCookie('fieldSyncFormGuessFields', 'off');
			}
		});
	});

	if (typeof lang == 'undefined') {
		lang = {};
	}

	lang.FieldSyncFormDuplicateFieldsClientError = "{% jslang 'FieldSyncFormDuplicateFieldsClientError' with [
		'provider': module.name
	] %}";

	lang.FieldSyncFormMapUnmatchedFields = "{% jslang 'FieldSyncFormMapUnmatchedFields' %}";
})(jQuery);
//]]></script>
