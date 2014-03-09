
			<tr class="Heading3" style="display: {{ DisplayGrid|safe }}">
				<td align="center" style="width:18px"><input type="checkbox" onclick="ToggleDeleteBoxes(this.checked)"></td>
				<td>&nbsp;</td>
				<td>
					{% lang 'CustomerGroupName' %} &nbsp;
					{{ SortLinksGroupName|safe }}
				</td>
				<td>
					{% lang 'Discount' %} &nbsp;
					{{ SortLinksDiscount|safe }}
				</td>
				<td>
					<span class="HelpText" onmouseout="HideQuickHelp(this);" onmouseover="ShowQuickHelp(this, '{% lang 'DiscountRules' %}', '{% lang 'DiscountRulesHelp' %}');">{% lang 'DiscountRules' %}</span> &nbsp;
					{{ SortLinksDiscountRules|safe }}
				</td>
				<td>
					{% lang 'CustomersInGroup' %} &nbsp;
					{{ SortLinksCustomersInGroup|safe }}
				</td>
				<td style="width:120px;">
					{% lang 'Action' %}
				</td>
			</tr>
			{{ CustomerGroupsGrid|safe }}
			<tr align="right">
				<td colspan="11" style="padding:6px 0px 6px 0px" class="PagingNav">
					{{ Nav|safe }}
				</td>
			</tr>
