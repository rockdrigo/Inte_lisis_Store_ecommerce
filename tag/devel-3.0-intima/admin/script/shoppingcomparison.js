var ShoppingComparison = {
	modules: {},

	initJobTracker: function()
	{
		for(var moduleId in ShoppingComparison.modules){
			var module = ShoppingComparison.modules[moduleId];

			$('#' + moduleId)
				.exportTracker({
					startJobUrl: ShoppingComparison.startJobUrl
						.replace(':moduleid', moduleId),
					stopJobUrl: ShoppingComparison.stopJobUrl
						.replace(':moduleid', moduleId),
					messages: {
						progress: lang.ShoppingComparisonFeedBeingGenerated
							.replace(':name', module.name)
							.replace(':complete', '0')
					},
					jobid: module.jobid
				});
		};
	},

	cancelButton: function()
	{
		if(confirm(lang.ConfirmCancel)) {
			window.location.reload(false);
		}
	},

	openUnmappedCategoriesModal: function(module)
	{
		var id = module.attr('id');
		var count = ShoppingComparison.modules[id].unmappedCategories;
		var name = ShoppingComparison.modules[id].name;

		$('#unmappedCategoriesModal .ModalTitle').html(
			lang.ShoppingComparisonUnmappedCategoriesModalTitle
				.replace(':count', count)
			);

		$('#unmappedCategoriesModal .content').html(
			lang.ShoppingComparisonUnmappedCategoriesModalHelp
				.replace(':count', count)
				.replace(':name', name)
				.replace(':categoriesUrl', ShoppingComparison.categoriesUrl)
			);

		$.iModal({
			type: 'inline',
			inline: '#unmappedCategoriesModal',
			width: 505,
			height: 900
		});

		$('#ModalContainer .close').click($.iModal.close);
		$('#ModalContainer .generateFeed').click(
			function(){
				$.iModal.close();
				return module.exportTracker('start');
			});
	},

	generateFeedLink: function()
	{
		var module = $(this).parents('.module');
		var id = module.attr('id');

		if(ShoppingComparison.modules[id].unmappedCategories)
			ShoppingComparison.openUnmappedCategoriesModal(module);
		else
			module.exportTracker('start');

		return false;
	},

	downloadFeedLink: function()
	{
		window.location.href=$(this).attr('href');
		return false;
	},

	init: function()
	{
		$('#tabs').tabs();

		$('.generateFeed').click(this.generateFeedLink);
		$('.downloadFeed').click(this.downloadFeedLink);
		$('.cancel').click(this.cancelButton);


		ShoppingComparison.initJobTracker();
	}
};