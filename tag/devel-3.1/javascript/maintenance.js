
var MaintenanceNotice = {
	scrollTop: 0,
	toolbar: {},
	AdminMaintenanceNoticeHeader: '',
	AdminMaintenanceNotice: '',


	init: function()
	{
		// Generate toolbar
		MaintenanceNotice.toolbar = document.createElement("DIV");
		MaintenanceNotice.toolbar.id = "maintenance_notice";
		MaintenanceNotice.toolbar.className = "MaintenanceModeNotice";

		MaintenanceNotice.toolbar.innerHTML = '<div class="MaintenanceModeHeader" id="MaintenanceModeHeader">' +
			MaintenanceNotice.AdminMaintenanceNoticeHeader +
			"</div>" +
			MaintenanceNotice.AdminMaintenanceNotice;

		// Append the toolbar to the document
		document.body.appendChild(MaintenanceNotice.toolbar);

		// Add drag events
		$("#maintenance_notice").draggable({
			stop: function(event, ui) {
				MaintenanceNotice.scrollTop = $(this).offset().top - $('body').scrollTop();
				if(parseInt(this.style.left) < 1) {
					this.style.left = '1px';
				}
			}
		});

		$(window)
			.bind('scroll', function() {
				MaintenanceNotice.toolbar.style.top = ($('body').scrollTop() + MaintenanceNotice.scrollTop) + "px";
			})
			.bind('resize', function(event) {
				menuWidth = $('#maintenance_notice').width();
				if(menuWidth + $('#maintenance_notice').offset().left > $(window).width()) {
					newLeft = ($(window).width() - menuWidth - 50) + 'px';
					$('#maintenance_notice').css('left', newLeft);
				}
			})
		;

		MaintenanceNotice.scrollTop = $('#maintenance_notice').scrollTop() - $('body').scrollTop();
		$(window).trigger('resize');
	}
};
