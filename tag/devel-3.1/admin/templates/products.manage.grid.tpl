{% import "macros/util.tpl" as util %}
		<table class="GridPanel SortableGrid AutoExpand" cellspacing="0" cellpadding="0" border="0" id="IndexGrid" style="width:100%;">
			<tr>
				<td colspan="12">
					<table class="LetterSort" cellspacing="2" cellpadding="0" border="0">
						<tr>
							{% for displayLetter, letter in letters %}
								<td width="3%"><a class="SortLink {% if letter == activeLetter %}ActiveLetter{% endif %}" href="index.php?ToDo=viewProducts&amp;{{ letterURL|http_build_query }}&amp;letter={{ letter }}">{{ displayLetter }}</a></td>
							{% endfor %}
							<td width="3%"><a class="SortLink" href="index.php?ToDo=viewProducts&amp;{{ letterURL|http_build_query }}">{% lang 'Clear' %}</a></td>
						</tr>
					</table>
				</td>
			</tr>
			<tr align="right">
				<td colspan="12" style="padding:6px 0px 6px 0px" class="PagingNav">
					{{ util.paging(numProducts, perPage, currentPage, pageURL, true) }}
				</td>
			</tr>
			<tr class="Heading3">
				<td align="center"><input type="checkbox" onclick="ToggleDeleteBoxes(this.checked)"></td>
				<td>&nbsp;</td>
				<td style="display: {{ HideInventoryOptions|safe }}">
					&nbsp;
				</td>
				<td class="ImageField">{% lang 'Image' %}</td>
				<td>
					{% lang 'ProductSKU' %} &nbsp;
					{{ SortLinksCode|safe }}
				</td>
				<td style="width: 95px; display: {{ HideInventoryOptions|safe }}">
					<span class="HelpText" onmouseout="HideQuickHelp(this);" onmouseover="ShowQuickHelp(this, '{% lang 'StockLevel' %}', '{% lang 'StockLevelHelp' %}');">{% lang 'StockLevel' %}</span> &nbsp;
					{{ SortLinksStock|safe }}
				</td>
				<td colspan="{{ ProductNameSpan|safe }}">
					{% lang 'ProductName' %} &nbsp;
					{{ SortLinksName|safe }}
				</td>
				<td width="70" style="text-align: right;">
					{% lang 'ProductPrice' %} &nbsp;
					{{ SortLinksPrice|safe }}
				</td>
				<td width="85" style="text-align: right;">
					{% lang 'Status' %} &nbsp;
					{{ SortLinksStatus|safe }}
				</td>
				<td width="70" nowrap="nowrap">
					{% lang 'ProductVisible' %} &nbsp;
					{{ SortLinksVisible|safe }}
				</td>
				<td width="80" nowrap="nowrap">
					{% lang 'ProductFeatured' %} &nbsp;
					{{ SortLinksFeatured|safe }}
				</td>
				<td style="width:70px;">
					{% lang 'Action' %}
				</td>
			</tr>
			{{ ProductGrid|safe }}
			<tr align="right">
				<td colspan="12" style="padding:6px 0px 6px 0px" class="PagingNav">
					{{ util.paging(numProducts, perPage, currentPage, pageURL, true) }}
				</td>
			</tr>
		</table>