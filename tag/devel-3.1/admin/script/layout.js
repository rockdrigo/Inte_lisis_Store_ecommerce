var Layout = {
	init: function()
	{
		Layout.initMobileTab();
	},

	initMobileTab: function()
	{
		$('input[name=enableMobileTemplate]')
			.click(function() {
				$('.' + $(this).attr('name') + 'Toggle').toggle($(this).is(':checked'));
			});
		$('input[name=enableMobileTemplate]').triggerHandler('click');

		$('#mobileTemplateSettingsForm').submit(function() {
			if($('input[name=enableMobileTemplate]:checked').length > 0) {
				if(!$('.enableMobileTemplateToggle input[type=checkbox]:checked').length) {
					alert(lang.InvalidSettingenableMobileTemplateDevices);
					$('.enableMobileTemplateToggle input[type=checkbox]:first').focus();
					return false;
				}

				if(!$('input[name=mobileTemplateLogo]').val().match(/(^|\.(jpg|jpeg|png|gif))$/i)) {
					alert(lang.UploadValidMobileLogo);
					$('input[name=mobileTemplateLogo]').focus();
					return false;
				}
			}
		});
	}
};

$(Layout.init);