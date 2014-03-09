/*
 * jQuery UI Interspire Job Tracker
 */
(function($) {
	$.widget("ui.jobtracker", {
		_init: function() {
			var o = this.options;

			//$('.startJob', this.element).click(this.delegate('start'));

			if(typeof o.jobid != 'undefined' && o.jobid != '')
				this.handleStartResponse(o.jobid);
		},

		start: function() {
			var o = this.options;

			$.ajax({
				type: "GET",
				url: o.startJobUrl,
				success: this.delegate('handleStartResponse')
			});

			return false;
		},

		stop: function() {
			var o = this.options;
			o.running = false;

			clearTimeout(o.checkProgressId);

			$.ajax({
				type: "GET",
				url: o.stopJobUrl+'&id='+o.jobid,
				success: this.delegate('handleStopResponse')
			});

			return false;
		},

		clear: function() {
			var o = this.options;
			if ($('.generateFeed', this.element).is(':disabled'))
				$('.generateFeed', this.element).attr('disabled','');

			if ($('.downloadFeed', this.element).is(':disabled'))
				$('.downloadFeed', this.element).attr('disabled','');

			$('.content', this.element).html('');
		},

		handleStartResponse: function(id) {
			var o = this.options;
			o.jobid = id;
			o.running = true;

			if(window.taskManager)
				window.taskManager.check();

			this.showProgress();
		},

		handleStopResponse: function(data) {
			var o = this.options;

			this.clear();
			this.addMessage(data['message'], 'Success');

			o.jobid = null;
		},

		showProgress: function() {
			var o = this.options;

			this.clear();
			this.addMessage(o.messages['progress'], 'Info');

			$('.generateFeed', this.element).attr('disabled','disabled');
			$('.downloadFeed', this.element).attr('disabled','disabled');
			$('.stopLink', this.element).click(this.delegate('stop'));

			this.getProgress();
		},

		getProgress: function() {
			var o = this.options;

			$.ajax({
				dataType: "json",
				url: o.getProgressUrl+'&id='+o.jobid,
				success: this.delegate('getProgressResponse'),
				global: false
			});
		},

		getProgressResponse: function(data) {
			var o = this.options;

			if(o.running !== true || data == null)
				return;

			$('.complete', this.element).html(data['progress']);

			if(data['running'] != true){
				this.jobEnded(data);

				return;
			}
			o.checkProgressId = setTimeout(this.delegate('getProgress'), o.progressCheckFrequency);
		},

		jobEnded: function(data){
			var o = this.options;

			this.clear();
			this.addMessage(data['message'], 'Success');
		},

		addMessage: function(message, type){
			var o = this.options;

			$('<div class="MessageBox MessageBox'+type+'">' + message + '</div>')
			.fadeIn()
			.appendTo($('.content', this.element));
		},

		/**
		 * Returns an anonymous function wrapper to the given
		 * method with the context bound to the current object.
		 */
		delegate: function(method){
			var self = this;

			return function(){
				return self[method].apply(self, arguments);
			};
		},

		options:{
			jobid:null,
			startJobUrl: 'index.php?ToDo=JobStatusTestStartTask',
			stopJobUrl: 'index.php?ToDo=JobStatusTestStopTask',
			getProgressUrl:'index.php?ToDo=JobStatusGetProgress',
			progressCheckFrequency: 1000
	    }

	});

	$.widget('ui.exportTracker',
		$.extend({}, $.ui.jobtracker.prototype, {
		})
	);
})(jQuery);
