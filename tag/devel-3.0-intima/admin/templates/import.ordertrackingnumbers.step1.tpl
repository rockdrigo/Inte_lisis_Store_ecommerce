	<form enctype="multipart/form-data" action="index.php?ToDo=importOrdertrackingnumbers&Step=2" onsubmit="return ValidateForm(CheckImportOrdertrackingnumberForm)" id="frmImport" method="post">
	<div class="BodyContainer">
		<table cellSpacing="0" cellPadding="0" width="100%" style="margin-left: 4px; margin-top: 8px;">
		<tr>
			<td class="Heading1">{% lang 'ImportOrdertrackingnumbersStep1' %}</td>
		</tr>
		<tr>
			<td class="Intro">
				<p>{% lang 'ImportOrdertrackingnumbersStep1Desc' %}</p>
				{{ Message|safe }}
			</td>
		</tr>
		<tr>
			<td>
				<div>
					<input type="reset" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()" />
					<input type="submit" value="{% lang 'Next' %} &raquo;" class="FormButton" />
				</div>
				<br />
			</td>
		</tr>

		<tr>
			<td>
			<table class="Panel">
				<tr>
				  <td class="Heading2" colspan=2>{% lang 'OrderStatusDetails' %}</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						<span class="Required">&nbsp;</span>&nbsp;{% lang 'UpdateOrderStatusTo' %}
					</td>
					<td>
						<select id="updateOrderStatus" name="updateOrderStatus" class="Field">
							<option value="0">{% lang 'DoNotUpdate' %}</option>
							<option value="1">{% lang 'Pending' %}</option>
							<option value="7">{% lang 'AwaitingPayment' %}</option>
							<option value="11">{% lang 'AwaitingFulfillment' %}</option>
							<option value="9">{% lang 'AwaitingShipment' %}</option>
							<option value="8">{% lang 'AwaitingPickup' %}</option>
							<option value="3">{% lang 'PartiallyShipped' %}</option>
							<option value="10">{% lang 'Completed' %}</option>
							<option value="2" selected="selected">{% lang 'Shipped' %}</option>
							<option value="5">{% lang 'Cancelled' %}</option>
							<option value="6">{% lang 'Declined' %}</option>
							<option value="4">{% lang 'Refunded' %}</option>
						</select>
						<img onMouseOut="HideHelp('u1');" onMouseOver="ShowHelp('u1', '{% lang 'UpdateOrderStatus' %}', '{% lang 'UpdateOrderStatusDesc' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="u1"></div>
					</td>
				</tr>
			</table>

			  <table class="Panel">
				<tr>
				  <td class="Heading2" colspan=2>{% lang 'ImportDetails' %}</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						<span class="Required">&nbsp;</span>&nbsp;{% lang 'ImportOverride' %}
					</td>
					<td>
						<label><input type="checkbox" name="OverrideDuplicates" value="1" /> {% lang 'YesImportOverride' %}</label>
						<img onMouseOut="HideHelp('a2');" onMouseOver="ShowHelp('a2', '{% lang 'ImportOverride' %}', '{% lang 'ImportOverrideDesc' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="a2"></div>
					</td>
				</tr>
			</table>
			  <table class="Panel">
				<tr>
				  <td class="Heading2" colspan=2>{% lang 'ImportFileDetails' %}</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						<span class="Required">*</span>&nbsp;{% lang 'ImportFile' %}:
					</td>
					<td>
						<div>
							<label>
								<input id="OrdertrackingnumberImportUseUpload" type="radio" name="useserver" value="0" checked="checked" onclick="ToggleSource();" />
								{% lang 'ImportFileUpload' %}
								{% lang 'ImportMaxSize' with [
									'maxSize': ImportMaxSize
								]%}
							</label>
							<img onMouseOut="HideHelp('d1');" onMouseOver="ShowHelp('d1', '{% lang 'ImportFileUpload' %}', '{% lang 'ImportFileUploadDesc' %}')" src="images/help.gif" width="24" height="16" border="0">
							<div style="display: none;" id="d1"></div>
						</div>
						<div id="OrdertrackingnumberImportUploadField" style="margin-left: 25px;">
							<input type="file" name="importfile" id="ImportFile" class="Field250" />
						</div>

						<div>
							<label><input id="OrdertrackingnumberImportUseServer" type="radio" name="useserver" value="1" onclick="ToggleSource();" /> {% lang 'ImportFileServer' %}</label>
							<img onMouseOut="HideHelp('d2');" onMouseOver="ShowHelp('d2', '{% lang 'ImportFileServer' %}', '{% lang 'ImportFileServerDesc' %}')" src="images/help.gif" width="24" height="16" border="0">
							<div style="display: none;" id="d2"></div>
						</div>
						<div id="OrdertrackingnumberImportServerField" style="margin-left: 25px; display: none;">
							<select name="serverfile" id="ServerFile" class="Field250">
								<option value="">{% lang 'ImportChooseFile' %}</option>
								{{ ServerFiles|safe }}
							</select>
						</div>
						<div id="OrdertrackingnumberImportServerNoList" style="margin: 5px 0 0 25px; display: none; font-style: italic;" class="Field250">
							{% lang 'FieldNoServerFiles' %}
						</div>
					</td>
				</tr>

				<tr>
					<td class="FieldLabel">
						<span class="Required">*</span>&nbsp;{% lang 'ImportContainsHeaders' %}
					</td>
					<td>
						<label><input type="checkbox" name="Headers" value="1" /> {% lang 'YesImportContainsHeaders' %}</label>
						<img onMouseOut="HideHelp('d3');" onMouseOver="ShowHelp('d3', '{% lang 'ImportContainsHeaders' %}', '{% lang 'ImportContainsHeadersDesc' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="d3"></div>
					</td>
				</tr>

				<tr>
					<td class="FieldLabel">
						<span class="Required">*</span>&nbsp;{% lang 'ImportFieldSeparator' %}:
					</td>
					<td>
						<input type="text" name="FieldSeparator" id="FieldSeparator" class="Field250" value="{{ FieldSeparator|safe }}" />
						<img onMouseOut="HideHelp('d4');" onMouseOver="ShowHelp('d4', '{% lang 'ImportFieldSeparator' %}', '{% lang 'ImportFieldSeparatorDesc' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="d4"></div>
					</td>
				</tr>

				<tr>
					<td class="FieldLabel">
						<span class="Required">*</span>&nbsp;{% lang 'ImportFieldEnclosure' %}:
					</td>
					<td>
						<input type="text" name="FieldEnclosure" id="FieldEnclosure" class="Field250" value='{{ FieldEnclosure|safe }}' />
						<img onMouseOut="HideHelp('d5');" onMouseOver="ShowHelp('d5', '{% lang 'ImportFieldEnclosure' %}', '{% lang 'ImportFieldEnclosureDesc' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="d5"></div>
					</td>
				</tr>
			</table>
			<table border="0" cellspacing="0" cellpadding="2" width="100%" class="PanelPlain">
				<tr>
					<td width="200" class="FieldLabel">
						&nbsp;
					</td>
					<td>
						<input type="reset" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()" />
						<input type="submit" value="{% lang 'Next' %} &raquo;" class="FormButton" />
					</td>
				</tr>
			</table>
			</td>
		</tr>
		</table>
	</div>
	</form>

	<script type="text/javascript">
	function ConfirmCancel()
	{
		if(confirm('{% lang 'ConfirmCancelImport' %}'))
			window.location = 'index.php?ToDo=manageordertrackingnumbers';
	}

	function CheckImportOrdertrackingnumberForm()
	{
		var f = document.getElementById('OrdertrackingnumberImportUseUpload');
		if(f.checked == true)
		{
			var f = document.getElementById('ImportFile');
			if(f.value == '')
			{
				alert('{% lang 'NoImportFile' %}');
				f.focus();
				return false;
			}
		}
		else
		{
			var f = document.getElementById('ServerFile');
			if(f.value < 1)
			{
				alert('{% lang 'NoImportFile' %}');
				f.focus();
				return false;
			}
		}

		var f = document.getElementById('FieldSeparator');
		if(f.value == '')
		{
			alert('{% lang 'NoImportFieldSeparator' %}');
			f.focus();
			return false;
		}

		var f = document.getElementById('FieldEnclosure');
		if(f.value == '')
		{
			alert('{% lang 'NoImportFieldEnclosure' %}');
			f.focus();
			return false;
		}
		return true;
	}

	function ToggleSource()
	{
		var file = document.getElementById('OrdertrackingnumberImportUseUpload');
		if(file.checked == true)
		{
			document.getElementById('OrdertrackingnumberImportUploadField').style.display = '';
			document.getElementById('OrdertrackingnumberImportServerField').style.display = 'none';
			document.getElementById('OrdertrackingnumberImportServerNoList').style.display = 'none';
		}
		else
		{
			document.getElementById('OrdertrackingnumberImportUploadField').style.display = 'none';
			if(document.getElementById('OrdertrackingnumberImportServerField').getElementsByTagName('SELECT')[0].options.length == 1)
			{
				document.getElementById('OrdertrackingnumberImportServerNoList').style.display = '';
			}
			else
			{
				document.getElementById('OrdertrackingnumberImportServerField').style.display = '';
			}
		}
	}
	</script>