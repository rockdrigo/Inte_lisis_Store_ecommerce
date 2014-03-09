{% import "macros/util.tpl" as util %}
{% import "macros/forms.tpl" as formBuilder %}

<script type="text/javascript" src="../javascript/formfield.js?{{ JSCacheToken }}"></script>
<form action="index.php?ToDo={{ FormAction|safe }}" onsubmit="return ValidateForm(checkAddCustomerForm)" id="frmCustomer" method="post">
	<input type="hidden" name="customerId" id="customerId" value="{{ CustomerId|safe }}" />
	<input type="hidden" name="newCustomer" id="newCustomer" value="{{ NewCustomer|safe }}" />
	<input id="currentTab" name="currentTab" value="0" type="hidden" />

	<div id="content">
		<h1>{{ Title|safe }}</h1>

		<p class="intro">
			{{ Intro|safe }}
		</p>

		<div id="MessageTmpBlock">
			{{ Message|safe }}
		</div>

		{{ formBuilder.startButtonRow }}
			<input type="submit" value="{% lang 'SaveAndExit' %}" class="FormButton" name="SaveButton1" />
			<input type="submit" value="{{ SaveAndAddAnother|safe }}" name="SaveContinueButton1"  onclick="saveAndAddAnother();" class="FormButton Field150" />
			<input type="reset" value="{% lang 'Cancel' %}" class="FormButton" name="CancelButton1" onclick="confirmCancel()" />
		{{ formBuilder.endButtonRow }}

		<ul id="tabnav">
			<li><a href="#" id="tab0" onclick="ShowTab(0)" class="active">{% lang 'CustomerDetails' %}</a></li>
			<li><a href="#" id="tab1" onclick="ShowTab(1)">{% lang 'CustomerAddressBook' %}</a></li>
		</ul>

		<div id="div0">
			<p class="intro">
				{% lang 'CustomerDetailsIntro' %}
			</p>

			{{ formBuilder.startForm }}
				{{ formBuilder.heading(lang.CustomerDetails) }}

				{{ formBuilder.startRow([
					'label': lang.CustomerFirstName ~ ':',
					'required': true
				]) }}
					<input type="text" id="custFirstName" name="custFirstName" class="Field300" value="{{ CustomerFirstName|safe }}" />
				{{ formBuilder.endRow }}

				{{ formBuilder.startRow([
					'label': lang.CustomerLastName ~ ':',
					'required': true
				]) }}
					<input type="text" id="custLastName" name="custLastName" class="Field300" value="{{ CustomerLastName|safe }}" />
				{{ formBuilder.endRow }}

				{{ formBuilder.startRow([
					'label': lang.CustomerCompany ~ ':'
				]) }}
					<input type="text" id="custCompany" name="custCompany" class="Field300" value="{{ CustomerCompany|safe }}" />
				{{ formBuilder.endRow }}
				<!-- 
				{{ formBuilder.startRow([
					'label': 'RFC' ~ ':'
				]) }}
					<input type="text" id="custRFC" name="custRFC" class="Field300" value="{{ CustomerRFC|safe }}" />
				{{ formBuilder.endRow }}
 				-->
				{{ formBuilder.startRow([
					'label': lang.CustomerEmail ~ ':',
					'required': true
				]) }}
					<input type="text" id="custEmail" name="custEmail" class="Field300" value="{{ CustomerEmail|safe }}">
					<input type="button" onclick="checkEmailUniqueRequest(); return false;" value="{% lang 'CustomerEmailUniqueCheckLink' %}" class="FormButton Field120"/>
				{{ formBuilder.endRow }}

				{{ formBuilder.startRow([
					'label': lang.CustomerGroup ~ ':'
				]) }}
					<select id="custGroupId" name="custGroupId" class="Field300">
						<option value="0">{% lang 'CustomerGroupNotAssoc' %}</option>
						{{ CustomerGroupOptions|safe }}
					</select>
				{{ formBuilder.endRow }}

				{{ formBuilder.startRow([
					'label': lang.CustomerPhone ~ ':'
				]) }}
					<input type="text" id="custPhone" name="custPhone" class="Field80" value="{{ CustomerPhone|safe }}" />
				{{ formBuilder.endRow }}

				{% if HideStoreCredit != "none" %}
					{{ formBuilder.startRow([
						'label': lang.CustomerStoreCredit ~ ':'
					]) }}
						{{ CurrencyTokenLeft|safe }} <input type="text" id="custStoreCredit" name="custStoreCredit" class="Field50" value="{{ CustomerStoreCredit|safe }}"> {{ CurrencyTokenRight|safe }}
						<img onmouseout="HideHelp('dcuststorecredit');" onmouseover="ShowHelp('dcuststorecredit', '{% lang 'CustomerStoreCredit' %}', '{% lang 'CustomerStoreCreditHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="dcuststorecredit"></div>
					{{ formBuilder.endRow }}
				{% endif %}

				{{ CustomFields|safe }}
			{{ formBuilder.endForm }}

			{{ formBuilder.startForm }}
				{{ formBuilder.heading(lang.CustomerPasswordDetails) }}

				{{ formBuilder.startRow([
					'label': PasswordLabel ~ ':',
					'required': (CustomerId == 0)
				]) }}
					<input type="password" id="custPassword" name="custPassword" class="Field250" value="{{ CustomerPassword|safe }}" AUTOCOMPLETE = "OFF">
					<img onmouseout="HideHelp('dcustpassword');" onmouseover="ShowHelp('dcustpassword', '{{ PasswordLabel|safe }}', '{{ PasswordHelp|safe }}')" src="images/help.gif" width="24" height="16" border="0">
					<div style="display:none" id="dcustpassword"></div>
				{{ formBuilder.endRow }}

				{{ formBuilder.startRow([
					'label': lang.CustomerPasswordConfirm ~ ':',
					'required': (CustomerId == 0)
				]) }}
					<input type="password" id="custPasswordConfirm" name="custPasswordConfirm" class="Field250" value="{{ CustomerPasswordConfirm|safe }}" AUTOCOMPLETE = "OFF">
					<img onmouseout="HideHelp('dcustpasswordconfirm');" onmouseover="ShowHelp('dcustpasswordconfirm', '{% lang 'CustomerPasswordConfirm' %}', '{{ PasswordConfirmHelp|safe }}')" src="images/help.gif" width="24" height="16" border="0">
					<div style="display:none" id="dcustpasswordconfirm"></div>
				{{ formBuilder.endRow }}
			{{ formBuilder.endForm }}
		</div>
		<div id="div1">
			<div style="padding-bottom:5px">{% lang 'CustomerDetailsIntro' %}</div>
			<div class="MessageBox MessageBoxInfo" style="display: {{ CustomerAddressEmptyShow|safe }}">{{ CustomerAddressListWarning|safe }}</div>
			<p class="Intro" style="display: {{ HideCustomerAddressButtons|safe }};">
				<input type="button" value="{% lang 'CustomerAddShippingAddress' %}" onclick="addShippingAddress();" class="SmallButton Field150" {{ CustomerAddressAddDisabled|safe }} />
				<input type="button" value="{% lang 'DeleteSelected' %}" name="DeleteAddressesButton" onclick="confirmDeleteAddressBoxes();" class="SmallButton Field150" {{ CustomerAddressDeleteDisabled|safe }} />
			</p>
			<div class="GridContainer" style="display: {{ CustomerAddressEmptyHide|safe }}">
				{{ CustomerShippingAddressGrid|safe }}
			</div><br />
			<br />
		</div>

		<table border="0" cellspacing="0" cellpadding="2" width="100%" class="PanelPlain" id="SaveButtons">
			<tr>
				<td>
					<input type="submit" value="{% lang 'SaveAndExit' %}" class="FormButton" name="SaveButton2" />
					<input type="submit" value="{{ SaveAndAddAnother|safe }}" name="SaveContinueButton2" onclick="saveAndAddAnother();" class="FormButton Field150" />
					<input type="reset" value="{% lang 'Cancel' %}" class="FormButton" name="CancelButton2" onclick="confirmCancel()" />
				</td>
			</tr>
		</table>
		</td>
	</tr>
	</table>
</div>
</form>

<script type="text/javascript"><!--

	$(document).ready(function() {
		ShowTab({{ CurrentTab|safe }});
	});

	function ShowTab(T)
	{
			i = 0;
			while (document.getElementById("tab" + i) != null) {
				document.getElementById("div" + i).style.display = "none";
				document.getElementById("tab" + i).className = "";
				i++;
			}

			if (T == 1) {
				$('#SaveButtons').hide();
			} else {
				$('#SaveButtons').show();
			}

			document.getElementById("div" + T).style.display = "";
			document.getElementById("tab" + T).className = "active";
			document.getElementById("currentTab").value = T;
	}

	function getAddressBoxes()
	{
		return $("#IndexGrid :checkbox[name='addresses[]']");
	}

	function selectedAddressBoxes()
	{
		return getAddressBoxes().not("[checked=false]");
	}

	function toggleAddressBoxes(status)
	{
		getAddressBoxes().each(function() { $(this).attr("checked", status); });
	}

	function confirmDeleteAddressBoxes(addressId)
	{
		if ((!isNaN(addressId) && addressId > 0) || selectedAddressBoxes().length > 0) {
			if (confirm("{% lang 'ConfirmDeleteCustomerAddresses' %}")) {
				if (!isNaN(addressId) && addressId > 0) {
					MakeHidden('addresses', addressId, 'frmCustomer');
				}
				document.getElementById("frmCustomer").action = "index.php?ToDo=deleteCustomerAddress";
				document.getElementById("frmCustomer").submit();
			}
		} else {
			alert("{% lang 'ChooseCustomerAddress' %}");
		}
	}

	function saveAndAddAnother() {
		MakeHidden('addanother', '1', 'frmCustomer');
	}

	function saveAndAddAddress() {
		ShowTab(0);
		if (checkAddCustomerForm()) {
			MakeHidden('addaddresses', '1', 'frmCustomer');
			document.getElementById("frmCustomer").submit();
		}
	}

	function confirmCancel() {
		if(confirm('{{ CancelMessage|safe }}')) {
			document.location.href='index.php?ToDo=viewCustomers';
		} else {
			return false;
		}
	}

	function checkAddCustomerForm()
	{
		var checkFileds = new Array();

		checkFileds['custFirstName'] = "{% lang 'CustomerFirstNameRequired' %}"
		checkFileds['custLastName'] = "{% lang 'CustomerLastNameRequired' %}"
		checkFileds['custEmail'] = "{% lang 'CustomerEmailRequired' %}";

		if ("{{ PasswordRequiredCheck|safe }}" == "1") {
			checkFileds['custPassword'] = "{% lang 'CustomerPasswordRequired' %}";
		}

		for (var i in checkFileds) {
			if ($('#' + i).val() == '') {
				alert(checkFileds[i]);
				$('#' + i).focus();
				return false;
			}
		}

		if($('#custEmail').val().indexOf("@") == -1 || $('#custEmail').val().indexOf(".") == -1) {
			alert("{% lang 'CustomerEmailInvalue' %}");
			$('#custEmail').focus();
			return false;
                }

		if ($('#custPassword').val() !== $('#custPasswordConfirm').val()) {
			alert("{{ PasswordConfirmError|safe }}");
			$('#custPassword').focus();
			return false;
		}

		if($('#custStoreCredit').val() && isNaN(priceFormat($('#custStoreCredit').val()))) {
			alert("{% lang 'CustomerStoreCreditError' %}");
			$('#custStoreCredit').focus().select();
			return false;
		}

		/**
		 * Now for the custom fields
		 */
		var formfields = FormField.GetValues({{ CustomFieldsAccountFormId|safe }});

		for (var i=0; i<formfields.length; i++) {
			var rtn = FormField.Validate(formfields[i].field);

			if (!rtn.status) {
				alert(rtn.msg);
				FormField.Focus(formfields[i].field);
				return false;
			}
		}

		return true;
	}

	function checkEmailUniqueRequest(formCheck)
	{
		if (formCheck !== 1) {
			formCheck = 0;
		}

		var obj = {};

		obj.type    = 'POST';
		obj.url     = 'remote.php';
		obj.data    = {
				'w'            : 'checkemailuniqueness',
				'remoteSection': 'customers',
				'customerId'   : '{{ CustomerId|safe }}',
				'email'        : $('#custEmail').val()
				};
		obj.success = checkEmailUniqueResponse;

		$.ajax(obj);
	}

	function checkEmailUniqueResponse(data)
	{
		var message = $('message', data).text();

		$('#MessageTmpBlock').hide();
		$('#MessageTmpBlock').html(message);
		$('#MessageTmpBlock').show('slow');
	}

	function addShippingAddress()
	{
		document.getElementById('frmCustomer').action = 'index.php?ToDo=addCustomerAddress';
		document.getElementById('frmCustomer').submit();
		return false;
	}

	function editShippingAddress(addressId)
	{
		MakeHidden('addressId', addressId, 'frmCustomer');
		document.getElementById('frmCustomer').action = 'index.php?ToDo=editCustomerAddress';
		document.getElementById('frmCustomer').submit();
		return false;
	}

	{{ FormFieldEventData|safe }}

//--></script>