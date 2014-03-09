{% lang 'CATEGORY2X1instructions' %}

<div id="usedforcatdiv" style="padding-left:25px;float:left;">
	<select multiple="multiple" size="12" name="var_catids[]" id="var_catids" class="Field250 ISSelectReplacement">
		{{ CategoryList|safe }}
	</select>
</div>
<div id="daysactivediv" style="padding-left:25px;float:left;">
	<select multiple="multiple" size="12" name="var_daysactive[]" id="var_daysactive" class="Field250 ISSelectReplacement">
		{{ DaysActive|safe }}
	</select>
</div>
<div style="clear : both;"></div>
<div style="padding-left:30px; margin-top:3px;">(<a onclick="SelectAll(true)" href="javascript:void(0)">Select All</a> / <a onclick="SelectAll(false)" href="javascript:void(0)">Unselect All</a>)</div>

<script type="text/javascript">

		var select = document.getElementById('var_catids');
		ISSelectReplacement.replace_select(select);

		function SelectAll(Status)
		{
			$('#var_catids input').attr('checked', !Status);
			$('#var_catids input').trigger('click');
			$('#var_catids option').attr('selected', Status);
		}

</script>