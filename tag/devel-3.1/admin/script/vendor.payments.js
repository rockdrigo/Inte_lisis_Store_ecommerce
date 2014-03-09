var VendorPayments = {
	Export: function()
	{
		$.iModal({
			data: $('#exportBox').html(),
			width: 320
		});
	},

	DeleteSelected: function()
	{
		if(!$('.GridContainer input[type=checkbox]:checked').length) {
			alert(lang.SelectOneMoreVendorPaymentsDelete);
			return false;
		}
		if(confirm(lang.ConfirmDeleteVendorPayments)) {
			$('#paymentsForm').submit();
			return true;
		}
		else {
			return false;
		}
	},

	Expand: function(paymentId)
	{
		if(!$('#trQ'+paymentId).hasClass('Expanded')) {
			$('#expand'+paymentId).attr('src', $('#expand'+paymentId).attr('src').replace('plus.gif', 'minus.gif'));
			$('#trQ'+paymentId).show().addClass('Expanded');
			$('#tr'+paymentId).addClass('QuickViewExpanded');
		}
		else {
			$('#expand'+paymentId).attr('src', $('#expand'+paymentId).attr('src').replace('minus.gif', 'plus.gif'));
			$('#trQ'+paymentId).hide().removeClass('Expanded');
			$('#tr'+paymentId).removeClass('QuickViewExpanded');
		}
	}
};