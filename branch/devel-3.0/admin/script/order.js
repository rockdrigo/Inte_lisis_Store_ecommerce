var Order = {
	ShipItems: function(orderId, addressId)
	{
		var addressAppend = '';
		if(typeof(addressId) != 'undefined') {
			addressAppend = '&addressId=' + addressId;
		}
		$.iModal({
			type: 'ajax',
			url: 'remote.php?remoteSection=orders&w=createShipment&orderId='+orderId + addressAppend
		});
		return false;
	},

	SaveShipment: function()
	{
		var hasSelection = false;
		var hasError = false;
		$('.ShipmentTable .QtyEntry').each(function() {
			if(!$(this).val() || $(this).val() == 0) {
				return true;
			}
			if($(this).is('input')) {
				var maxQty = $(this).attr('class').match(/MaxValue(\d+)/);
				if(isNaN($(this).val()) || $(this).val() > parseInt(maxQty[1])) {
					alert(lang.ChooseOneMoreItemsToShip);
					$(this).select().focus();
					hasError = true;
					return false;
				}
			}
			hasSelection = true;
		});

		if(hasError) {
			return false;
		}

		if(!hasSelection) {
			alert(lang.ChooseOneMoreItemsToShip);
			return false;
		}

		$('#ModalButtonRow .CloseButton').hide();
		$('#ModalButtonRow .SubmitButton').attr('disabled', true);
		$('#ModalButtonRow .SubmitButton').data('oldValue', $('.ModalButtonRow .SubmitButton').val());
		$('#ModalButtonRow .SubmitButton').val('Creating Shipment...');
		$('#ModalButtonRow .LoadingIndicator').show();

		$.ajax({
			url: 'remote.php?remoteSection=orders&w=saveNewShipment',
			type: 'post',
			dataType: 'xml',
			data: $('#ShipmentDetails').serialize(),
			success: function(data) {
				if($('status', data).text() == 0) {
					alert($('message', data).text());
					$('#ModalButtonRow .CloseButton').show();
					$('#ModalButtonRow .SubmitButton').attr('disabled', false);
					$('#ModalButtonRow .SubmitButton').val($('.ModalButtonRow .SubmitButton').val());
					$('#ModalButtonRow .LoadingIndicator').hide();
					return false;
				}
				else {
					$.modal.close();
					if($('stillShippable', data).text() != 1) {
						$('#ShipItemsLink'+$('orderId', data).text()).remove();
					}
					if($('orderStatus', data).text()) {
						statusId = $('orderStatus', data).text();
						$('#order_status_column_'+$('orderId', data).text())
							.attr('class', '')
							.addClass('OrderStatus')
							.addClass('OrderStatus'+statusId)
						;
						$('#status_'+$('orderId', data).text()).val($('orderStatus', data).text());
					}
					display_success('OrdersStatus', $('message', data).text());
				}
			},
			error: function(data) {
				alert(lang.ProblemCreatingShipment);
				$('#ModalButtonRow .CloseButton').show();
				$('#ModalButtonRow .SubmitButton').attr('disabled', false);
				$('#ModalButtonRow .SubmitButton').val($('.ModalButtonRow .SubmitButton').val());
				$('#ModalButtonRow .LoadingIndicator').hide();
			}
		});
		return false;
	},

	PrintShipmentPackingSlip: function(shipmentId, orderId)
	{
		var l = screen.availWidth / 2 - 450;
		var t = screen.availHeight / 2 - 320;
		var win = window.open('index.php?ToDo=printShipmentPackingSlips&orderId='+orderId+'&shipmentId='+shipmentId, 'packingSlip', 'width=900,height=650,left='+l+',top='+t+',scrollbars=1');
		return false;
	},

	PrintOrderPackingSlip: function(orderId)
	{
		var l = screen.availWidth / 2 - 450;
		var t = screen.availHeight / 2 - 320;
		var win = window.open('index.php?ToDo=printShipmentPackingSlips&orderId='+orderId, 'packingSlip', 'width=900,height=650,left='+l+',top='+t+',scrollbars=1');
		return false;
	},

	PrintSelectedPackingSlips: function()
	{
		var fp = document.getElementById("frmOrders1").elements;
		var c = 0;

		for(i = 0; i < fp.length; i++) {
			if(fp[i].type == "checkbox" && fp[i].checked)
				c++;
		}

		if(c > 0) {
				document.getElementById("frmOrders1").action = "index.php?ToDo=printOrderPackingSlips";
				document.getElementById("frmOrders1").target = "_blank";
				document.getElementById("frmOrders1").submit();
		}
		else {
			alert(print_orders_choose_message);
		}
	},

	HandleAction: function(orderId, action)
	{
		if(!action) {
			return false;
		}
		$('#order_action_'+orderId).val('');
		switch(action) {
			case 'editOrder':
				window.location = 'index.php?ToDo=editOrder&orderId='+orderId;
				break;
			case 'printInvoice':
				PrintInvoice(orderId);
				break;
			case 'printPackingSlip':
				Order.PrintOrderPackingSlip(orderId);
				break;
			case 'orderNotes':
				Order.ViewNotes(orderId)
				break;
			case 'orderCustomFields':
				Order.ViewCustomFields(orderId)
				break;
			case 'shipItems':
				Order.ShipItems(orderId);
				break;
			case 'viewShipments':
				Order.viewShipments(orderId);
				break;
			case 'shipItemsMultipleAddresses':
				alert(lang.ShipOrderMultipleAddressInstructions);
				// Expand the order
				if($('#trQ'+orderId).css('display') == 'none') {
					QuickView(orderId);
				}
				break;
			case 'delayedCapture':
				Order.DelayedCapture(orderId);
				break;
			case 'refundOrder':
				Order.RefundOrder(orderId);
				break;
			case 'voidTransaction':
				Order.VoidTransaction(orderId);
				break;
		}
		return false;
	},

	viewShipments: function(orderId)
	{
		$.iModal({
			type: 'ajax',
			url: 'index.php?ToDo=viewOrderShipments&orderId='+orderId,
			width: 920
		});
	},

	ViewNotes: function(orderId)
	{
		$.iModal({
			type: 'ajax',
			url: 'remote.php?remoteSection=orders&w=viewOrderNotes&orderId='+orderId,
			width: 600
		});
	},

	ViewCustomFields: function(orderId)
	{
		$.iModal({
			type: 'ajax',
			url: 'remote.php?remoteSection=orders&w=viewCustomFields&orderId='+orderId,
			width: 600
		});
	},

	SaveNotes: function(msgStatusId)
	{
		if (msgStatusId == '' || msgStatusId == undefined) {
			msgStatusId = 'OrdersStatus';
		}

		$('.ModalButtonRow .CloseButton').hide();
		$('.ModalButtonRow .LoadingIndicator').show();
		$('.ModalButtonRow .Submit')
			.data('oldValue', $('.ModalButtonRow .Submit').val())
			.attr('disabled', true)
			.val(lang.SavingNotes)
		;
		$.ajax({
			type: 'post',
			url: 'remote.php?remoteSection=orders&w=saveOrderNotes',
			data: $('#notesForm').serialize(),
			dataType: 'xml',
			success: function(xml)
			{
				$.modal.close();
				if($('message', xml).text()) {
					display_success(msgStatusId, $('message', xml).text());
				}
			},
			error: function()
			{
				$('.ModalButtonRow .CloseButton').show();
				$('.ModalButtonRow .LoadingIndicator').hide();
				$('.ModalButtonRow .Submit')
					.attr('disabled', false)
					.val($('.ModalButtonRow .Submit').val())
				;
			}
		})
	},


	LoadOrderProductFieldData: function(orderId)
	{
		$.iModal({
			type: 'ajax',
			url: 'remote.php?remoteSection=orders&w=loadorderproductfieldsdata&orderId='+orderId
		});
		return false;
	},

	DelayedCapture: function(orderId)
	{
		if(!confirm(lang.ConfirmDelayCapture)) {
			return false;
		}

		$.ajax({
			type: 'post',
			url: 'remote.php',
			data: 'remoteSection=orders&w=delayedcapture&orderid='+orderId,
			dataType: 'xml',
			success: function(xml)
			{
					window.location= "index.php?ToDo=viewOrders";
			}
		});
		return true;
	},

	ValidateRefundForm: function()
	{

		var refundType = $("input[name='refundType']:checked");
		var refundAmt =  $("input[name='refundAmt']");

		if(!refundType.val()) {
			alert(lang.SelectRefundType);
			$("input[name='refundType']").focus();
			return false;
		}
		if(refundType.val() == 'partial') {
			if(refundAmt.val()=='') {
				alert(lang.EnterRefundAmount);
				refundAmt.focus();
				return false;
			}
			if(isNaN(refundAmt.val()) || refundAmt.val() <= 0) {
				alert(lang.InvalidRefundAmountFormat);
				refundAmt.focus();
				return false;
			}
		}
		return true;
	},

	RefundOrder: function(orderId)
	{

		$.iModal({
			type: 'ajax',
			url: 'remote.php?remoteSection=orders&w=loadrefundform&orderid='+orderId
		});
		return false;
	},

	VoidTransaction: function(orderId)
	{
		if(!confirm(lang.ConfirmVoid)) {
			return false;
		}

		$.ajax({
			type: 'post',
			url: 'remote.php',
			data: 'remoteSection=orders&w=voidtransaction&orderid='+orderId,
			dataType: 'xml',
			success: function(xml)
			{
					window.location= "index.php?ToDo=viewOrders";
			}
		});
		return true;
	},

	showOrderDeletedGeneralNotice: function () {
		alert(lang.OrderDeletedGeneralNotice);
	}
};

// Functions to deal with client-side AJAX requests etc on the orders page

var ajax_img = null;
var status_col = null;
var status_box = null;
var action = "";
var td = null;
var ret = "";
var order_status_before_change = 0;
var status_id = 0;
var track_order_id = 0;

function ProcessData(html)
{
	ret = html;

	if(action == "order_quick") {
		td.innerHTML = ret;
	}
	else if(action == "order_status") {
		order_status_update_result(ret);
	}
}

function HandleOrderAction()
{
	var sel = $('#OrderActionSelect').val();

	if (sel == '') {
		return;
	} else {
		sel = sel.toLowerCase();
	}

	switch (sel) {
		case 'undelete':
			confirmUndeleteSelected();
			break;
		case 'purge':
			confirmPurgeSelected();
			break;
		case 'delete':
			ConfirmDeleteSelected();
			break;
		case 'printinvoice':
			PrintSelectedInvoices();
			break;
		case 'printslip':
			Order.PrintSelectedPackingSlips();
			break;
		case 'export':
			ExportSelected();

			/*
			$.iModal({
				data: $('#exportBox').html(),
				width: 320
			});
			*/

			break;
		default:
			var matches, regex = /^updatestatus([0-9]+)$/;
			if (matches = sel.match(regex)) {
				BulkUpdateStatus(matches[1]);
			} else {
				alert(choose_action_option);
			}
			break;
	}
}

function BulkUpdateStatus(StatusID)
{
	var fp = document.getElementById("frmOrders1").elements;
	var orders = [];

	for (i = 0; i < fp.length; i++) {
		if (fp[i].type == "checkbox" && fp[i].checked && !isNaN(fp[i].value))
			orders[orders.length] = fp[i].value;
	}

	if (orders.length > 0) {
		tb_show('', 'index.php?ToDo=updateMultiOrderStatus&orders=' + orders.join(',') + '&statusId=' + StatusID + '&TB_iframe=true&height=150&width=400&modal=true&random='+new Date().getTime(), '');
	} else {
		alert(update_order_status_choose_message);
	}
}

function PrintInvoice(OrderId)
{
	var l = screen.availWidth / 2 - 450;
	var t = screen.availHeight / 2 - 320;
	var win = window.open('index.php?ToDo=printOrderInvoice&orderId='+OrderId, 'packingSlip', 'width=900,height=650,left='+l+',top='+t+',scrollbars=1');
}

function PrintPackingSlip(OrderId)
{
	var l = screen.availWidth / 2 - 450;
	var t = screen.availHeight / 2 - 320;
	var win = window.open('index.php?ToDo=printOrderPackingSlip&orderId='+OrderId, 'packingSlip', 'width=900,height=650,left='+l+',top='+t+',scrollbars=1');
}

function ToggleDeleteBoxes(Status)
{
	var fp = document.getElementById("frmOrders1").elements;

	for(i = 0; i < fp.length; i++)
		fp[i].checked = Status;
}

function countSelectedOrders ()
{
	return $('#frmOrders1 input[type=checkbox][name="orders[]"]:checked').size();
}

function confirmUndeleteSelected ()
{
	if (countSelectedOrders() < 1) {
		alert(lang.ChooseOrderUndelete);
		return false;
	}

	if (!confirm(lang.ConfirmUndeleteOrders)) {
		return false;
	}

	// @hack frmOrders1 is fixed to the deleteOrders action so change that temporarily, submit and then restore
	//       this is restored to handle back button navigation on browsers that don't refresh the page completely
	var form = $('#frmOrders1');
	var action = form.attr('action');

	form.attr('action', 'index.php?ToDo=undeleteOrders')
		.submit()
		.attr('action', action);
}

function confirmPurgeSelected ()
{
	if (countSelectedOrders() < 1) {
		alert(lang.ChooseOrderPurge);
		return false;
	}

	if (!confirm(lang.ConfirmDeleteOrders)) {
		return false;
	}

	// @hack frmOrders1 is fixed to the deleteOrders action so change that temporarily, submit and then restore
	//       this is restored to handle back button navigation on browsers that don't refresh the page completely
	var form = $('#frmOrders1');
	var action = form.attr('action');

	form.attr('action', 'index.php?ToDo=purgeOrders')
		.submit()
		.attr('action', action);
}

function ConfirmDeleteSelected ()
{
	var fp = document.getElementById("frmOrders1").elements;
	var c = 0;

	for(i = 0; i < fp.length; i++) {
		if(fp[i].type == "checkbox" && fp[i].checked)
			c++;
	}

	if(c > 0) {
		switch (String(config.DeletedOrdersAction).toLowerCase()) {
			case 'purge':
				var confirmationMessage = lang.ConfirmDeleteOrders;
				break;

			default:
				var confirmationMessage = lang.ConfirmRestorableDeleteOrders;
		}

		if(confirm(confirmationMessage))
			document.getElementById("frmOrders1").submit();
	}
	else
	{
		alert(delete_orders_choose_message);
	}
}

function PrintSelectedInvoices()
{
	var fp = document.getElementById("frmOrders1").elements;
	var c = 0;

	for(i = 0; i < fp.length; i++) {
		if(fp[i].type == "checkbox" && fp[i].checked)
			c++;
	}

	if(c > 0) {
			document.getElementById("frmOrders1").action = "index.php?ToDo=printMultiOrderInvoices";
			document.getElementById("frmOrders1").target = "_blank";
			document.getElementById("frmOrders1").submit();
	}
	else {
		alert(print_orders_choose_message);
	}
}

function ExportSelected() {
	document.getElementById("frmOrders1").action = ExportAction;
	document.getElementById("frmOrders1").submit();
}

function ConfirmShipSelected()
{
	var fp = document.getElementById("frmOrders1").elements;
	var c = 0;

	for(i = 0; i < fp.length; i++) {
		if(fp[i].type == "checkbox" && fp[i].checked)
			c++;
	}

	if(c > 0) {
		document.getElementById("frmOrders1").action = "index.php?ToDo=markOrdersShipped";
		document.getElementById("frmOrders1").submit();
	}
	else
	{
		alert("%%LNG_ChooseOrderToShip%%");
	}
}

var order_id = '';
var status_message = '';

function update_order_status(orderid, statusid, statustext) {

	$('#ajax_status_'+orderid).attr('src', 'images/ajax-loader.gif');
	status_message = statustext.toLowerCase();
	$.ajax({
		url: 'remote.php?w=updateOrderStatus&o='+orderid+'&s='+statusid+'&t='+tok,
		success: function(response) {
			$('#ajax_status_'+orderid).attr('src', 'images/ajax-blank.gif');
			if(response == 1) {
				$('#order_status_column_'+orderid).get(0).className = $('#order_status_column_'+orderid).get(0).className.replace(/OrderStatus[0-9*]/, '');
				$('#order_status_column_'+orderid)
					.addClass('OrderStatus')
					.addClass('OrderStatus'+statusid)
				;
				display_success('OrdersStatus', order_status_update_success_message.replace("%d", orderid).replace("%s", status_message));
			}
			else {
				alert(failed_order_status_update_message);
			}
		},
		error: function()
		{
			$('#ajax_status_'+orderid).attr('src', 'images/ajax-blank.gif');
			alert(failed_order_status_update_message);
		}
	});
}

function do_custom_search(search_id) {
	if(search_id > 0) {
		document.location.href = "index.php?ToDo=customOrderSearch&searchId="+search_id;
	}
	else {
		document.location.href = "index.php?ToDo=viewOrders";
	}
}

function confirm_delete_custom_search(search_id) {
	if(confirm(delete_custom_search_message))
		document.location.href = "index.php?ToDo=deleteCustomOrderSearch&searchId="+search_id;
}

function CheckEventDate() {

	var result = true;

	if(typeof(eventDateData) == 'undefined')
		return true;

	if ($('#EventDateDay').val() == -1 || $('#EventDateMonth').val() == -1 || $('#EventDateYear').val() == -1) {
		alert(eventDateData['invalidMessage']);
		return false;
	}

	if (eventDateData['type'] == 1) {

		if (new Date($('#EventDateYear').val()+'/'+$('#EventDateMonth').val()+'/'+$('#EventDateDay').val()) > new Date(eventDateData['compDateEnd'])
		 || new Date($('#EventDateYear').val()+'/'+$('#EventDateMonth').val()+'/'+$('#EventDateDay').val()) < new Date(eventDateData['compDate'])
		) {
			result = false;
		}

	} else if (eventDateData['type'] == 2) {
		if (new Date($('#EventDateYear').val()+'/'+$('#EventDateMonth').val()+'/'+$('#EventDateDay').val()) < new Date(eventDateData['compDate'])) {
			result = false;
		}

	} else if (eventDateData['type'] == 3) {
		if (new Date($('#EventDateYear').val()+'/'+$('#EventDateMonth').val()+'/'+$('#EventDateDay').val()) > new Date(eventDateData['compDate'])) {
			result = false;
		}
	} else {
		result = false;
	}

	if (!result) {
		alert(eventDateData['errorMessage']);
	}
	return result;
}
