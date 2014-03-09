		<table class="GridPanel SortableGrid" cellspacing="0" cellpadding="0" border="0" id="IndexGrid" style="width:100%;">
				<tr align="right">
					<td colspan="10" style="padding:6px 0px 6px 0px" class="PagingNav">
						{{ Nav|safe }}
						<br />
					</td>
				</tr>
			<tr class="Heading3">
				<td align="center" style="width:18px"><input type="checkbox" onclick="ToggleDeleteBoxes(this.checked)"></td>
				<td style="width:30px;"></td>
				<td>
					<span>{% lang 'DiscountName' %}</span>
				</td>
				<td nowrap style="width:100px">
					{% lang 'DiscountMaxUses' %} &nbsp;
				</td>
				<td nowrap style="width:100px">
					<span onmouseover="ShowQuickHelp(this, '{% lang 'DiscountCurrentUses' %}', '{% lang 'DiscountCurrentUsesHelp' %}');" onmouseout="HideQuickHelp(this);" class="HelpText">{% lang 'DiscountCurrentUses' %}</span>
				</td>
				<td nowrap style="width:100px">
					<div style="display:none" id="invDiv" name="invDiv"></div>
					{% lang 'DiscountExpiryDate' %}
				</td>
				<td style="width:80px">
					{% lang 'Enabled' %} &nbsp;
				</td>
				<td style="width:130px">
					<span onmouseover="ShowQuickHelp(this, '{% lang 'Halts' %}', '{% lang 'HaltsHelp' %}');" onmouseout="HideQuickHelp(this);" class="HelpText">{% lang 'Halts' %}</span>
				</td>
				<td style="width:80px">
					{% lang 'Action' %}
				</td>
			</tr>
		</table>
		<ul class="SortableList" id="DiscountList" style=" padding-top: 1px; padding-bottom: 1px; z-index:0">
					{{ DiscountGrid|safe }}
		</ul>