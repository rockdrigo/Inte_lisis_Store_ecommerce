{% import "macros/forms.tpl" as forms %}
{% import "macros/util.tpl" as util %}
<link rel="stylesheet" type="text/css" href="Styles/orders.css" media="all" />

	<div class="BodyContainer">

	<table id="Table13" cellSpacing="0" cellPadding="0" width="100%">
		<tr>
			<td class="Heading1">
				{% lang 'View' %}: <a href="#" style="color:#005FA3" id="ViewsMenuButton" class="PopDownMenu">{{ ViewName|safe }} <img width="8" height="5" src="images/arrow_blue.gif" border="0" /></a>
			</td>
		</tr>
		<tr>
		<td class="Intro">
			<p>{{ OrderIntro|safe }}</p>
			<div id="OrdersStatus">
				{{ Message|safe }}
			</div>
			<table id="IntroTable" cellspacing="0" cellpadding="0" width="100%">
			<tr>
			<td class="Intro" valign="top">
				{{ AddOrderButton|safe }}
				&nbsp;
				{% if disableOrderExports %}
					<input type="button" value="{% lang 'ExportTheseOrders' %}" disabled="disabled" class="SmallButton PopDownMenu" style="width:140px;" /><br />
				{% else %}
					<input type="button" value="{% lang 'ExportTheseOrders' %}" id="OrderExportMenuButton" class="SmallButton PopDownMenu" style="width:140px;" /><br />
				{% endif %}
				<br />
				<div style="display: {{ DisplayGrid|safe }}">
					<select id="OrderActionSelect" name="OrderActionSelect" class="Field200">
						{{ OrderActionOptions|safe }}
					</select>
					<input type="button" id="OrderActionButton" name="OrderActionButton" value="{% lang 'OrderActionButton' %}" class="FormButton" style="width:40px;" onclick="HandleOrderAction()" />
				</div>
			</td>
			<td class="SmallSearch" align="right">
				<form name="frmOrders" id="frmOrders" action="index.php" method="get">
					{{ forms.hiddenInputs(['ToDo':'viewOrders'] + searchURL, ['searchQuery']) }}
					<table id="Table16" style="display:{{ DisplaySearch|safe }}">
						<tr>
							<td class="text" nowrap align="right">
								<input name="searchQuery" id="searchQuery" type="text" value="{{ QueryEscaped|safe }}" id="SearchQuery" class="SearchBox" style="width:150px" />&nbsp;
								<input type="image" name="SearchButton" id="SearchButton" src="images/searchicon.gif" border="0"  style="padding-left: 10px; vertical-align: top;" />
							</td>
						</tr>
						<tr>
							<td nowrap>
								<a href="index.php?ToDo=searchOrders">{% lang 'AdvancedSearch' %}</a>
								<span style="display:{{ HideClearResults|safe }}">| <a id="SearchClearButton" href="index.php?ToDo=viewOrders">{% lang 'ClearResults' %}</a></span>
							</td>
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
			<form name="frmOrders1" id="frmOrders1" method="post" action="index.php?ToDo=deleteOrders">
				<div class="GridContainer" id="GridContainer">
					{{ OrderDataGrid|safe }}
				</div>
			</form>
		</td></tr>
	</table>
	</div>
		<div id="ViewsMenu" class="DropDownMenu DropShadow" style="display: none; width:200px">
				<ul>
					{{ CustomSearchOptions|safe }}
				</ul>
				<hr />
				<ul>
					<li><a href="index.php?ToDo=createOrderView" style="background-image:url('images/view_add.gif'); background-repeat:no-repeat; background-position:5px 5px; padding-left:28px">{% lang 'CreateANewView' %}</a></li>
					<li style="display:{{ HideDeleteViewLink|safe }}"><a onclick="$('#ViewsMenu').hide(); confirm_delete_custom_search('{{ CustomSearchId|safe }}')" href="javascript:void(0)" style="background-image:url('images/view_del.gif'); background-repeat:no-repeat; background-position:5px 5px; padding-left:28px">{% lang 'DeleteThisView' %}</a></li>
				</ul>
			</div>
		</div>
		</div>
		</div>
	</div>

	{% if not disableOrderExports %}
		{{ util.dropDownMenu([
			'id': 'OrderExportMenu',
			'groups': orderExportMenu,
		]) }}
	{% endif %}

	<script language="javascript" type="text/javascript">//<![CDATA[

		config.DeletedOrdersAction = "{{ ISC_CFG.DeletedOrdersAction|js }}";

		var tok = "{{ AuthToken|safe }}";

		var delete_orders_choose_message = "{% lang 'ChooseOrder' %}";
		var print_orders_choose_message = "{% lang 'ChooseOrderInvoice' %}";
		var confirm_delete_orders_message = "{% lang 'ConfirmDeleteOrders' %}";
		var order_status_update_success_message = "{% lang 'OrderStatusUpdated' %}";
		var failed_order_status_update_message = "{% lang 'OrderStatusUpdateFailed' %}";
		var confirm_update_order_status_message = "{% lang 'ConfirmUpdateOrderStatus' %}";
		var trackingno_update_success_message = "{% lang 'TrackingNoUpdated' %}";
		var trackingno_update_failed_message = "{% lang 'TrackingNoUpdateFailed' %}";
		var delete_custom_search_message = "{% lang 'ConfirmDeleteCustomSearch' %}";
		var update_order_status_choose_message = "{% lang 'ChooseOrderUpdateStatus' %}";
		var choose_action_option = "{% lang 'ChooseActionFirst' %}";

		{{ util.jslang([
			'ChooseOneMoreItemsToShip',
			'ProblemCreatingShipment',
			'SavingNotes',
			'ConfirmDelayCapture',
			'ConfirmRefund',
			'ConfirmVoid',
			'SelectRefundType',
			'EnterRefundAmount',
			'InvalidRefundAmountFormat',
			'ConfirmSendTrackingNumber',
			'TrackingLinkEmailed',
			'ShipOrderMultipleAddressInstructions',
			'ConfirmDeleteOrders',
			'ConfirmRestorableDeleteOrders',
			'ChooseOrderUndelete',
			'ChooseOrderPurge',
			'ConfirmUndeleteOrders',
			'OrderDeletedGeneralNotice',
		]) }}

		$(document).ready(function() {
			{{ SelectOrder|safe }}
		});

		{% if disableOrderExports %}
		var ExportAction = "{{ ExportAction|safe }}";
		{% endif %}
	//]]></script>
	<script type="text/javascript" src="script/order.js?{{ JSCacheToken }}"></script>

{% if not disableOrderExports %}
	{% include 'emailintegration.export.javascript.tpl' %}
{% endif %}
