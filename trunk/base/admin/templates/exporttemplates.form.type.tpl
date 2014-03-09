<div id="div{{ FileIndex|safe }}" style="padding-top: 10px; {{ TypeDisplay|safe }} {{ TypeWidth|safe }}">
	<p style="padding-left: 10px;">
	{% lang 'TypeIntro' %}
	</p>
	<table class="GridPanel SortablePanel" cellspacing="0" cellpadding="0" border="0" style="width:100%; margin-top:10px">
		<tr class="Heading3">
			<td align="center" style="width: 25px;"><input type="checkbox" onchange="toggleFieldCheck('{{ TypeName|safe }}', this.checked);" checked="checked"/></td>
			<td style="width: 150px;">{% lang 'ThisField' %}</td>
			<td style="width: 410px;" id="{{ TypeName|safe }}_headercol">{% lang 'ExportWithName' %}</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
	</table>

	{{ FieldGrid|safe }}
</div>