			<table class="GridPanel SortableGrid" cellspacing="0" cellpadding="0" border="0" id="IndexGrid" style="width:100%; display: {{ DisplayGrid|safe }}">
				<tr align="right">
					<td colspan="8" style="padding:6px 0px 6px 0px" class="PagingNav">
						{{ Nav|safe }}
					</td>
				</tr>
			<tr class="Heading3">
				<td align="center"><input type="checkbox" onclick="ToggleDeleteBoxes(this.checked)"></td>
				<td>&nbsp;</td>
				<td>
					{% lang 'UserName1' %} &nbsp;
					{{ SortLinksUser|safe }}
				</td>
				<td style="width:30%">
					{% lang 'Name' %} &nbsp;
					{{ SortLinksName|safe }}
				</td>
				<td>
					{% lang 'UserEmail' %} &nbsp;
					{{ SortLinksEmail|safe }}
				</td>
				<td style="width: 150px; {{ HideVendorColumn|safe }}">
					{% lang 'Vendor' %} &nbsp;
					{{ SortLinksVendor|safe }}
				</td>
				<td style="width:80px; display: {{ StatusField|safe }}">
					{% lang 'UserStatus' %} &nbsp;
					{{ SortLinksStatus|safe }}
				</td>
				<td style="width:70px">
					{% lang 'Action' %}
				</td>
			</tr>
			{{ UserGrid|safe }}
			<tr align="right">
				<td colspan="8" style="padding:6px 0px 6px 0px" class="PagingNav">
					{{ Nav|safe }}
				</td>
			</tr>
		</table>