var Customers = {
	ViewNotes: function(customerId)
	{
		$.iModal({
			type: 'ajax',
			url: 'remote.php?remoteSection=customers&w=viewCustomerNotes&customerId='+customerId,
			width: 600
		});
	},

	SaveNotes: function()
	{
		$('.ModalButtonRow .CloseButton').hide();
		$('.ModalButtonRow .LoadingIndicator').show();
		$('.ModalButtonRow .Submit')
			.data('oldValue', $('.ModalButtonRow .Submit').val())
			.attr('disabled', true)
			.val(lang.SavingNotes)
		;
		$.ajax({
			type: 'post',
			url: 'remote.php?remoteSection=customers&w=saveCustomerNotes',
			data: $('#notesForm').serialize(),
			dataType: 'xml',
			success: function(xml)
			{
				$.modal.close();
				if($('message', xml).text()) {
					display_success('CustomerStatus', $('message', xml).text());
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
	}
};
