<tr style="{{ HideProperty|safe }}" class="{{ PropertyClass|safe }}">

	<td class="FieldLabel" nowrap>
		{{ Required|safe }} <label for="StoreName">{{ PropertyName|safe }}</label>
		<div style="{{ HideSelectAllLinks|safe }}">
			&nbsp;&nbsp;&nbsp;(<a onclick="SelectAll('{{ FieldId|safe }}'); return false;" href="#">{% lang 'SelectAll' %}</a> / <a  onclick="UnselectAll('{{ FieldId|safe }}'); return false;" href="#">{% lang 'UnselectAll' %}</a>)
	</td>
	<td class="{{ PanelBottom|safe }}">
		{{ PropertyBox|safe }}
		{{ HelpTip|safe }}
	</td>
</tr>

