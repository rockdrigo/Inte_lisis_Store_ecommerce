<table class="GridPanel" cellspacing="0" cellpadding="0" border="0" style="width: 100%; margin-bottom: 10px;">
	<tr class="Heading3">
		<td align="center" style="{{ HideCheckAll|safe }}"><input type="checkbox" onclick="ToggleDeleteBoxes(this.checked)"></td>
		<td>&nbsp;</td>
		<td>
			{% lang 'ExportTemplateTitle' %} &nbsp;
			{{ SortLinksTitle|safe }}
		</td>
		<td>
			<span class="HelpText" onmouseout="HideQuickHelp(this);" onmouseover="ShowQuickHelp(this, '{% lang 'TemplateType' %}', '{% lang 'TemplateTypeHelp' %}');">{% lang 'TemplateType' %}</span>&nbsp;
			{{ SortLinksType|safe }}
		</td>
		<td {{ HideVendorColumn|safe }}>
			{{ VendorLabel|safe }} &nbsp;
			{{ SortLinksVendor|safe }}
		</td>
		<td style="width:100px;">
			{% lang 'Action' %} &nbsp;
			{{ SortLinksAction|safe }}
		</td>
	</tr>
	{{ ExportTemplateGridData|safe }}
</table>