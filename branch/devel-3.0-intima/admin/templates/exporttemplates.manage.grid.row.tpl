<tr id="tr{{ ExportTemplateId|safe }}" class="GridRow" onmouseover="this.className='GridRowOver'" onmouseout="this.className='GridRow'">
	<td align="center" style="width: 25px; {{ HideCheckAll|safe }}">
		{{ CheckTemplate|safe }}
	</td>
	<td align="center" style="width:18px">
		<img src="images/application_form.png" width="12" height="15" />
	</td>
	<td colspan="{{ ProductNameSpan|safe }}" class="{{ SortedFieldNameClass|safe }}">
		{{ ExportTemplateName|safe }}
	</td>
	<td>
		{{ TemplateType|safe }}
	</td>
	<td {{ HideVendorColumn|safe }}>
		{{ VendorName|safe }}
	</td>
	<td>
		<select id="select{{ ExportTemplateId|safe }}" style="width: 160px;" onchange="PerformAction(this);">
			<option value="">{% lang 'ChooseAnAction' %}</option>
			{{ TemplateActions|safe }}
		</select>
	</td>
</tr>