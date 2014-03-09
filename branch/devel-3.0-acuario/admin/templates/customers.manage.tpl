{% import "macros/forms.tpl" as forms %}
{% import "macros/util.tpl" as util %}

	<div class="BodyContainer">
	<table id="Table13" cellSpacing="0" cellPadding="0" width="100%">
		<tr>
			<td class="Heading1">
				{% lang 'View' %}: <a href="#" style="color:#005FA3" id="ViewsMenuButton" class="PopDownMenu">{{ ViewName|safe }} <img width="8" height="5" src="images/arrow_blue.gif" border="0" /></a>
			</td>
		</tr>
		<tr>
		<td class="Intro">
			<p>{{ CustomerIntro|safe }}</p>
			<div id="CustomerStatus">
				{{ Message|safe }}
			</div>
			<div style="background-color: rgb(244, 244, 244); display:none" class="MessageBox MessageBoxSuccess" id="CustomerGroupMessage"></div>
			<table id="IntroTable" cellspacing="0" cellpadding="0" width="100%">
			<tr>
			<td class="Intro" valign="top">
				<input type="button" name="IndexDeleteButton" value="{% lang 'AddCustomer' %}..." id="IndexAddButton" class="SmallButton" onclick="document.location.href='index.php?ToDo=addCustomer'" {{ DisableAdd|safe }} />
				<input type="button" name="IndexDeleteButton" value="{% lang 'DeleteSelected' %}" id="IndexDeleteButton" class="SmallButton" onclick="ConfirmDeleteSelected()" {{ DisableDelete|safe }} />

				{% if DisableExport %}
					<input type="button" value="{% lang 'ExportCustomers' %}" disabled="disabled" class="SmallButton PopDownMenu" style="width:160px;" />
				{% else %}
					<input type="button" value="{% lang 'ExportCustomers' %}" id="CustomerExportMenuButton" class="SmallButton PopDownMenu" style="width:160px;" />
				{% endif %}
			</td>
			<td class="SmallSearch" align="right">
				<form action="index.php" method="get" onsubmit="return ValidateForm(CheckSearchForm)">
					{{ forms.hiddenInputs(['ToDo':'viewCustomers'] + searchURL, ['searchQuery']) }}
					<table id="Table16" style="display:{{ DisplaySearch|safe }}">
						<tr>
							<td nowrap>
								<input name="searchQuery" id="searchQuery" type="text" value="{{ EscapedQuery|safe }}" id="SearchQuery" class="Button" size="20" />&nbsp;

								<script language="javascript" type="text/javascript">//<![CDATA[
									function do_custom_search(search_id) {
										if(search_id > 0) {
											document.location.href = "index.php?ToDo=customCustomerSearch&searchId="+search_id;
										}
										else {
											document.location.href = "index.php?ToDo=viewProducts";
										}
									}

									function confirm_delete_custom_search(search_id) {
										if(confirm('{% lang 'ConfirmDeleteCustomSearch' %}'))
											document.location.href = "index.php?ToDo=deleteCustomCustomerSearch&searchId="+search_id;
									}
								//]]></script>

								<input type="image" name="SearchButton" style="padding-left: 10px; vertical-align: top;" id="SearchButton" src="images/searchicon.gif" border="0" />
							</td>
						</tr>
						<tr>
							<td nowrap="nowrap">
								<a href="index.php?ToDo=searchCustomers">{% lang 'AdvancedSearch' %}</a>
								<span style="display:{{ HideClearResults|safe }}">| <a id="SearchClearButton" href="index.php?ToDo=viewCustomers">{% lang 'ClearResults' %}</a></span>
							</td>
						</tr>
						<tr>
							<td></td>
						</tr>
					</table>
				</form>
			</td>
			</tr>
			</table>
		</td>
		</tr>
		<tr>
		<td style="display: {{ DisplayGrid|safe }}">
			<form name="frmCustomers" id="frmCustomers" method="post" action="index.php?ToDo=deleteCustomers">
				<div class="GridContainer">
					{{ CustomerDataGrid|safe }}
				</div>
			</form>
		</td></tr>
	</table>

	{% if not DisableExport %}
		{{ util.dropDownMenu([
			'id': 'CustomerExportMenu',
			'groups': customerExportMenu,
		]) }}
	{% endif %}

	<div id="ViewsMenu" class="DropShadow DropDownMenu" style="display: none; width:200px">
		<ul>
			{{ CustomSearchOptions|safe }}
		</ul>
		<hr />
		<ul>
			<li><a href="index.php?ToDo=createCustomerView" style="background-image:url('images/view_add.gif'); background-repeat:no-repeat; background-position:5px 5px; padding-left:28px">{% lang 'CreateANewView' %}</a></li>
			<li style="display:{{ HideDeleteViewLink|safe }}"><a onclick="$('#ViewsMenu').hide(); confirm_delete_custom_search('{{ CustomSearchId|safe }}')" href="javascript:void(0)" style="background-image:url('images/view_del.gif'); background-repeat:no-repeat; background-position:5px 5px; padding-left:28px">{% lang 'DeleteThisView' %}</a></li>
		</ul>
	</div>

	</div>

	<script type="text/javascript" src="script/order.js?{{ JSCacheToken }}"></script>
	<script type="text/javascript">

		var td = null;
		var ret = "";

		function CheckSearchForm() {
			var query = document.getElementById("searchQuery");

			if(query.value == "")
			{
				alert("{% lang 'EnterSearchTerm' %}");
				query.focus();
				return false;
			}

			return true;
		}

		function ConfirmDeleteSelected() {
			var fp = document.getElementById("frmCustomers").elements;
			var c = 0;

			for(i = 0; i < fp.length; i++)
			{
				if(fp[i].type == "checkbox" && fp[i].checked)
					c++;
			}

			if(c > 0)
			{
				if(confirm("{% lang 'ConfirmDeleteCustomers' %}"))
					document.getElementById("frmCustomers").submit();
			}
			else
			{
				alert("{% lang 'ChooseCustomer' %}");
			}
		}

		function ToggleDeleteBoxes(Status) {
			var fp = document.getElementById("frmCustomers").elements;

			for(i = 0; i < fp.length; i++)
				fp[i].checked = Status;
		}

		function confirm_delete_custom_search(search_id) {
			if(confirm("{% lang 'ConfirmDeleteCustomSearch' %}"))
				document.location.href = "index.php?ToDo=deleteCustomCustomerSearch&searchId="+search_id;
		}

		function updateStoreCredit(id) {
			var credit = $('#storecredit_'+id).val();
			var button = $('#save_storecredit_'+id);
			if(credit != "" && isNaN(priceFormat(credit))) {
				alert('{% lang 'SearchEnterValidStoreCredit' %}');
				$('#storecredit_'+id).select();
				$('#storecredit_'+id).focus();
				return false;
			}
			button.val('...');
			button.attr('disabled', true);
			button.blur();
			$.ajax({
				url: 'remote.php?w=updateStoreCredit&customerId='+id+'&credit='+credit,
				success: function(response) {
					button.val('{% lang 'Save' %}');
					button.attr('disabled', false);
				},
				error: function() {
					button.val('{% lang 'Save' %}');
					button.attr('disabled', false);
				}
			});
		}

		function updateCustomerGroup(customerId, groupId, customerName, groupName) {
			$.ajax({
				url: 'remote.php?w=updateCustomerGroup&customerId='+customerId+'&groupId='+groupId,
				success: function(response) {
					if(response == "1") {
						if(groupId > 0) {
							$('#CustomerGroupMessage').text(customerName + ' is now a member of the ' + groupName + ' group.');
						}
						else {
							$('#CustomerGroupMessage').text(customerName + ' is no longer in a customer group.');
						}

						$('#CustomerGroupMessage').show('slow');
						window.setTimeout("$('#CustomerGroupMessage').hide('slow');", 5000);
					}
					else {
						alert('{% lang 'ErrorUpdatingCustomerGroup' %}');
						document.location.reload();
					}
				},
				error: function() {
					alert('{% lang 'ErrorUpdatingCustomerGroup' %}');
				}
			});
		}

		function viewOrderNotes(orderId)
		{
			if (isNaN(orderId)) {
				return false;
			}

			$.iModal({
				type: 'ajax',
				url: 'remote.php?remoteSection=customers&w=viewOrderNotes&orderId='+orderId,
				width: 600
			});
		}

	</script>
<script type="text/javascript" src="script/customers.js?{{ JSCacheToken }}"></script>
<script language="javascript" type="text/javascript">//<![CDATA[
	if (typeof lang == 'undefined') {
		lang = {};
	}
	lang.SavingNotes = "{% lang 'SavingNotes' %}";
//]]></script>

{% if not DisableExport %}
	{% include 'emailintegration.export.javascript.tpl' %}
{% endif %}
