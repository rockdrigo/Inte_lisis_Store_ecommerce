			<tr class="GridRow" onmouseover="this.className='GridRowOver'" onmouseout="this.className='GridRow'">
				<td width="1">
					<input type="checkbox" class="DeleteCheck" name="delete[]" value="{{ LogId|safe }}" />
				</td>
				<td width="1">
					<img src="images/log.gif" alt="" />
				</td>
				<td class="{{ SortedFieldNameClass|safe }}">
					{{ Username|safe }}
				</td>
				<td>
					{{ Action|safe }}
				</td>
				<td width="150" class="{{ SortedFieldDateClass|safe }}">
					{{ Date|safe }}
				</td>
				<td width="80" nowrap="nowrap" class="{{ SortedFieldIPClass|safe }}">
					{{ Ip|safe }}
				</td>
			</tr>