
	<tr class="GridRow" onmouseover="this.className='GridRowOver'" onmouseout="this.className='GridRow'">
		<td align="center" style="width:25px">
			<input type="checkbox" name="news[]" value="{{ NewsId|safe }}">
		</td>
		<td align="center" style="width:18px">
			<div class="NewsIcon"></div>
		</td>
		<td width="550" class="{{ SortedFieldTitleClass|safe }}">
			{{ Title|safe }}
		</td>
		<td style="width:250px" class="{{ SortedFieldDateClass|safe }}">
			{{ Date|safe }}
		</td>
		<td align="center" class="{{ SortedFieldVisibleClass|safe }}">
			{{ Visible|safe }}
		</td>
		<td>
			{{ EditNewsLink|safe }}&nbsp;&nbsp;&nbsp;
			<a title='{% lang 'PreviewNewsPost' %}' href="javascript:PreviewNews({{ NewsId|safe }})">{% lang 'PreviewNews' %}</a>
		</td>
	</tr>