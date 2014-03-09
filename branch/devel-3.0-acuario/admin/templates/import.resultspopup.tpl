<table id="OuterPanel" cellSpacing="0" cellPadding="0" width="100%">
	<tr>
		<td class="Heading1">
			{{ Heading|safe }}
		</td>
		<td align="right">
			<a href="javascript: window.opener.focus(); window.close();">{% lang 'PopupCloseWindow' %}</a>
		</td>
	</tr>
	</tr>
	<tr>
		<td class="Intro" colspan="2">
			{{ Intro|safe }}
		</td>
	</tr>
	<tr>
		<td colspan="2" class="Intro">
			<textarea name="list" rows="15" class="Field" style="width:100%; height: 300px;">{{ Results|safe }}</textarea>
		</td>
	</tr>
</table>