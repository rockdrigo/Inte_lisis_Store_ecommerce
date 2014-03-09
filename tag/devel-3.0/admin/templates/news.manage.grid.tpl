			<table class="GridPanel SortableGrid" cellspacing="0" cellpadding="0" border="0" id="IndexGrid" style="width:100%; margin-top:10px">
				<tr align="right">
					<td colspan="9" style="padding:6px 0px 6px 0px" class="PagingNav">
						{{ Nav|safe }}
					</td>
				</tr>
			<tr class="Heading3">
				<td align="center"><input type="checkbox" onclick="ToggleDeleteBoxes(this.checked)"></td>
				<td>&nbsp;</td>
				<td>
					{% lang 'NewsTitle' %} &nbsp;
					{{ SortLinksTitle|safe }}
				</td>
				<td>
					{% lang 'Date' %} &nbsp;
					{{ SortLinksDate|safe }}
				</td>
				<td style="width:70px;">
					{% lang 'Visible' %} &nbsp;
					{{ SortLinksVisible|safe }}
				</td>
				<td style="width:100px;">
					{% lang 'Action' %}
				</td>
			</tr>
			{{ NewsGrid|safe }}
			<tr align="right">
				<td colspan="9" style="padding:6px 0px 6px 0px" class="PagingNav">
					{{ Nav|safe }}
				</td>
			</tr>
		</table>