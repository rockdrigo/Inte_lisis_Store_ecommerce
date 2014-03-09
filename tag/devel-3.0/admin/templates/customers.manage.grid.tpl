{% import "macros/util.tpl" as util %}
		<table class="GridPanel SortableGrid AutoExpand" cellspacing="0" cellpadding="0" border="0" id="IndexGrid" style="width:100%;">
				<tr>
					<td colspan="11">
						<table class="LetterSort" cellspacing="2" cellpadding="0" border="0">
							<tr>
								{% for letter in letters %}
									<td width="3%"><a class="SortLink {% if letter == activeLetter %}ActiveLetter{% endif %}" href="index.php?ToDo=viewCustomers&amp;{{ letterURL|http_build_query }}&amp;letter={{ letter }}">{{ letter }}</a></td>
								{% endfor %}
								<td width="3%"><a class="SortLink" href="index.php?ToDo=viewCustomers&amp;{{ letterURL|http_build_query }}">{% lang 'Clear' %}</a></td>
							</tr>
						</table>
					</td>
				</tr>
				<tr align="right">
					<td colspan="11" style="padding:6px 0px 6px 0px" class="PagingNav">
						{{ util.paging(numCustomers, perPage, currentPage, pageURL, true) }}
					</td>
				</tr>
			<tr class="Heading3">
				<td align="center"><input type="checkbox" onclick="ToggleDeleteBoxes(this.checked)"></td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>
					{% lang 'CustName' %} &nbsp;
					{{ SortLinksName|safe }}
				</td>
				<td>
					{% lang 'Email' %} &nbsp;
					{{ SortLinksEmail|safe }}
				</td>
				<td>
					{% lang 'Phone' %} &nbsp;
					{{ SortLinksPhone|safe }}
				</td>
				<td style="display: {{ HideGroup|safe }}">
					{% lang 'CustomerGroup' %} &nbsp;
					{{ SortLinksGroup|safe }}
				</td>
				<td style="display: {{ HideStoreCredit|safe }}">
					{% lang 'StoreCredit' %} &nbsp;
					{{ SortLinksStoreCredit|safe }}
				</td>
				<td>
					{% lang 'CustDateCreated' %} &nbsp;
					{{ SortLinksDate|safe }}
				</td>
				<td>
					{% lang 'NumOrders' %} &nbsp;
					{{ SortLinksNumOrders|safe }}
				</td>
				<td>
					{% lang 'Action' %}
				</td>
			</tr>
			{{ CustomerGrid|safe }}
			<tr align="right">
				<td colspan="11" style="padding:6px 0px 6px 0px" class="PagingNav">
					{{ util.paging(numCustomers, perPage, currentPage, pageURL, true) }}
				</td>
			</tr>
		</table>