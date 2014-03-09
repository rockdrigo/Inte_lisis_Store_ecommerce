<tr class="GridRow" onmouseover="$(this).addClass('GrodRowOver');" onmouseout="$(this).removeClass('GridRowOver');">
	<td style="text-align: center;"><input type="checkbox" class="check" name="wrap[]" value="{{ WrapId|safe }}" /></td>
	<td><img src="images/giftwrap.gif" alt="" /></td>
	<td>{{ WrapName|safe }}</td>
	<td>{{ WrapPrice|safe }}</td>
	<td style="text-align: center;"><img src="images/{{ WrapVisibleImage|safe }}" alt="" /></td>
	<td>
		<a href="index.php?ToDo=editGiftWrap&amp;wrapId={{ WrapId|safe }}">{% lang 'Edit' %}</a>
		<a href="index.php?ToDo=deleteGiftWrap&amp;wrap[]={{ WrapId|safe }}" onclick="return ConfirmDeleteWrap();">{% lang 'Delete' %}</a>
	</td>
</tr>