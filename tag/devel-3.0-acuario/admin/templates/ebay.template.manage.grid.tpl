			<table class="GridPanel SortableGrid" cellspacing="0" cellpadding="0" border="0" id="IndexGrid" style="width:100%; margin-top:10px">
				<tr align="right">
					<td colspan="6" style="padding:6px 0px 6px 0px" class="PagingNav">
						{{ Nav|safe }}
					</td>
				</tr>
			<tr class="Heading3">
				<td align="center">
					<input type="checkbox" id="checkalltemplates" onclick="$('.EbayTemplate').attr('checked', this.checked);">
				</td>
				<td>&nbsp;</td>
				<td>
					{% lang 'TemplateName' %} &nbsp;
					{{ SortLinksName|safe }}
				</td>
				<td>
					{% lang 'TemplateDate' %} &nbsp;
					{{ SortLinksDate|safe }}
				</td>
				<td align="center" style="width:100px;">
					{% lang 'TemplateEnabled' %} &nbsp;
					{{ SortLinksEnabled|safe }}
				</td>
				<td style="width:100px;">
					{% lang 'Action' %}
				</td>
			</tr>
			{{ EbayTemplateGrid|safe }}
			<tr align="right">
				<td colspan="6" style="padding:6px 0px 6px 0px" class="PagingNav">
					{{ Nav|safe }}
				</td>
			</tr>
		</table>