;(function($){

// create and use `fsm` locally
var fsm = new Interspire_FSM();

// the init state immediately starts loading modal content via ajax and waits for it to be loaded, after which it automatically transitions to the select_list state
fsm.state('Init')
	.initial()
	.enter(function(ev, state){
		// replace exportModule with an instance of Interspire_EmailIntegration_ProviderModel for that module
		fsm.payload.exportModule = new Interspire_EmailIntegration_ProviderModel(fsm.payload.exportModule);

		$.iModal({
			type: 'ajax',
			method: 'post',
			width: 520,
			url: 'remote.php',
			urlData: {
				remoteSection: 'emailintegration',
				w: 'moduleExport',
				exportType: fsm.payload.exportType,
				exportStep: 'init',
				exportModule: fsm.payload.exportModule.id,
				exportSearch: fsm.payload.exportSearch
			},
			onError: function () {
				fsm.finish();
				alert(lang.EmailIntegration_ExportMachine_FailedToLoadDialog);
			},
			onShow: function () {
				fsm.transition('ModalShow');
			},
			onClose: function () {
				// attempt to clean up the state machine if the user closes the dialog
				fsm.finish();
			}
		});
	})
	.transition('ModalShow', function(){
		// this transition points dynamically to either NoLists or SelectList depending on the resulting HTML content of the modal
		if ($('.ExportMachine_State_NoLists').length) {
			return fsm.state('NoLists');
		}
		return fsm.state('SelectList');
	})
		.execute(function(){
			// we are transitioning from Init to SelectList, so the modal is showing for the first time and we need to setup binds on the common buttons

			$('.ExportMachine_BackButton').click(function(){
				fsm.transition('ClickBack');
			});

			$('.ExportMachine_NextButton').click(function(){
				fsm.transition('ClickNext');
			});

			$('.ExportMachine_CancelButton').click(function(){
				fsm.transition('ClickCancel');
			});

			$('.ExportMachine_FinishButton').click(function(){
				fsm.transition('ClickFinish');
			});

			$('.ExportMachine_CloseButton').click(function(){
				fsm.transition('ClickClose');
			});

			$('.ExportMachine_SelectList').dblclick(function(){
				$('.ExportMachine_NextButton').click();
			});
		});

fsm.state('NoLists')
	.transition('ClickCancel', 'End');

fsm.state('SelectList')
	.enter(function(){
		$('.ExportMachine_State_SelectList .ExportMachine_SelectList').focus();
	})
	.transition('ClickCancel', 'End')
	.from.transition('ClickNext', 'PrepareConfigureFields')
		.test(function(){
			// only allow this transition if a list is selected
			return !!$('.ExportMachine_SelectList').val();
		})
		.poll('.ExportMachine_SelectList', 'change'); // watch this element's events to run the above test automatically

fsm.state('PrepareConfigureFields')
	.enter(function(){
		var listId = $('.ExportMachine_SelectList').val();
		if (typeof fsm.payload.listId == 'undefined' || fsm.payload.listId != listId) {
			// list has changed or been selected for the first time
			fsm.payload.listId = listId;
			fsm.payload.listName = $('.ExportMachine_SelectList option:selected').text();

			var module = fsm.payload.exportModule;
			module.ajaxAction('getFieldSyncForm', {
				data: {
					listId: listId,
					modalContentOnly: true,
					subscriptionType: fsm.payload.exportType
				},
				success: function (data) {
					$('.ExportMachine_ConfigureFields_Container').html(data.html);
					fsm.transition('FieldsPrepared');
				}
			});
		} else {
			// last has not changed from previous selection -- proceed immediately
			fsm.transition('FieldsPrepared');
		}
	})
	.transition('ClickCancel', 'End')
	.from.transition('FieldsPrepared', 'ConfigureFields');

fsm.state('ConfigureFields')
	.enter(function(){
		$('.ExportMachine_State_ConfigureFields .mapLocal:first').focus()
	})
	.transition('ClickCancel', 'End')
	.from.transition('ClickBack', 'SelectList')
	.from.transition('ClickNext', 'ConfirmExport')
		.execute(function(event){
			var valid = Interspire_EmailIntegration.validateFieldSyncForm(false);
			if (!valid) {
				$('.ExportMachine_ConfigureFields_OkMessage').hide();
				$('.ExportMachine_ConfigureFields_ErrorMessage').show();
				$('.ExportMachine_ConfigureFields').scrollTop(0);
				event.preventDefault();
				return;
			}
			$('.ExportMachine_ConfigureFields_ErrorMessage').hide();

			// when transitioning from ConfigureFields to ConfirmExport, serialise the configured field map
			fsm.payload.exportMap = Interspire_EmailIntegration.serializeFieldSyncForm();
		});

fsm.state('ConfirmExport')
	.enter(function(){
		$('.ExportMachine_ConfirmExport_ListName').text(fsm.payload.listName);
	})
	.transition('ClickCancel', 'End')
	.from.transition('ClickBack', 'ConfigureFields')
	.from.transition('ClickFinish', 'CommenceExport')
		.execute(function(){
			fsm.payload.exportDoubleOptin = $('#ExportMachine_ConfirmExport_DoubleOptin').attr('checked');
			fsm.payload.exportUpdateExisting = $('#ExportMachine_ConfirmExport_UpdateExisting').attr('checked');
		});

fsm.state('CommenceExport')
	.enter(function(ev, state){
		$.ajax({
			type: 'POST',
			url: 'remote.php',
			data: {
				remoteSection: 'emailintegration',
				w: 'moduleExport',
				exportType: fsm.payload.exportType,
				exportStep: 'commence',
				exportModule: fsm.payload.exportModule.id,
				exportSearch: fsm.payload.exportSearch,
				exportMap: fsm.payload.exportMap,
				exportList: fsm.payload.listId,
				exportDoubleOptin: fsm.payload.exportDoubleOptin ? 1 : 0,
				exportUpdateExisting: fsm.payload.exportUpdateExisting ? 1 : 0
			},
			dataType: 'json',
			success: function (data) {
				if (data && data.success) {
					fsm.transition('ExportCommenced');
				} else {
					fsm.transition('ExportCommenceFailed');
				}
			},
			error: function () {
				fsm.transition('ExportCommenceFailed');
			}
		});
	})
	.transition('ExportCommenced', 'Confirmation')
	.from.transition('ExportCommenceFailed', 'ExportFailed');

fsm.state('ExportFailed')
	.transition('ClickBack', 'ConfirmExport')
	.from.transition('ClickCancel', 'End');

fsm.state('Confirmation')
	.enter(function(ev, state){
		// when confirmation window shows, force the task manager to process a job if it's not already
		if (window.taskManager) {
			window.taskManager.check();
		}
	})
	.transition('ClickClose', 'End');

fsm.state('End')
	.enter(function(ev, state){
		fsm.finish();
	});

$(fsm).bind('machine_finish', function(event, fsm){
	// the machine has finished, so make sure the modal is closed
	$.iModal.close();
});

$(fsm).bind('state_enter', function(event, state){
	// on entering any state, try showing a dialog element that relates to that state
	//console.debug('entering state: ' + state.name);
	$('.ExportMachine_State_' + state.name).show();
});

$(fsm).bind('state_exit', function(event, state){
	// on exiting any state, try showing a dialog element that relates to that state
	//console.debug('exiting state: ' + state.name);
	$('.ExportMachine_State_' + state.name).hide();
});

$(fsm).bind('transitions_change', function(event, fsm){
	// the transition available for the machine's current state have changed, check to see how buttons should be configured

	$('.ExportMachine_BackButton').enabled(fsm.can('ClickBack'));
	$('.ExportMachine_NextButton').enabled(fsm.can('ClickNext'));
	$('.ExportMachine_CancelButton').enabled(fsm.can('ClickCancel'));

	if (fsm.can('ClickFinish') || fsm.can('ClickClose')) {
		$('.ExportMachine_NextButton').hide();

		if (fsm.can('ClickFinish')) {
			$('.ExportMachine_FinishButton').show();
		} else {
			$('.ExportMachine_FinishButton').hide();
		}

		if (fsm.can('ClickClose')) {
			$('.ExportMachine_CloseButton').show();
		} else {
			$('.ExportMachine_CloseButton').hide();
		}
	}
	else
	{
		$('.ExportMachine_NextButton').show();
		$('.ExportMachine_FinishButton, .ExportMachine_CloseButton').hide();
	}
});

// assign to global scope with proper name
Interspire_EmailIntegration_ModuleExportMachine = fsm;

})(jQuery);

// split context with the above so there's no clash over the `fsm` variable
/*
(function($){

// create and use `fsm` locally
var fsm = new Interspire_FSM();

// the init state immediately starts loading modal content via ajax and waits for it to be loaded, after which it automatically transitions to the select_list state
fsm.state('Init')
	.initial()
	.enter(function(ev, state){
		$.iModal({
			type: 'ajax',
			method: 'post',
			width: 520,
			url: 'remote.php',
			urlData: {
				remoteSection: 'emailintegration',
				w: 'ruleExport',
				exportType: fsm.payload.exportType,
				exportStep: 'init',
				exportRule: fsm.payload.exportRule,
				exportSearch: fsm.payload.exportSearch
			},
			onError: function () {
				fsm.finish();
				alert(lang.EmailIntegration_ExportMachine_FailedToLoadDialog);
			},
			onShow: function () {
				fsm.transition('ModalShow')
			},
			onClose: function () {
				// attempt to clean up the state machine if the user closes the dialog
				fsm.finish();
			},
		});
	})
	.transition('ModalShow', 'ConfirmExport')
		.execute(function(){
			// we are transitioning from Init to SelectList, so the modal is showing for the first time and we need to setup binds on the common buttons
			$('.ExportMachine_BackButton').click(function(){
				fsm.transition('ClickBack');
			});

			$('.ExportMachine_CancelButton').click(function(){
				fsm.transition('ClickCancel');
			});

			$('.ExportMachine_FinishButton').click(function(){
				fsm.transition('ClickFinish');
			});
		});

fsm.state('ConfirmExport')
	.transition('ClickCancel', 'End')
	.from.transition('ClickFinish', 'CommenceExport')
		.execute(function(){
			fsm.payload.exportDoubleOptin = $('#ExportMachine_ConfirmExport_DoubleOptin').attr('checked');
			fsm.payload.exportUpdateExisting = $('#ExportMachine_ConfirmExport_UpdateExisting').attr('checked');
		});

fsm.state('CommenceExport')
	.enter(function(ev, state){
		$.ajax({
			type: 'POST',
			url: 'remote.php',
			data: {
				remoteSection: 'emailintegration',
				w: 'ruleExport',
				exportType: fsm.payload.exportType,
				exportStep: 'commence',
				exportRule: fsm.payload.exportRule,
				exportSearch: fsm.payload.exportSearch,
				exportDoubleOptin: fsm.payload.exportDoubleOptin ? 1 : 0,
				exportUpdateExisting: fsm.payload.exportUpdateExisting ? 1 : 0
			},
			dataType: 'json',
			success: function (data) {
				if (data && data.success) {
					fsm.transition('ExportCommenced');
				} else {
					fsm.transition('ExportCommenceFailed');
				}
			},
			error: function () {
				fsm.transition('ExportCommenceFailed');
			}
		});
	})
	.transition('ExportCommenced', 'Confirmation')
	.from.transition('ExportCommenceFailed', 'ExportFailed');

fsm.state('ExportFailed')
	.transition('ClickBack', 'ConfirmExport')
	.from.transition('ClickCancel', 'End');

fsm.state('Confirmation')
	.enter(function(ev, state){
		// when confirmation window shows, force the task manager to process a job if it's not already
		if (window.taskManager) {
			window.taskManager.check();
		}
	})
	.transition('ClickClose', 'End');

fsm.state('End')
	.enter(function(ev, state){
		fsm.finish();
	});

$(fsm).bind('machine_finish', function(event, fsm){
	// the machine has finished, so make sure the modal is closed
	$.iModal.close();
});

$(fsm).bind('state_enter', function(event, state){
	// on entering any state, try showing a dialog element that relates to that state
	//console.debug('entering state: ' + state.name);
	$('.ExportMachine_State_' + state.name).show();
});

$(fsm).bind('state_exit', function(event, state){
	// on exiting any state, try showing a dialog element that relates to that state
	//console.debug('exiting state: ' + state.name);
	$('.ExportMachine_State_' + state.name).hide();
});

$(fsm).bind('transitions_change', function(event, fsm){
	// the transition available for the machine's current state have changed, check to see how buttons should be configured
	$('.ExportMachine_BackButton').enabled(fsm.can('ClickBack'));
	$('.ExportMachine_FinishButton').enabled(fsm.can('ClickFinish'));
	$('.ExportMachine_CancelButton').enabled(fsm.can('ClickCancel'));
});

// assign to global scope with proper name
Interspire_EmailIntegration_RuleExportMachine = fsm;

})(jQuery);
*/
