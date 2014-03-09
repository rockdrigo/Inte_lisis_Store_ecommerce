;(function($){

$('.emailIntegrationFieldSyncFormCancelButton').live('click', function(event){
	event.preventDefault();
	$.iModal.close();
});

$('.emailIntegrationFieldSyncFormSubmitButton').live('click', function(event){
	event.preventDefault();

	var valid = Interspire_EmailIntegration.validateFieldSyncForm();
	if (!valid) {
		return;
	}

	// serialize
	var map = Interspire_EmailIntegration.serializeFieldSyncForm();

	// the currently-syncing rule is stored on a dom element by customerrules.tpl
	var rule = $('.newCustomerSubscriptionRuleBuilder').data('syncingFieldsFor');
	$('.newCustomerSubscriptionRuleBuilder').removeData('syncingFieldsFor');
	rule.find('.customerrules_map').val(JSON.stringify(map));
	$.iModal.close();
});

$(function(){
	$('#emailIntegrationSettingsForm').submit(function(event){
		var $$ = $(this);

		// which modules are still enabled?
		var enabled = $$.find('select[name="modules[]"]').val();

		// which are enabled, and configured?
		var configured = {};
		$.each(Interspire_EmailIntegration_ProviderModel.modules, function(moduleName, provider){
			if (provider.getConfigured() && $.inArray(moduleName, enabled) > -1) {
				configured[moduleName] = provider;
			}
		});

		// perform validation and serialization for each of the modules
		$.each(configured, function(moduleName, provider){
			// serialize rules
			try {
				var rules = provider.generateRuleModels();
			} catch (error) {
				if (typeof error.isc != 'undefined') {
					// validation error during serialization
					$('#tabs').tabs('select', error.isc.module);
					display_error('Status', error.message);
				}
				event.preventDefault();
				return false;
			}

			if (!rules.length) {
				// valid, but no rules
				rules = '';
			} else {
				// valid rules
				rules = JSON.stringify(rules);
			}

			$$.find('input[name="' + moduleName + '[rules]"]').val(rules);
		});
	});
});

})(jQuery); // end jquery wrapper
