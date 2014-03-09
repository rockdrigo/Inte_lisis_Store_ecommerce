
	<tr class="GridRow" onmouseover="this.className='GridRowOver'" onmouseout="this.className='GridRow'">
		<td align="center" style="width:25px">
			<input class="EbayTemplate" type="checkbox" name="templates[]" value="{{ Id|safe }}">
		</td>
		<td align="center" style="width:18px">
			<img width="16" height="16" alt="product" src="images/application_form.png">
		</td>
		<td width="550" class="{{ SortedFieldNameClass|safe }}">
			{{ Name|safe }}
		</td>
		<td style="width:250px" class="{{ SortedFieldDateClass|safe }}">
			{{ Date|safe }}
		</td>
		<td align="center" class="{{ SortedFieldEnabledClass|safe }}">
			{{ Enabled|safe }}
		</td>
		<td>
			{{ EditTemplateLink|safe }}&nbsp;&nbsp;&nbsp;
		</td>
	</tr>