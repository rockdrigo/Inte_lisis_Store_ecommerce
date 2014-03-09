
	<div class="BodyContainer">
		<table id="Table13" cellSpacing="0" cellPadding="0" width="100%">
			<tr>
				<td class="Heading1">{% lang 'ViewExportTemplates' %}</td>
			</tr>
			<tr>
				<td class="Intro">
					<p>{% lang 'ManageExportTemplatesIntro' %}</p>
					{{ Message|safe }}
					<p>
						<input type="button" name="IndexAddButton" value="{% lang 'AddExportTemplate' %}..." id="IndexCreateButton" class="SmallButton" onclick="document.location.href='index.php?ToDo=createExportTemplate'" /> &nbsp;<input type="button" name="IndexDeleteButton" value="{% lang 'DeleteSelected' %}" id="IndexDeleteButton" class="SmallButton" style="min-width: 110px;" onclick="ConfirmDeleteSelected()" {{ DisableDelete|safe }} />
					</p>
				</td>
			</tr>
			<tr>
				<td style="display: {{ DisplayGrid|safe }}">
					<form name="frmTemplates" id="frmTemplates" method="post" action="index.php?ToDo=deleteExportTemplate">
						<div class="GridContainer">
							{{ TemplatesGrid|safe }}
						</div>
					</form>

				</td>
			</tr>
		</table>
	</div>

	<script type="text/javascript">
		function ConfirmDeleteSelected()
		{
			var fp = document.getElementById("frmTemplates").elements;
			var c = 0;

			for(i = 0; i < fp.length; i++)
			{
				if(fp[i].type == "checkbox" && fp[i].checked)
					c++;
			}

			if(c > 0)
			{
				if(confirm("{% lang 'ConfirmDeleteExportTemplates' %}"))
					document.getElementById("frmTemplates").submit();
			}
			else
			{
				alert("{% lang 'ChooseExportTemplate' %}");
			}
		}

		function ToggleDeleteBoxes(Status)
		{
			$("#frmTemplates :checkbox:enabled").attr("checked", Status);
		}

		function PerformAction(select) {
			var action = select.value;

			if (action == "") {
				return;
			}

			var id = select.id.substr(6);
			var location = "";
			switch (action) {
				case "edit":
					location = 'index.php?ToDo=editExportTemplate&tempId=' + id;
					break;
				case 'delete':
					if(confirm("{% lang 'ConfirmDeleteExportTemplate' %}")) {
						location = 'index.php?ToDo=deleteExportTemplate&tempId=' + id;
					}
					break;
				default:
					location = 'index.php?ToDo=startExport&t=' + action + "&tempId=" + id;
					break;
			}

			if (location) {
				document.location = location;
			}
		}
	</script>