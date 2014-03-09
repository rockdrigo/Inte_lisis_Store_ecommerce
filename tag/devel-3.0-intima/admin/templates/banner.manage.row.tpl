<tr class="GridRow" onmouseover="this.className='GridRowOver'" onmouseout="this.className='GridRow'">
	<td align="center" style="width:25px">
		<input type="checkbox" name="banner[]" value="{{ BannerId|safe }}">
	</td>
	<td align="center" style="width:18px;">
		<img src='images/banner.gif'>
	</td>
	<td class="{{ SortedFieldNameClass|safe }}">
		{{ Name|safe }}
	</td>
	<td class="{{ SortedFieldLocationClass|safe }}">
		{{ Location|safe }}
	</td>
	<td class="{{ SortedFieldDateClass|safe }}">
		{{ Date|safe }}
	</td>
	<td align="center" class="{{ SortedFieldStatusClass|safe }}">
		{{ Visible|safe }}
	</td>
	<td>
		<a title='{% lang 'EditThisBanner' %}' class='Action' href='index.php?ToDo=editBanner&amp;bannerId={{ BannerId|safe }}'>{% lang 'Edit' %}</a>
	</td>
</tr>