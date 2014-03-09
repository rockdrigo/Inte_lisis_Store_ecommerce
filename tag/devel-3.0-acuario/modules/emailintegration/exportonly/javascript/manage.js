;(function($){

Interspire_EmailIntegration_Provider_ExportOnly = function(){
	var self = this;
	Interspire_EmailIntegration_ProviderModel.call(this, 'emailintegration_exportonly');

	self.getApiAuthData = function () {
		return {};
	}
};

var exportonly = new Interspire_EmailIntegration_Provider_ExportOnly();

$(function(){
	$('#emailintegration_exportonly .exportSubscriptionsButton').click(function(event){
		event.preventDefault();
		Common.ExportNewsletterSubscribers();
	});


	$('#emailintegration_exportonly .deleteSubscriptionsButton').click(function(event){
		event.preventDefault();

		if (!confirm(lang.EmailIntegration_ExportOnly_Delete_Confirm)) {
			return;
		}

		exportonly.ajax('deleteSavedSubscribers', {
			success: function(){
				$('#emailintegration_exportonly_subscribercount').val(0);
				$('#emailintegration_exportonly_subscriberactions').hide();
			}
		});
	});
});

})(jQuery);
