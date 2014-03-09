			<table class="GridPanel SortableGrid" cellspacing="0" cellpadding="0" border="0" id="IndexGrid" style="width:100%;">
			<tr class="Heading3">
				<td align="center"><input type="checkbox" onclick="ToggleDeleteBoxes(this.checked)"></td>
				<td>&nbsp;</td>
				<td>
					{% lang 'BannerName' %} &nbsp;
					{{ SortLinksName|safe }}
				</td>
				<td>
					{% lang 'BannerLocation' %} &nbsp;
					{{ SortLinksLocation|safe }}
				</td>
				<td>
					{% lang 'DateCreated' %} &nbsp;
					{{ SortLinksDate|safe }}
				</td>
				<td style="width:70px;">
					{% lang 'Visible' %} &nbsp;
					{{ SortLinksStatus|safe }}
				</td>
				<td style="width:80px;">
					{% lang 'Action' %}
				</td>
			</tr>
			{{ BannerGrid|safe }}
		</table>