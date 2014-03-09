
	<tr class="GridRow" onmouseover="this.className='GridRowOver'" onmouseout="this.className='GridRow'">
		<td align="center" style="width:23px">
			<input type="checkbox" name="users[]" value="{{ UserId|safe }}" {{ CheckDisabled|safe }}>
		</td>
		<td align="center" style="width:18px;">
			<img src="images/user.gif" />
		</td>
		<td class="{{ SortedFieldUserClass|safe }}">
			{{ Username|safe }}
		</td>
		<td class="{{ SortedFieldNameClass|safe }}">
			{{ Name|safe }}
		</td>
		<td class="{{ SortedFieldEmailClass|safe }}">
			{{ Email|safe }}
		</td>
		<td style="width: 150px; {{ HideVendorColumn|safe }}" class="{{ SortedFieldVendorClass|safe }}">
			{{ Vendor|safe }}
		</td>
		<td align="center" style="display: {{ StatusField|safe }}" class="{{ SortedFieldStatusClass|safe }}">
			{{ Status|safe }}
		</td>
		<td>
			{{ EditUserLink|safe }}&nbsp;&nbsp;&nbsp;
			{{ CopyUserLink|safe }}
		</td>
	</tr>
