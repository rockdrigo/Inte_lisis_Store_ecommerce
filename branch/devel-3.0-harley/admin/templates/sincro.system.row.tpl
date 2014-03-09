			<tr id="tr{{ SincroId|safe }}" class="GridRow" onmouseover="this.className='GridRowOver'" onmouseout="this.className='GridRow'">
				<td width="1">
					<input type="checkbox" class="DeleteCheck" name="delete[]" value="{{ SincroId|safe }}" />
				</td>
				<td width="80" class="{{ SincroIdClass|safe }}">
					{{ SincroId|safe }}
				</td>
				<td align="center" style="width:15px">
					{{ ExpandLink|safe }}
				</td>
				<td class="{{ SortedFieldSincroSumClass|safe }}">
					{{ SincroSum|safe }}
				</td>
				<td class="{{ SortedFieldSincroEstatusClass|safe }}">
					{{ SincroEstatus|safe }}
				</td>
				<td class="{{ SortedFieldSincroDateClass|safe }}">
					{{ SincroDate|safe }}
				</td>
			</tr>
			<tr id="trQ{{ SincroId|safe }}" style="display:none">
				<td colspan="2">
					&nbsp;
				</td>
				<td colspan="3" id="tdQ{{ SincroId|safe }}" class="QuickView">
				</td>
				<td colspan="1">&nbsp;</td>
			</tr>