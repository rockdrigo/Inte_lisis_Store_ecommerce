			<tr id="tr{{ LogId|safe }}" class="GridRow" onmouseover="this.className='GridRowOver'" onmouseout="this.className='GridRow'">
				<td width="1">
					<input type="checkbox" class="DeleteCheck" name="delete[]" value="{{ LogId|safe }}" />
				</td>
				<td width="1">
					<img src="images/log_{{ SeverityClass|safe }}.gif" alt="" />
				</td>
				<td width="80" class="{{ SeverityClass|safe }}">
					{{ Severity|safe }}
				</td>
				<td align="center" style="width:15px">
					{{ ExpandLink|safe }}
				</td>
				<td class="{{ SortedFieldTypeClass|safe }}">
					{{ Type|safe }}
				</td>
				<td class="{{ SortedFieldModuleClass|safe }}">
					{{ Module|safe }}
				</td>
				<td class="{{ SortedFieldSummaryClass|safe }}">
					{{ Summary|safe }}
				</td>
				<td width="150" class="{{ SortedFieldDateClass|safe }}">
					{{ Date|safe }}
				</td>
			</tr>
			<tr id="trQ{{ LogId|safe }}" style="display:none">
				<td colspan="3">
					&nbsp;
				</td>
				<td colspan="4" id="tdQ{{ LogId|safe }}" class="QuickView">
				</td>
				<td colspan="2">&nbsp;</td>
			</tr>