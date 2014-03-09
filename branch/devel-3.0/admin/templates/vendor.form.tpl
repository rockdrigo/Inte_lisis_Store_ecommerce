<form action="index.php?ToDo={{ FormAction|safe }}" id="frmVendor" method="post" onsubmit="return ValidateForm(CheckVendorForm)" enctype="multipart/form-data">
	<input type="hidden" name="vendorId" id="vendorId" value="{{ VendorId|safe }}" />
	<input type="hidden" name="currentTab" value="{{ CurrentTab|safe }}" id="currentTab" />
<div class="BodyContainer">
	<table class="OuterPanel">
		<tr>
			<td class="Heading1">{{ Title|safe }}</td>
		</tr>

		<tr>
			<td class="Intro">
				<p>{{ Intro|safe }}</p>
				{{ Message|safe }}
				<p>
					<input type="submit" name="SaveButton1" value="{% lang 'SaveAndExit' %}" class="FormButton" />
					<input type="submit" name="SaveAddAnotherButton1" value="{{ SaveAndAddAnother|safe }}" name="addAnother" class="FormButton" style="width:130px" />
					<input type="button" name="CancelButton1" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()" />				</p>
			</td>
		</tr>

		<tr>
			<td>
				<ul id="tabnav">
					<li><a href="#" class="active" id="tab0" onclick="ShowTab(0)">{% lang 'VendorInformation' %}</a></li>
					<li style="{{ HideShipping|safe }}"><a href="#" id="tab1" onclick="ShowTab(1)">{% lang 'VendorShipping' %}</a></li>
				</ul>
				<div id="div0">
					<table width="100%" class="Panel">
						<tr>
							<td class="Heading2" colspan="2">{% lang 'VendorProfile' %}</td>
						</tr>

						<tr>
							<td class="FieldLabel">
								<span class="Required">*</span> {% lang 'VendorName' %}:
							</td>
							<td>
								<input type="text" name="vendorname" id="vendorname" class="Field250" value="{{ VendorName|safe }}" />
								<img onmouseout="HideHelp('vendornamehelp');" onmouseover="ShowHelp('vendornamehelp', '{% lang 'VendorName' %}', '{% lang 'VendorNameHelp' %}')" src="images/help.gif" alt="" border="0" />
								<div style="display:none" id="vendornamehelp"></div>
							</td>
						</tr>

						<tr>
							<td class="FieldLabel">
								<span class="Required">*</span> {% lang 'VendorPhone' %}:
							</td>
							<td>
								<input type="text" name="vendorphone" id="vendorphone" class="Field250" value="{{ VendorPhone|safe }}" />
								<img onmouseout="HideHelp('vendorphonehelp');" onmouseover="ShowHelp('vendorphonehelp', '{% lang 'VendorPhone' %}', '{% lang 'VendorPhoneHelp' %}')" src="images/help.gif" alt="" border="0" />
								<div style="display:none" id="vendorphonehelp"></div>
							</td>
						</tr>

						<tr>
							<td class="FieldLabel">
								<span class="Required">*</span> <label for="vendorzip">{% lang 'VendorEmail' %}:</label>
							</td>
							<td class="PanelBottom">
								<input type="text" name="vendoremail" id="vendoremail" value="{{ VendorEmail|safe }}" class="Field250" />
								<img onmouseout="HideHelp('vendoremailhelp');" onmouseover="ShowHelp('vendoremailhelp', '{% lang 'VendorEmail' %}', '{% lang 'VendorEmailHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
								<div style="display:none" id="vendoremailhelp"></div>
							</td>
						</tr>

						<tr>
							<td class="FieldLabel">
								<span class="Required">*</span> <label for="vendoraddress">{% lang 'VendorAddress' %}:</label>
							</td>
							<td>
								<input type="text" name="vendoraddress" id="vendoraddress" value="{{ VendorAddress|safe }}" class="Field250" />
								<img onmouseout="HideHelp('vendoraddresshelp');" onmouseover="ShowHelp('vendoraddresshelp', '{% lang 'CompanyAddress' %}', '{% lang 'CompanyAddressHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
								<div style="display:none" id="vendoraddresshelp"></div>
							</td>
						</tr>

						<tr>
							<td class="FieldLabel">
								<span class="Required">*</span> <label for="vendorcity">{% lang 'VendorCity' %}:</label>
							</td>
							<td>
								<input type="text" name="vendorcity" id="vendorcity" value="{{ VendorCity|safe }}" class="Field250" />
								<img onmouseout="HideHelp('vendorcityhelp');" onmouseover="ShowHelp('vendorcityhelp', '{% lang 'VendorCity' %}', '{% lang 'VendorCityHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
								<div style="display:none" id="vendorcityhelp"></div>
							</td>
						</tr>

						<tr>
							<td class="FieldLabel">
								<span class="Required">*</span> <label for="vendorcountry">{% lang 'VendorCountry' %}:</label>
							</td>
							<td>
								<select name="vendorcountry" id="vendorcountry" class="Field250 " onchange="GetStates(this, 'vendorstate', 'vendorstate1')">
									{{ CountryList|safe }}
								</select>
								<img onmouseout="HideHelp('vendorcountryhelp');" onmouseover="ShowHelp('vendorcountryhelp', '{% lang 'VendorCountry' %}', '{% lang 'VendorCountryHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
								<div style="display:none" id="vendorcountryhelp"></div>
							</td>
						</tr>

						<tr>
							<td class="FieldLabel">
								<span class="Required">*</span> <label for="vendorstate">{% lang 'VendorState' %}:</label>
							</td>
							<td class="Field">
								<select style="{{ HideStateList|safe }}" name="vendorstate" id="vendorstate" class="Field250">
									<option value="">{% lang 'ChooseState' %}</option>
									{{ StateList|safe }}
								</select>
								<input style="{{ HideStateBox|safe }}" type="text" name="vendorstate1" id="vendorstate1" class="Field250" value="{{ VendorState|safe }}" />
							</td>
						</tr>

						<tr>
							<td class="FieldLabel">
								<span class="Required">*</span> <label for="vendorzip">{% lang 'VendorZip' %}:</label>
							</td>
							<td class="PanelBottom">
								<input type="text" name="vendorzip" id="vendorzip" value="{{ VendorZip|safe }}" class="Field50" />
								<img onmouseout="HideHelp('vendorziphelp');" onmouseover="ShowHelp('vendorziphelp', '{% lang 'VendorZip' %}', '{% lang 'VendorZipHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
								<div style="display:none" id="vendorziphelp"></div>
							</td>
						</tr>

						<tr style="{{ HideLogoUpload|safe }}">
							<td class="FieldLabel">
								<span class="Required">&nbsp;</span>
								<label for="vendorlogo">{% lang 'VendorLogo' %}:</label>
							</td>
							<td>
								<input type="file" name="vendorlogo" id="vendorlogo" />
								<img onmouseout="HideHelp('vendorlogohelp');" onmouseover="ShowHelp('vendorlogohelp', '{% lang 'VendorLogo' %}', '{% lang 'VendorLogoHelp' %}')" src="images/help.gif" width="24" height="16" border="0" />
								<div style="display:none" id="vendorlogohelp"></div>
								<span style="{{ HideCurrentVendorLogo|safe }}">
									<label><input name='deletevendorlogo' id='deletevendorlogo' type='checkbox' value='1' /> {% lang 'DeleteCurrentImage' %}</label> <a href="{{ CurrentVendorLogoLink|safe }}" target="_blank">{{ CurrentVendorLogo|safe }}</a>
								</span>
							</td>
						</tr>

						<tr style="{{ HidePhotoUpload|safe }}">
							<td class="FieldLabel">
								<span class="Required">&nbsp;</span>
								<label for="vendorphoto">{% lang 'VendorPhoto' %}:</label>
							</td>
							<td>
								<input type="file" name="vendorphoto" id="vendorphoto" />
								<img onmouseout="HideHelp('vendorphotohelp');" onmouseover="ShowHelp('vendorphotohelp', '{% lang 'VendorPhoto' %}', '{% lang 'VendorPhotoHelp' %}')" src="images/help.gif" width="24" height="16" border="0" />
								<div style="display:none" id="vendorphotohelp"></div>
								<span style="{{ HideCurrentVendorPhoto|safe }}">
									<label><input name='deletevendorphoto' id='deletevendorphoto' type='checkbox' value='1' /> {% lang 'DeleteCurrentImage' %}</label> <a href="{{ CurrentVendorPhotoLink|safe }}" target="_blank">{{ CurrentVendorPhoto|safe }}</a>
								</span>
							</td>
						</tr>

						<tr>
							<td class="FieldLabel" valign="top">
								<span class="Required">&nbsp;</span> <label for="ForwardInvoiceEmails">{% lang 'ForwardInvoiceEmails' %}:</label>
							</td>
							<td class="PanelBottom">
								<label> <input type="checkbox" name="forwardvendoremails" onclick="if(this.checked) { $('.ForwardInvoiceEmailsToggle').show(); } else { $('.ForwardInvoiceEmailsToggle').hide(); }" value="1" {{ VendorForwardInvoices|safe }} /> {% lang 'YesEnableForwardInvoiceEmails' %}</label>
								<img onmouseout="HideHelp('invoiceemailshelp');" onmouseover="ShowHelp('invoiceemailshelp', '{% lang 'ForwardInvoiceEmails' %}', '{% lang 'ForwardInvoiceEmailsHelp' %}')" src="images/help.gif" width="24" height="16" border="0" />
								<div style="display:none" id="invoiceemailshelp"></div>
								<div style="margin-top: 3px; {{ HideForwardInvoiceEmails|safe }}" class="ForwardInvoiceEmailsToggle">
									<img src="images/nodejoin.gif" style="vertical-align: middle;" />
									<input type="text" name="vendororderemail" id="vendororderemail" class="Field250" value="{{ VendorOrderEmail|safe }}" /><br />
									<span class="Disabled" style='text-decoration: none; padding-left: 25px;'>{% lang 'ForwardOrderInvoicesDesc' %}</span>
								</div>
							</td>
						</tr>
						<tr style="{{ HidePermissions|safe }}" id="VendorProfitMarginFields">
							<td class="FieldLabel">
								<span class="Required">*</span> <label for="vendorprofitmargin">{% lang 'VendorProfitMargin' %}:</label>
							</td>
							<td class="PanelBottom">
								<input type="text" name="vendorprofitmargin" id="vendorprofitmargin" value="{{ VendorProfitMargin|safe }}" class="Field50" />
								<img onmouseout="HideHelp('vendorprofitmarginhelp');" onmouseover="ShowHelp('vendorprofitmarginhelp', '{% lang 'VendorProfitMargin' %}', '{% lang 'VendorProfitMarginHelp' %}')" src="images/help.gif" width="24" height="16" border="0" />
								<div style="display:none" id="vendorprofitmarginhelp"></div>
							</td>
						</tr>
					</table>
					<table width="100%" class="Panel" style="{{ HidePermissions|safe }}">
						<tr>
							<td class="Heading2" colspan="2">{% lang 'VendorPermissions' %}</td>
						</tr>
						<tr>
							<td class="FieldLabel" valigbn="top">{% lang 'CategoryPermissions' %}:</td>
							<td class="PanelBottom">
								<input type="checkbox" name="vendorlimitcats" id="vendorlimitcats" {{ AccessAllCategories|safe }} /> <label for="vendorlimitcats">{% lang 'VendorAccessAllAccess' %}</label>
								<span id="accesscatssel" style="{{ HideAccessCategories|safe }}">(<a href="#" id="selectAllCats">{% lang 'SelectAll' %}</a> / <a href="#" id="unselectAllCats">{% lang 'UnselectAll' %}</a>)</span>
								<img onmouseout="HideHelp('d4');" onmouseover="ShowHelp('d4', '{% lang 'CategoryPermissions' %}', '{% lang 'CategoryPermissionsHelp' %}')" src="images/help.gif" width="24" height="16" border="0" />
								<div style="display:none" id="d4"></div><br />
								<div style="padding-top:5px; {{ HideAccessCategories|safe }}" id="accesscategorylist">
									<img src="images/nodejoin.gif" width="20" height="20" style="float:left; margin-right: 5px"/>
									<select size="5" id="vendoraccesscats" name="vendoraccesscats[]" class="Field400 ISSelectReplacement" multiple="multiple" style="height: 140px">
									{{ AccessCategoryOptions|safe }}
									</select>
								</div>
							</td>
						</tr>
					</table>
					<table width="100%" class="Panel">
						<tr>
							<td class="Heading2" colspan="2">{% lang 'VendorBio' %}</td>
						</tr>
						<tr>
							<td colspan="2">{{ WYSIWYG|safe }}</td>
						</tr>
					</table>
				</div>
				<div id="div1" style="display: none">
					<table width="100%" class="Panel">
						<tr>
							<td class="Heading2" colspan="2">{% lang 'VendorShipping' %}</td>
						</tr>

						<tr>
							<td class="FieldLabel">
								{% lang 'VendorShipping' %}:
							</td>
							<td>
								<label style="display: block;"><input type="radio" onclick="ToggleShipping(this.value)" name="vendorshipping" value="0" {{ VendorShippingDefault|safe }} /> {% lang 'VendorShippingStoreDefault' %}</label>
								<div id="StoreShippingMethods" style="{{ HideStoreMethodsList|safe }}">
									<img src="images/nodejoin.gif" alt="" class="FloatLeft" />
									{{ StoreShippingMethods|safe }}
								</div>

								<label style="display: block;"><input type="radio" onclick="ToggleShipping(this.value)" name="vendorshipping" value="1" {{ VendorShippingCustom|safe }} /> {% lang 'VendorShippingCustom' %}</label>
								<div id="ShippingZonesToggle" style="{{ HideShippingZonesGrid|safe }}">
									<div id="ShippingNotConfigured" style="{{ HideShippingNotConfigured|safe }}">
										<img src="images/nodejoin.gif" alt="" class="FloatLeft"/>
										<div class="FloatLeft" style="width: 600px;">
											<p class="InfoTip" style="margin-top: 3px; background-position: 10px 10px">{% lang 'VendorShippingCustomIntro' %}</p>
										</div>
									</div>
								</div>
							</td>
						</tr>
					</table>

					<div id="ShippingZonesGrid" style="{{ HideShippingZonesGrid|safe }}">
						<p style="padding-bottom: 0; margin-bottom: 10px; margin-top: 10px;">
						<input type="button" name="ZoneAddButton" value="{% lang 'AddShippingZoneButton' %}" class="SmallButton" onclick="document.location.href='index.php?ToDo=addShippingZone&vendorId={{ VendorId|safe }}';" />
							<input type="button" name="ZoneDeleteButton" value="{% lang 'DeleteSelected' %}" class="SmallButton" onclick="ConfirmDeleteSelectedZones();" {{ DisableDeleteZones|safe }} />
						</p>
						{{ NoZonesMessage|safe }}
						<div class="GridContainer" style="{{ DisplayZoneGrid|safe }}">
							{{ ShippingZonesGrid|safe }}
						</div>
					</div>
				</div>
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="PanelPlain">
					<tr>
						<td>
							<input type="submit" name="SaveButton2" value="{% lang 'SaveAndExit' %}" class="FormButton" />
							<input type="submit" name="SaveAddAnotherButton2" value="{{ SaveAndAddAnother|safe }}" name="addAnother" class="FormButton" style="width:130px" />
							<input type="button" name="CancelButton2" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()" />
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</div>
</form>
<script type="text/javascript">
	function ToggleShipping(value)
	{
		if(value == 1) {
			if($('#ShippingNotConfigured').css('display') != 'none') {
				$('#ShippingZonesToggle').show();
			}
			else {
				$('#ShippingZonesGrid').show();
			}
			$('#StoreShippingMethods').hide();
		}
		else {
			$('#ShippingZonesGrid').hide();
			$('#StoreShippingMethods').show();
			$('#ShippingZonesToggle').hide();
		}
	}
	function CheckVendorForm()
	{
		if($('#frmVendor').attr('action').indexOf('deleteVendorPages') != -1) {
			return true;
		}

		if(!$('#vendorname').val()) {
			alert('{% lang 'EnterVendorName' %}');
			$('#vendorname').focus();
			return false;
		}

		if(!$('#vendorphone').val()) {
			alert('{% lang 'EnterVendorPhone' %}');
			$('#vendorphone').focus();
			return false;
		}

		if($('#vendoremail').val().indexOf('@') == -1) {
			alert('{% lang 'EnterVendorEmail' %}');
			$('#vendoremail').focus();
			return false;
		}

		if(!$('#vendoraddress').val()) {
			alert('{% lang 'EnterVendorAddress' %}');
			$('#vendoraddress').focus();
			return false;
		}

		if(!$('#vendorcity').val()) {
			alert('{% lang 'EnterVendorCity' %}');
			$('#vendorcity').focus();
			return false;
		}

		if(!$('#vendorcountry').val()) {
			alert('{% lang 'EnterVendorCountry' %}');
			$('#vendorcountry').focus();
			return false;
		}

		if($('#vendorstate').css('display') != 'none' && !$('#vendorstate').val()) {
			alert('{% lang 'EnterVendorState' %}');
			$('#vendorstate').focus();
			return false;
		}

		if(!$('#vendorzip').val()) {
			alert('{% lang 'EnterVendorZip' %}');
			$('#vendorzip').focus();
			return false;
		}

		imageExtensions = 'jpg,jpeg,jpe,gif,png';
		if($('#vendorlogo').val()) {
			ext = $('#vendorlogo').val().replace(/^.*\./, '').toLowerCase();
			if(imageExtensions.toLowerCase().replace(' ', '').indexOf(ext) == -1) {
				alert('{% lang 'ChooseValidVendorLogo' %}');
				$('#vendorlogo').select().focus();
				return false;
			}
		}

		if($('#vendorphoto').val()) {
			ext = $('#vendorphoto').val().replace(/^.*\./, '').toLowerCase();
			if(imageExtensions.toLowerCase().replace(' ', '').indexOf(ext) == -1) {
				alert('{% lang 'ChooseValidVendorPhoto' %}');
				$('#vendorphoto').select().focus();
				return false;
			}
		}

		if($('#VendorProfitMarginFields').css('display') != 'none' && (isNaN(priceFormat($('#vendorprofitmargin').val())) || priceFormat($('#vendorprofitmargin').val()) < 0)) {
			alert('{% lang 'EnterVendorProfitMargin' %}');
			$('#vendorprofitmargin').select().focus();
			return false;
		}

		if(g('wysiwyg')) {
			var content = g('wysiwyg').value;
		}
		else if(g('wysiwyg_html')) {
			var content = g('wysiwyg_html').value;
		}

		if(IsWysiwygEditorEmpty(content)) {
			alert("{% lang 'EnterVendorBio' %}");
			return false;
		}
		return true;
	}


	function ConfirmCancel()
	{
		if(confirm('{% lang 'ConfirmCancel' %}')) {
			if('{{ CurrentVendor|safe }}' != 0) {
				window.location = 'index.php';
			}
			else {
				window.location = 'index.php?ToDo=viewVendors&currentTab=1';
			}
		}

		return false;
	}

	function ConfirmDeleteSelectedZones()
	{
		if(!$('#ShippingZonesGrid .GridContainer input[type=checkbox].check:checked').length) {
			alert('{% lang 'SelectOneMoreZonesDelete' %}');
			return false;
		}
		if(confirm('{% lang 'ConfirmDeleteZones' %}')) {
			$('#frmVendor').attr('action', 'index.php?ToDo=deleteShippingZones');
			$('#frmVendor').attr('onsubmit', function() { return true});
			$('#frmVendor').submit();
		}
		else {
			return false;
		}
	}

	function ConfirmDeleteZone() {
		if(confirm('{% lang 'ConfirmDeleteZone' %}')) {
			return true;
		}

		return false;
	}

	function GetStates(selObj, dest, stateTextBox)
	{
		var country = selObj.options[selObj.selectedIndex].value;

		selDest = document.getElementById(dest);
		otherBox = document.getElementById(stateTextBox);

		if(country == "")
		{
			ResetStates(false);
			selObj.focus();
		}
		else
		{
			// Get all of the states for this country
			//dataMode = 1;
			DoCallback("w=countryStates&c="+country);
		}
	}

	function ResetStates(ShowChoose)
	{
		selDest.options.length = 0;

		if(ShowChoose)
			selDest.options[selDest.options.length] = new Option("{% lang 'ChooseState' %}", "");
	}

	function ProcessData(html)
	{
		ResetStates(true);
		states = html.split("~");
		numStates = 0;

		for(i = 0; i < states.length; i++)
		{
			vals = states[i].split("|");

			if(states[i].length > 0) {
				selDest.options[selDest.options.length] = new Option(vals[0], vals[1]);
				numStates++;
			}
		}

		// If there are no states then hide the state dropdown list
		if(numStates == 0) {
			selDest.style.display = "none";
			otherBox.style.display = "";
		}
		else {
			selDest.style.display = "";
			otherBox.style.display = "none";
		}
	}

	function ShowTab(T)
	{
		i = 0;
		while (document.getElementById("tab" + i) != null) {
			document.getElementById("div" + i).style.display = "none";
			document.getElementById("tab" + i).className = "";
			i++;
		}

		document.getElementById("div" + T).style.display = "";
		document.getElementById("tab" + T).className = "active";
		document.getElementById("currentTab").value = T;
	}

	$(document).ready(function() {
		if($('#currentTab').val()) {
			ShowTab($('#currentTab').val());
		}

		// Show or hide the access categories list as required
		$('#vendorlimitcats').click(function() {
			if((this).checked) {
				$('#accesscategorylist').hide();
				$('#accesscatssel').hide();
			}
			else {
				$('#accesscategorylist').show();
				$('#accesscatssel').show();
			}
		});

		// Select all access categories
		$('#selectAllCats').click(function() {
			if(g('vendoraccesscats_old')) {
				if(g('vendoraccesscats_old').disabled != true) {
					$('#vendoraccesscats input').attr('checked', false);
					$('#vendoraccesscats input').trigger('click');
				}
			}
			else {
				$('#vendoraccesscats option').attr('selected', true);
			}
			return false;
		});

		$('#unselectAllCats').click(function() {
			if(g('vendoraccesscats_old')) {
				if(g('vendoraccesscats_old').disabled != true) {
					$('#vendoraccesscats input').attr('checked', true);
					$('#vendoraccesscats input').trigger('click');
				}
			}
			else {
				$('#vendoraccesscats option').attr('selected', false);
			}
			return false;
		});
	});
</script>