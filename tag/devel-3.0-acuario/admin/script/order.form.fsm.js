;(function($){

var fsm = new Interspire_FSM(); // local reference
orderFormFsm = fsm; // global reference

fsm.state('Init')
	.initial()
	.enter(function(ev, state)	{
		fsm.previousState = null;
		state.machine.transition('InitForm')
	})
	.transition('InitForm', 'CustomerDetails')
		.execute(function(){
			$('.orderMachineBackButton').click(function(){
				fsm.transition('clickBack');
			});

			$('.orderMachineNextButton').click(function(){
				fsm.transition('clickNext');
			});

			$('.orderMachineCancelButton').click(function(event){
				event.preventDefault();
				if (Order_Form.confirmCancel()) {
					fsm.transition('clickCancel');
				}
			});

			$('.orderMachineSaveButton').click(function(){
				fsm.transition('clickSave');
			});

			var customerId = $('input[name=customerId]').val();
			if (customerId && customerId != 0) {
				Order_Form.loadCustomer(customerId);
			}
		});

fsm.state('CustomerDetails')
	.transition('clickCancel', 'Cancel')
	.from.transition('clickNext', 'ValidateCustomerDetails');

fsm.state('ValidateCustomerDetails')
	.enter(function()
	{
		if(!Order_Form.validateCustomerDetails()) {
			fsm.transition('customerDetailsInvalid');
			return;
		}

		// Loading indicator

		var formData = Order_Form.getSerializedSection('.orderMachineStateCustomerDetails');
		$.ajax({
			url: 'remote.php?remoteSection=orders&w=editOrderSaveBillingAddress',
			data: formData,
			dataType: 'json',
			type: 'post',
			success: Order_Form.handleResponse,
			error: function(){
				fsm.transition('customerDetailsInvalid');
			}
		});
	})
	.transition('customerDetailsOk', 'Items')
	.from.transition('clickCancel', 'Cancel')
	.from.transition('customerDetailsInvalid', 'CustomerDetails');

fsm.state('Items')
	.transition('clickBack', 'CustomerDetails')
	.from.transition('clickCancel', 'Cancel')
	.from.transition('clickNext', 'ValidateItems');

fsm.state('ValidateItems')
	.enter(function()
	{
		if(!Order_Form.validateOrderItems()) {
			fsm.transition('itemsInvalid');
			return;
		}

		fsm.transition('itemsOk');
	})
	.transition('itemsOk', 'Shipping')
	.from.transition('clickCancel', 'Cancel')
	.from.transition('itemsInvalid', 'Items');

fsm.state('Shipping')
	.enter(function() {
		var shipItemsTo = $('input[name=shipItemsTo]:checked').val();
		if(shipItemsTo == 'billing') {
			Order_Form.copyBillingDetailsToShipping();
		}
	})
	.transition('clickBack', 'Items')
	.from.transition('clickCancel', 'Cancel')
	.from.transition('clickNext', 'ValidateShipping');

fsm.state('ValidateShipping')
	.enter(function()
	{
		var shipItemsTo = $('[name=shipItemsTo]:checked').val();

		switch (shipItemsTo) {
			case 'multiple':
				break;

			default:
				// only validate if billing/single is selected
				if(!Order_Form.validateShipping()) {
					fsm.transition('shippingInvalid');
					return;
				}
		}

		// Loading indicator

		var formData = Order_Form.getSerializedSection('.orderMachineStateShipping');
		$.ajax({
			url: 'remote.php?remoteSection=orders&w=editOrderSaveShipping',
			data: formData,
			dataType: 'json',
			type: 'post',
			success: Order_Form.handleResponse,
			error: function(){
				fsm.transition('shippingInvalid');
			}
		});
	})
	.transition('shippingOk', 'Summary')
	.from.transition('clickCancel', 'Cancel')
	.from.transition('shippingInvalid', 'Shipping');

fsm.state('Summary')
	.transition('clickBack', 'Shipping')
	.from.transition('clickCancel', 'Cancel')
	.from.transition('clickChangeBillingDetails', 'CustomerDetails')
	.from.transition('clickChangeShippingDetails', 'Shipping')
	.from.transition('clickChangeShippingMethod', 'Shipping')
	.from.transition('clickSave', 'ValidateSummary')  // aka 'save', but it's called ValidateSummary so that the Summary state remains visible while saving
		.execute(function(){
			if (Order_Form.getIsDeleted()) {
				// don't bother contacting the server to save a deleted order because it will be denied
				alert(lang.EditDeletedOrderSaveNotice);
				return false;
			}
		});

fsm.state('ValidateSummary')
	.enter(function(){
		if (Order_Form.getPaymentMethod() != undefined) {
			if (!Order_Form.validatePaymentMethod()) {
				fsm.transition('saveError');
				return;
			}
		}

		fsm.state.indicator = LoadingIndicator.Show({
			background: '#fff',
			parent: '.orderMachineStateSummaryLoadingIndicator'
		});

		// js validation already run above, commit to server
		var formData = Order_Form.getSerializedSection('.orderMachineStateCustomerDetails, .orderMachineStateSummary');
		$.ajax({
			url: 'remote.php?remoteSection=orders&w=editOrderSave',
			data: formData,
			dataType: 'json',
			type: 'post',
			success: Order_Form.handleResponse,
			error: function(){
				fsm.transition('saveError');
			}
		});
	})
	.exit(function(){
		LoadingIndicator.Destroy(fsm.state.indicator);
	})
	.transition('clickCancel', 'Cancel')
	.from.transition('saveError', 'Summary')
	.from.transition('saveOk', 'End')
		.execute(function(){
			// don't ask to confirm leaving the page after a successful save
			Order_Form.preventUnload(false);
		});

fsm.state('Cancel')
	.enter(function(ev, state){
		state.machine.finish();
		window.location.href = 'index.php?ToDo=viewOrders';
	});

fsm.state('End')
	.enter(function(ev, state){
		state.machine.finish();
		var href = 'index.php?ToDo=viewOrders';
		if (Order_Form.orderId) {
			href += '&selectOrder=' + Order_Form.orderId;
		}
		window.location.href = href;
	});

$(fsm)
	.bind('machine_finish', function(){
		$.iModal.close();
		if (fsm.previousState) {
			$('.orderMachineState' + fsm.previousState.name.replace(/^Validate/, '')).show();
		}
	})
	.bind('state_enter', function(e, state){
		var stateName = state.name.replace(/^Validate/, '');
		$('.orderMachineState' + stateName).show();
	})
	.bind('state_exit', function(e, state){
		// on exit, track previous state so that when we enter cancel/end states we can keep the previous state's elements visible instead of hiding everything
		fsm.previousState = state;
		var stateName = state.name.replace(/^Validate/, '');
		$('.orderMachineState' + stateName).hide();
	})
	.bind('transitions_change', function(e, fsm){
		$('.orderMachineSaveButton').enabled(fsm.can('clickSave'));
		$('.orderMachineBackButton').enabled(fsm.can('clickBack'));
		$('.orderMachineCancelButton').enabled(fsm.can('clickCancel'));
		$('.orderMachineNextButton').enabled(fsm.can('clickNext'));
	});

})(jQuery);
