	<div class="BodyContainer">
		<table cellSpacing="0" cellPadding="0" width="100%" style="margin-left: 4px; margin-top: 8px;">
		<tr>
			<td class="Heading1">{% lang 'ImportProductVariationsStep5' %}</td>
		</tr>
		<tr>
			<td class="Intro"><p>{% lang 'ImportProductVariationsStep5Desc' %}</p>
			{{ Message|safe }}</td>
		</tr>
		<tr>
			<td>
				{{ Report|safe }}
			</td>
		</tr>
		</table>
	</div>
	<script type="text/javascript">
		function ShowReport(reporttype) {
			var link = 'index.php?ToDo=importProductVariations&Step=ViewReport&ImportSession={{ ImportSession|safe }}&ReportType='+reporttype;

			var top = screen.height / 2 - (230);
			var left = screen.width / 2 - (250);

			window.open(link,"reportWin","left=" + left + ",top="+top+",toolbar=false,status=no,directories=false,menubar=false,scrollbars=false,resizable=false,copyhistory=false,width=500,height=460");
		}
	</script>