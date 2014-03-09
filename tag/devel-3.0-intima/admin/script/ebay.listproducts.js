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
			width: 420,
			url: 'remote.php',
			urlData: {
				remoteSection: 'ebay',
				w: 'getListProductsDialog',
				productOptions: fsm.payload.productOptions
			},
			onError: function () {
				state.machine.finish();
				alert(lang.EmailIntegration_ListMachine_FailedToLoadDialog);
			},
			onShow: function () {
				if (fsm.payload.productCount == 0) {
					state.machine.transition('ModalShowNoProducts');
				}
				else {
					state.machine.transition('ModalShow');
				}
			},
			onClose: function () {
				// attempt to clean up the state machine if the user closes the dialog
				state.machine.finish();
			}
		});
	})
	.transition('ModalShow', 'SelectTemplate')
		.execute(function(){
			// we are transitioning from Init to SelectTemplate, so the modal is showing for the first time and we need to setup binds on the common buttons

			$('.ListMachine_BackButton').click(function(){
				fsm.transition('ClickBack');
			});

			$('.ListMachine_NextButton').click(function(){
				fsm.transition('ClickNext');
			});

			$('.ListMachine_ListButton').click(function(){
				fsm.transition('ClickList');
			});

			$('.ListMachine_LinkToLiveListButton').click(function(){
				fsm.transition('ClickLinkToLiveList');
			});

			$('.ListMachine_CancelButton').click(function(){
				fsm.transition('ClickCancel');
			});

			$('.ListMachine_CloseButton').click(function(){
				fsm.transition('ClickClose');
			});

			$('.ListMachine_StopButton').click(function(){
				fsm.transition('ClickStop');
			});
		})
	.from.transition('ModalShowNoProducts', 'NoProductsSelected')
		.execute(function(){
			$('.ListMachine_CancelButton').click(function(){
				fsm.transition('ClickCancel');
			});
		});

fsm.state('NoProductsSelected')
	.transition('ClickCancel', 'End');

fsm.state('SelectTemplate')
	.transition('ClickCancel', 'End')
	.from.transition('ClickNext', 'GetCategoryFeatures')
		.test(function(){
			// only allow this transition if a template is selected
			return !!$('.ListMachine_SelectTemplate').val();
		})
		.poll('.ListMachine_SelectTemplate', 'change')
		.execute(function() {
			fsm.payload.templateId = $('.ListMachine_SelectTemplate').val();

			var listingDate = $("input[name='listingDate']:checked").val();

			fsm.payload.scheduleDate = null;
			fsm.payload.scheduleDateOffset = null;

			// check for a valid schedule date
			if (listingDate == 'schedule') {
				var scheduleDate = $('#scheduleDate').datepicker('getDate');
				if (scheduleDate == null) {
					alert('Please select a date to schedule the listing.');
					$('#scheduleDate').focus();
					return false;
				}

				var hours = parseInt($("#timeHour").val());
				var ampm = $("#timeAMPM").val();
				if (ampm == "pm" && hours < 12) {
					hours += 12;
				}
				else if (ampm == "am" && hours == 12) {
					hours = 0;
				}
				scheduleDate.setHours(hours);
				scheduleDate.setMinutes(parseInt($("#timeMinutes").val()));

				if (scheduleDate < new Date()) {
					alert('Please select a date and time in the future.');
					return false;
				}

				fsm.payload.scheduleDate = scheduleDate.getTime() / 1000;
				fsm.payload.scheduleDateOffset = -scheduleDate.getTimezoneOffset();
			}

			fsm.payload.listingDate = listingDate;
		});

fsm.state('GetCategoryFeatures')
	.enter(function(ev, state) {
		$(".categoryFeatures").html('');

		// get category features
		$.ajax({
			url: 'remote.php',
			type: 'post',
			dataType: 'json',
			data: {
				remoteSection: 'ebay',
				w: 'getCategoryFeaturesList',
				templateId: fsm.payload.templateId,
				productOptions: fsm.payload.productOptions,
				originalProductCount: fsm.payload.originalProductCount,
				productCount: fsm.payload.productCount
			},
			success: function(data) {
				if (data && data.success) {
					$(".categoryFeatures").html(data.html);
					if (data.productOptions != '') {
						fsm.payload.productOptions = data.productOptions;
					}
					if (data.productCount != 0) {
						fsm.payload.productCount = data.productCount;
						fsm.transition('GetCategoryFeaturesSuccess');
					} else {
						fsm.transition('GetCategoryFeaturesInvalidProducts');
					}
				}
				else {
					fsm.transition('GetCategoryFeaturesFailed');
				}
			},
			error: function(data) {
				fsm.transition('GetCategoryFeaturesFailed');
			}
		});
	})
	.transition('GetCategoryFeaturesSuccess', 'CategoryFeatures')
	.from.transition('GetCategoryFeaturesFailed', 'CategoryFeaturesFailed')
	.from.transition('GetCategoryFeaturesInvalidProducts', 'CategoryFeaturesInvalidProducts');

fsm.state('CategoryFeatures')
	.transition('ClickCancel', 'End')
	.from.transition('ClickBack', 'SelectTemplate')
	.from.transition('ClickNext', 'GetEstimatedCosts');

fsm.state('CategoryFeaturesInvalidProducts')
	.transition('ClickCancel', 'End')
	.from.transition('ClickBack', 'SelectTemplate');

fsm.state('CategoryFeaturesFailed')
	.transition('ClickCancel', 'End')
	.from.transition('ClickBack', 'SelectTemplate');

fsm.state('GetEstimatedCosts')
	.enter(function(ev, state) {
		$("#estimatedCostsMessage").html('').hide();

		// get estimated costs
		$.ajax({
			url: 'remote.php',
			type: 'post',
			dataType: 'json',
			data: {
				remoteSection: 'ebay',
				w: 'getEstimatedListingCosts',
				productOptions: fsm.payload.productOptions,
				productCount: fsm.payload.productCount,
				templateId: fsm.payload.templateId,
				listingDate: fsm.payload.listingDate,
				scheduleDate: fsm.payload.scheduleDate,
				scheduleDateOffset: fsm.payload.scheduleDateOffset
			},
			success: function(data) {
				if (data && data.success) {
					$("#estimatedCostsContent").html(data.html);

					fsm.payload.extraFees = data.extraFees;

					fsm.transition('GetEstimatedCostsSuccess');
				}
				else {
					$('#estimatedCostsMessage').html(data.message).show();
					fsm.transition('GetEstimatedCostsFailed');
				}
			},
			error: function(data) {
				fsm.transition('GetEstimatedCostsFailed');
			}
		});
	})
	.transition('GetEstimatedCostsSuccess', 'EstimatedCosts')
	.from.transition('GetEstimatedCostsFailed', 'EstimatedCostsFailed');

fsm.state('EstimatedCosts')
	.transition('ClickCancel', 'End')
	.from.transition('ClickBack', 'CategoryFeatures')
	.from.transition('ClickList', 'ListProducts');

fsm.state('EstimatedCostsFailed')
	.transition('ClickCancel', 'End')
	.from.transition('ClickBack', 'CategoryFeatures');

fsm.state('LinkToLiveList')
	.enter(function(ev, state){
		state.machine.finish();
		top.location="index.php?ToDo=viewEbay&currentTab=0";
	});

fsm.state('ListProducts')
	.enter(function(ev, state) {
		$("#jobFailedMessage").html('').hide();

		// begin our listing
		$.ajax({
			url: 'remote.php',
			type: 'post',
			dataType: 'json',
			data: {
				remoteSection: 'ebay',
				w: 'initProductListing',
				productOptions: fsm.payload.productOptions,
				productCount: fsm.payload.productCount,
				templateId: fsm.payload.templateId,
				listingDate: fsm.payload.listingDate,
				scheduleDate: fsm.payload.scheduleDate,
				scheduleDateOffset: fsm.payload.scheduleDateOffset
			},
			success: function(data) {
				if (data && data.success) {
					fsm.payload.jobId = data.id;
					fsm.transition('JobStarted');
				}
				else {
					$("#jobFailedMessage").html(data.message).show();
					fsm.transition('JobFailed');
				}
			}
		});
	})
	.transition('JobStarted', 'ListProductsStarted')
	.from.transition('JobFailed', 'ListProductsStartFailed')

fsm.state('ListProductsStarted')
	.enter(function(ev, state) {
		setTimeout(updateListingProgress, 2000);
	})
	.transition('ClickClose', 'End')
	.from.transition('ListingFinished', 'ListProductsFinished')
	.from.transition('ListingCancelled', 'ListProductsCancelled');

fsm.state('ListProductsStartFailed')
	.transition('ClickBack', 'SelectTemplate')
	.from.transition('ClickClose', 'End');

fsm.state('ListProductsFinished')
	.transition('ClickLinkToLiveList', 'LinkToLiveList');

fsm.state('ListProductsCancelled')
	.transition('ClickClose', 'End');

fsm.state('Incomplete')
	.transition('ClickClose', 'End');

fsm.state('End')
	.enter(function(ev, state){
		state.machine.finish();
	});

$(fsm).bind('machine_finish', function(event, fsm){
	// the machine has finished, so make sure the modal is closed
	$.iModal.close();
});

$(fsm).bind('state_enter', function(event, state){
	// on entering any state, try showing a dialog element that relates to that state
	//console.debug('entering state: ' + state.name);
	$('.ListMachine_State_' + state.name).show();
});

$(fsm).bind('state_exit', function(event, state){
	// on exiting any state, try showing a dialog element that relates to that state
	//console.debug('exiting state: ' + state.name);
	$('.ListMachine_State_' + state.name).hide();
});

$(fsm).bind('transitions_change', function(event, fsm){
	// the transition available for the machine's current state have changed, check to see how buttons should be configured

	$('.ListMachine_NextButton').enabled(fsm.can('ClickNext'));
	$('.ListMachine_CancelButton').enabled(fsm.can('ClickCancel'));
	$('.ListMachine_BackButton').enabled(fsm.can('ClickBack'));

	if (fsm.can('ClickList')) {
		$('.ListMachine_ListButton').show();
		$('.ListMachine_NextButton').hide();
	}
	else {
		$('.ListMachine_ListButton').hide();
		$('.ListMachine_NextButton').show();
	}

	if (fsm.can('ClickLinkToLiveList')) {
		$('.ListMachine_NextButton').hide();
		$('.ListMachine_BackButton').hide();
		$('.ListMachine_CancelButton').hide();
		$('.ListMachine_LinkToLiveListButton').show();
	} else {
		$('.ListMachine_LinkToLiveListButton').hide();
	}

	if (fsm.can('ClickClose')) {
		$('.ListMachine_CloseButton').show();
		$('.ListMachine_AbortButton').show();
		$('.ListMachine_NextButton').hide();
		$('.ListMachine_BackButton').hide();
		$('.ListMachine_CancelButton').hide();
	}
	else {
		$('.ListMachine_CloseButton').hide();
		$('.ListMachine_AbortButton').hide();
		$('.ListMachine_CancelButton').show();
	}
});

// assign to global scope with proper name
Interspire_Ebay_ListProductsMachine = fsm;

function updateListingProgress() {
	$.ajax({
		url: 'remote.php',
		type: 'post',
		dataType: 'json',
		global: false,
		data: {
			remoteSection: 'ebay',
			w: 'getListingProgress',
			jobId: fsm.payload.jobId
		},
		success: function(data) {
			if (data && data.success) {
				$("#listingProgress").progressbar("value", data.percent);
				$("#listingProgressLabel").text(data.percent + '%');
				$("#listingProgressETA").html(data.progressBasic);

				if (data.percent == 100) {
					Interspire_Ebay_ListProductsMachine.transition('ListingFinished');
				}
				else {
					setTimeout(updateListingProgress, 2000);
				}
			}
		}
	});
}

})(jQuery);
