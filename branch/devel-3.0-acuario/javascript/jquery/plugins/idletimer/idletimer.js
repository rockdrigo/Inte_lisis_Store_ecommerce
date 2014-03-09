(function($){
	$.idleTimer = function(timeout, options){
		// init last event timestamp in cookie
		$.cookie('ISC_IdleTimer_LastEvent', new Date, options);

		// bind events to update cookie
		var events = 'mousemove keydown DOMMouseScroll mousewheel mousedown';
		$(document).bind(events, function(){
			$.cookie('ISC_IdleTimer_LastEvent', new Date, options);
		});

		if (!timeout) {
			// disabled
			return;
		}

		// if a path option is passed, update cookie from frontend (design mode)
		if (!options.hasOwnProperty('path')) {
			// check for idle timeout every 1 second
			var tid = setInterval('check()', 1000);
			check = function(){
				var now = new Date;
				var then = new Date($.cookie('ISC_IdleTimer_LastEvent'));
				var diff = now.getTime() - then.getTime();
				if (diff > timeout) {
					clearInterval(tid);
					document.location = 'index.php?ToDo=logOut&type=idle';

				}
			};
		}
	}
})(jQuery);