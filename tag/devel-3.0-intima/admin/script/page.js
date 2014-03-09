function ToggleOptimizerConfigForm(skipConfirmMsg) {

	if($('#pageEnableOptimizer').attr('checked')) {

		var showForm = true;
		if(!skipConfirmMsg) {
			showForm = confirm(lang.ConfirmEnablePageOptimizer);
		}

		if(showForm) {
			$('#OptimizerConfigForm').show();
		} else {
			$('#pageEnableOptimizer').attr('checked', false)
		}
	} else {
		$('#OptimizerConfigForm').hide();
	}
}

