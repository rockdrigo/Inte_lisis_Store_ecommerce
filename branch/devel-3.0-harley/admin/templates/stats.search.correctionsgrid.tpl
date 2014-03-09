<a name="searchCorrectionsAnchor"></a>
<div style="text-align:right;  {{ HidePagingLinks|safe }}">
	<div style="padding-bottom:10px">
		{% lang 'ResultsPerPage' %}:
		<select onchange="ChangeSearchCorrectionsPerPage(this.options[this.selectedIndex].value)">
			<option {{ IsShowPerPage5|safe }} value="5">5</option>
			<option {{ IsShowPerPage10|safe }} value="10">10</option>
			<option {{ IsShowPerPage20|safe }} value="20">20</option>
			<option {{ IsShowPerPage30|safe }} value="30">30</option>
			<option {{ IsShowPerPage50|safe }} value="50">50</option>
			<option {{ IsShowPerPage100|safe }} value="100">100</option>
			<option {{ IsShowPerPage200|safe }} value="200">200</option>
		</select>
	</div>
	{{ Paging|safe }}
</div>
<br style="clear: both;" />
<table width="100%" border=0 cellspacing=1 cellpadding=5 class="text">
	<tr class="Heading3">
		<td nowrap align="left" width="25%">
			{% lang 'SearchTerms' %} &nbsp;
			{{ SortLinksSearchTerms|safe }}
		</td>
		<td align="right">
			<span onmouseover="ShowQuickHelp(this, '{% lang 'ProductsShownBefore' %}', '{% lang 'ProductsShownBeforeHelp' %}');" onmouseout="HideQuickHelp(this);" class="HelpText">{% lang 'ProductsShownBefore' %}</span> &nbsp;
			{{ SortLinksProductsShownBefore|safe }}
		</td>
		<td nowrap align="left" width="25%">
			<span onmouseover="ShowQuickHelp(this, '{% lang 'CorrectedSearchTerms' %}', '{% lang 'CorrectedSearchTermsHelp' %}');" onmouseout="HideQuickHelp(this);" class="HelpText">{% lang 'CorrectedSearchTerms' %}</span> &nbsp;
			{{ SortLinksCorrectedSearchTerms|safe }}
		</td>
		<td align="right">
			<span onmouseover="ShowQuickHelp(this, '{% lang 'ProductsShownAfter' %}', '{% lang 'ProductsShownAfterHelp' %}');" onmouseout="HideQuickHelp(this);" class="HelpText">{% lang 'ProductsShownAfter' %}</span> &nbsp;
			{{ SortLinksProductsShownAfter|safe }}
		</td>
		<td align="right">
			{% lang 'NumberOfOccurances' %} &nbsp;
			{{ SortLinksNumberOfOccurances|safe }}
		</td>
		<td nowrap>
			{% lang 'SearchCorrectionType' %} &nbsp;
			{{ CorrectionType|safe }}
		</td>
	</tr>
	{{ ResultsGrid|safe }}
</table>
{{ JumpToSearchCorrectionsGrid|safe }}
