<form enctype="multipart/form-data" action="index.php?ToDo={{ FormAction|safe }}" onsubmit="return ValidateForm(CheckForm)" id="frmTemplate" method="post">
{{ hiddenFields|safe }}
<input id="currentTab" name="currentTab" value="0" type="hidden">
<div class="BodyContainer">
	<table cellSpacing="0" cellPadding="0" width="100%" style="margin-left: 4px; margin-top: 8px;">
	<tr>
		<td class="Heading1">{{ TemplateTitle|safe }}</td>
	</tr>
	<tr>
		<td class="Intro">
			<p>{{ ExportIntro|safe }}</p>
			{{ Message|safe }}
			<p style="{{ HideForm|safe }}">
				<input type="submit" value="{% lang 'Continue' %}" class="FormButton" />
				<input type="reset" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()" />
			</p>
		</td>
	</tr>
	<tr>
		<td>
			<ul id="tabnav" style="{{ HideForm|safe }}"">
				<li><a href="#" class="active" id="tab0" onclick="ShowTab(0)">{% lang 'ExportDetails' %}</a></li>
				<li><a href="#" id="tab1" onclick="ShowTab(1)">{% lang 'DataSummary' %}</a></li>
			</ul>
		</td>
	</tr>
	<tr>
		<td>
			<!--Template Details-->
			<div id="div0" style="padding-top: 10px; {{ HideForm|safe }}">
				<table width="100%" class="Panel">
					<tr>
						<td class="Heading2" colspan="2">{% lang 'ExportFormatTitle' %}</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							<span class="Required">*</span>&nbsp;{% lang 'TemplateLabel' %}:
						</td>
						<td>
							{{ TemplatesList|safe }}
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							<span class="Required">*</span>&nbsp;{% lang 'ExportFileFormat' %}:
						</td>
						<td>
							<table border="0">
								{{ Methods|safe }}
							</table>
						</td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<td>
							<p>
								<input type="submit" value="{% lang 'Continue' %}" class="FormButton" />
								<input type="reset" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()" />
							</p>
						</td>
					</tr>
				</table>
			</div>

			<div id="div1" style="padding-top: 5px; display: none;">
				<div class="GridContainer" id="GridContainer">
					{{ DataGrid|safe }}
				</div>
			</div>
		</td>
	</tr>
	</table>
</div>
</form>

<script type="text/javascript">
	function ConfirmCancel()
	{
		if(confirm('{% lang 'CancelMessage' %}'))
		{
			document.location.href='{{ ViewLink|safe }}';
		}
		else
		{
			return false;
		}
	}

	function CheckForm() {
		if ($("#template").val() == null) {
			alert("{% lang 'NoTemplateSelected' %}");

			return false;
		}

		var formData = $("#frmTemplate").serialize();

		$.iModal({
			type: 'ajax',
			url: 'index.php?ToDo={{ FormAction|safe }}',
			urlData: formData,
			width: 400,
			onBeforeClose: function() {
				CancelAjaxExport();
			}
		});

		return false;
	}

	function ShowTab(T) {
		i = 0;
		while (document.getElementById("tab" + i) != null) {
			$('#div'+i).hide();
			$('#tab'+i).removeClass('active');
			++i;
		}

		$('#div'+T).show();
		$('#tab'+T).addClass('active');
	}
</script>