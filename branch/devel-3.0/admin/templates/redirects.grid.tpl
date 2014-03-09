<table class="GridPanel SortableGrid AutoExpand" cellspacing="0" cellpadding="0" border="0" id="IndexGrid" style="width:100%;">
	<tr align="right">
		<td colspan="11" style="padding:6px 0px 6px 0px" class="PagingNav">
			{{ RedirectPaging|safe }}
		</td>
	</tr>
	<tr class="Heading3 RedirectsHeadingRow" id="RedirectsHeadingRow">
		<td align="center"><input type="checkbox" id="RedirectsMasterCheckbox"></td>
		<td>{{ SortLinksRedirectId|safe }}</td>
		<td style="width:40%;">
			<span class="HelpText" onmouseout="HideQuickHelp(this);" onmouseover="ShowQuickHelp(this, '{% lang 'RedirectOldURL' %}', '{% lang 'RedirectOldURLHelp' %}')">{% lang 'RedirectOldURL' %}</span>
			{{ SortLinksOldUrl|safe }}

		</td>
		<td style="width:80px;">
			<span class="HelpText" onmouseout="HideQuickHelp(this);" onmouseover="ShowQuickHelp(this, '{% lang 'RedirectType' %}', '{% jslang 'RedirectTypeHelp' %}')">{% lang 'RedirectType' %}</span>
			{{ SortLinksNewUrl|safe }}
		</td>
		<td style="width:35%;">
			{% lang 'RedirectNewURL' %} &nbsp;
		</td>
		<td>
			{% lang 'Action' %}
		</td>
	</tr>
	{{ RedirectsGrid|safe }}
	<tr align="right">
		<td colspan="11" style="padding:6px 0px 6px 0px" class="PagingNav">
			{{ RedirectPaging|safe }}
		</td>
	</tr>
</table>
