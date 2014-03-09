	<div class="BodyContainer">
		<table id="Table13" cellSpacing="0" cellPadding="0" width="100%">
		<tr>
			<td class="Heading1">{% lang 'ImportOrdertrackingnumbersStep3' %}</td>
		</tr>
		<tr>
			<td class="Intro">
				<p>{% lang 'ImportOrdertrackingnumbersStep3Desc' %}
				</p>
			</td>
		</tr>
		<tr>
			<td>
				<input type="button" value="{% lang 'StartImport' %}" id="StartImport" onclick="startImport(); return false;" class="FormButton" />
			</td>
		</tr>
		</table>
	</div>
	<script type="text/javascript">
		function ConfirmCancel()
		{
			if(confirm('{% lang 'ConfirmCancelImport' %}'))
				window.location = 'index.php?ToDo=importOrdertrackingnumbers';
		}

		function startImport()
		{
			tb_show('', 'index.php?ToDo=importOrdertrackingnumbers&Step=ImportFrame&ImportSession={{ ImportSession|safe }}&keepThis=true&TB_iframe=tue&height=240&width=400&modal=true', '');
			document.getElementById('StartImport').disabled = true;
			document.getElementById('StartImport').value = '{% lang 'ImportRunning' %}';
		}
	</script>