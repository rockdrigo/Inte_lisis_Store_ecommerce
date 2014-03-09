;(function($){

// create and use `fsm` locally
var fsm = new Interspire_FSM();

// the init state immediately starts loading modal content via ajax and waits for it to be loaded, after which it automatically transitions to the select_list state
fsm.state('Init')
	.initial()
	.enter(function(ev, state){
		$.iModal({
			type: 'ajax',
			method: 'post',
			width: 500,
			url: 'remote.php',
			urlData: {
				remoteSection: 'ebay',
				w: 'getEndListingDialog',
				itemCount: fsm.payload.selectedItemIds.length
			},
			onError: function () {
				state.machine.finish();
				alert(lang.LoadDialogFailed);
			},
			onShow: function () {
				state.machine.transition('ModalShow');
			},
			onClose: function () {
				// attempt to clean up the state machine if the user closes the dialog
				state.machine.finish();
			}
		});
	})

	.transition('ModalShow', 'LoadSelectedItems')
		.execute(function(){
			$('.ListingMachine_FinishButton').click(function(){
				var valid = true;
				$('[name^=item_]').each(function() {
					if ($(this).val() == "") {
						valid = false;
					}
				});
				if (valid) {
					if (confirm(lang.ConfirmEndListing)) {
						fsm.transition('ClickFinish');
					} else {
						fsm.transition('ClickCancel');
					}
				} else {
					alert (lang.SelectAllEndReason);
				}
			});
			$('.ListingMachine_CancelButton').click(function(){
				fsm.transition('ClickCancel');
			});
			$('.ListingMachine_CloseButton').click(function(){
				fsm.transition('ClickClose');
			});

		});

fsm.state('LoadSelectedItems')
	.enter(function(ev, state) {
		fsm.getListingList();
	})
	.transition('ClickFinish', 'EndListing')
	.from.transition('SelectedItemsLoadFailed', 'LoadSelectedItemsFailed')
	.from.transition('ClickCancel', 'End');

fsm.state('LoadSelectedItemsFailed')
	.enter(function(ev, state) {
		fsm.transition('ClickCancel');
	})
	.transition('ClickCancel', 'End');

fsm.state('EndListing')
	.enter(function(ev, state) {
		fsm.endListing();
	})
	.transition('PartiallyEnded', 'DisplayPartiallyEnded')
	.from.transition('EndedSuccessfully', 'DisplayEndedSuccessfully')
	.from.transition('EndedFailure', 'DisplayEndedFailure')
	.from.transition('ClickFinish', 'EndListing');

fsm.state('DisplayPartiallyEnded')
	.enter(function(ev, state) {
		$('.ListingMachine_State_InputReasonIntro').hide();
		$('.ListingMachine_State_EndSuccess').hide();
		$('.ListingMachine_State_EndFailure').hide();
		$('#listingContainer').hide();
		$('.ListingMachine_State_EndPartialSuccess').show();
		$('.ListingMachine_State_EndPartialSuccess').append(fsm.payload.endingListingResultHtml);
		$('.ListingMachine_State_EndResultFailureNote').show();
	})
	.transition('ClickClose', 'End');

fsm.state('DisplayEndedSuccessfully')
	.enter(function(ev, state) {
		$('.ListingMachine_State_InputReasonIntro').hide();
		$('.ListingMachine_State_EndSuccess').show();
		$('.ListingMachine_State_EndSuccess').append(fsm.payload.endingListingResultHtml);
		$('.ListingMachine_State_EndFailure').hide();
		$('#listingContainer').hide();
		$('.ListingMachine_State_EndPartialSuccess').hide();
		$('.ListingMachine_State_EndResultFailureNote').hide();
	})
	.transition('ClickClose', 'End');

fsm.state('DisplayEndedFailure')
	.enter(function(ev, state) {
		$('.ListingMachine_State_InputReasonIntro').hide();
		$('.ListingMachine_State_EndSuccess').hide();
		$('.ListingMachine_State_EndFailure').show();
		$('#listingContainer').hide();
		$('.ListingMachine_State_EndPartialSuccess').hide();
		$('.ListingMachine_State_EndResultFailureNote').show();
	})
	.transition('ClickClose', 'End');

fsm.endListing = function() {
	var selectedReason = $("[name=endReasons]:checked").val();
	$.ajax({
		url: 'remote.php',
		type: 'post',
		dataType: 'json',
		data: {
			remoteSection: 'ebay',
			w: 'endListing',
			selectedItems: fsm.payload.selectedItemIds,
			selectedReason: selectedReason
		},
		success: function(data) {
			if (data && data.success) {
				if (data.UpdatedListingRows || data.AllSuccess) {
					$("#deleteebaylivelisting .GridContainer").html(data.UpdatedListingRows);
				}
				fsm.payload.endingListingResultHtml = data.endinglistingresultHTML;
				if (data.PartiallySuccess) {
					fsm.transition('PartiallyEnded');
				} else if (data.AllSuccess) {
					fsm.transition('EndedSuccessfully');
				} else if (data.Failure) {
					fsm.transition('EndedFailure');
				}
				$("#listingContainer").html(data.endinglistingresultHTML);
			}
		}
	});
};

fsm.getListingList = function() {
	$.ajax({
		url: 'remote.php',
		type: 'post',
		dataType: 'json',
		data: {
			remoteSection: 'ebay',
			w: 'getEndingReason'
		},
		success: function(data) {
			if (data && data.success) {
				$("#listingContainer").html(data.endinglistingreasonHTML);
			} else {
				fsm.transition('SelectedItemsLoadFailed');
				alert(lang.UnknownErrorRetrieveData);
			}
		}
		,
		error: function() {
			fsm.transition('SelectedItemsLoadFailed');
				alert(lang.UnknownErrorRetrieveData);
		}
	});
};

fsm.state('End')
	.enter(function(ev, state){
		state.machine.finish();
	});

$(fsm).bind('machine_finish', function(event, fsm){
	// the machine has finished, so make sure the modal is closed
	$.iModal.close();
});

$(fsm).bind('transitions_change', function(event, fsm){
	// the transition available for the machine's current state have changed, check to see how buttons should be configured

	$('.ListingMachine_CloseButton').enabled(fsm.can('ClickClose'));
	$('.ListingMachine_CancelButton').enabled(fsm.can('ClickCancel'));
	$('[name^=item_]').enabled(fsm.can('ClickCancel'));
	$('[name=allReasons]').enabled(fsm.can('ClickCancel'));
	$('.ListingMachine_FinishButton').enabled(fsm.can('ClickFinish'));

	if (fsm.can('ClickFinish')) {
		$('.ListingMachine_CloseButton').hide();
		$('.ListingMachine_CancelButton').show();
	}
	else {
		$('.ListingMachine_CloseButton').show();
		$('.ListingMachine_CancelButton').hide();
		$('.ListingMachine_FinishButton').hide();
	}
});

// assign to global scope with proper name
Interspire_Ebay_EndListingMachine = fsm;

$(document).ready(function() {
	$(".EndingItemReasonBox input").live('change', function() {
		$(this).parents('ul').find('li').removeClass('SelectedRow')
		$(this).parents('li').addClass('SelectedRow');
	});

	$(".EndingItemReasonBox li").live('mouseenter', function() {
		$(this).addClass('ISSelectOptionHover');
	});

	$(".EndingItemReasonBox li").live('mouseleave', function() {
		$(this).removeClass('ISSelectOptionHover');
	});
});

})(jQuery);