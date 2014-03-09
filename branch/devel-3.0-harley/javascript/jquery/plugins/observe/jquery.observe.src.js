/**
 * Observe changes to a set of form inputs (or anything which exposes val()) and trigger a callback every n msec when changes occur.
 *
 * Usage:
 * $(selector).observe(callback);			//	called with no options
 * $(selector).observe(options, callback);	//	called with both options and callback
 * $(selector).observe(options);			//	called with options, must supply callback as a property of options
 *
 * Example:
 * $(selector).observe({ delay: 5000, type: 'once' }, function(){ alert('changed'); });	// will display an alert 5 seconds after an input was last changed
 *
 * By default, this will consider value changes from all nodes defined by the selector as one change for observation. If you need to monitor and trigger on each input individually, call .observe() on each item, such as:
 *
 * $(selector).each(function(){
 *     $(this).observe(...);
 * });
 *
 * Available options:
 * type (string, default 'once') use 'once' to create an observer which triggers once at the end of a set of changes, use 'constant' to trigger every {delay} msec whenever changes are present. Using 'once' is a little more processor intensive as it continuously tracks value changes, consider using 'constant' if the inputs you are checking are many or complex.
 * delay (number, default 1000) Defines the trigger delay in milliseconds, the observer will not trigger it's callback more often that this delay.
 * checkDelay (number, default 100) Defines the value-checking interval delay in milliseconds, if you're checking a complex set of inputs you may need to increase this value to increase performance.
 * callback (function) Callback function to trigger, if this is not provided in the options object it must be provided as an argument as described in the usage above
 * start (boolean, default true) If set to true, will automatically start the observer's timer
 *
 * @author Gwilym Evans <gwilym.evans@interspire.com>
 */
;(function($){
	$.fn.observe = function (options, callback) {
		var self = this;

		self.getValue = function () {
			var val = [];
			self.nodes.each(function(){
				val.push($(this).val());
			});
			return val.join('|');
		};

		self.start = function () {
			self.stop();
			self.lastTrigger = self.lastChange = new Date();
			self.interval = window.setInterval(self.intervalCheck, self.options.checkDelay);
			self.lastValue = self.getValue();
			self.changed = false;
		};

		self.stop = function () {
			window.clearTimeout(self.timeout);
			window.clearInterval(self.interval);
		};

		self.intervalCheck = function () {
			if (self.nodes.parents('body').length == 0) {
				//	all nodes have been removed from the dom, stop observing
				self.stop();
				return;
			}

			if (self.options.type != 'constant' || !self.changed) {
				//	if the value is already known to be changed since last trigger, we do not need to check it again for the constant trigger type
				var val = self.getValue();

				if (val != self.lastValue) {
					self.lastChange = new Date();
					self.lastValue = val;

					if (!self.changed) {
						//	first detected change since last trigger
						self.changed = true;

						//	do not trigger immediately, reset the last trigger time
						self.lastTrigger = new Date();
						self.lastTrigger.setMilliseconds(0 - self.options.checkDelay);
						return;
					}
				}
			}

			if (!self.changed) {
				//	value has not changed since callback was last triggered, do nothing
				return;
			}

			var now = new Date();

			var trigger = false;

			switch (self.options.type) {
				case 'once':
					//	observer of type 'once' will only trigger once per series of changes, n msec after the most recent change
					if (now - self.lastChange > self.options.delay){
						trigger = true;
					}
					break;

				case 'constant':
					//	observer of type 'constant' will trigger every n msec while changes exist
					if (now - self.lastTrigger > self.options.delay) {
						trigger = true;
					}
					break;

				default:
					throw new Error('jQuery.observe: "'+ self.options.type +'" is not a valid observer type');
					break;
			}

			if (!trigger) {
				return;
			}

			self.lastTrigger = new Date();
			self.changed = false;
			self.options.callback();
		};

		if (typeof callback == 'function') {
			options.callback = callback;
			delete callback;
		} else if (typeof options == 'function') {
			//	first argument is actually a callback and no options were provided, adjust the arguments
			var options = { callback: options };
		}

		self.options = $.extend({
			type: 'once',
			delay: 1000,
			checkDelay: 100,
			callback: function(){},
			start: true
		}, options);

		self.nodes = $(this);

		self.timeout = null;
		self.interval = null;

		if (self.options.start) {
			self.start();
		}

		return $(this);
	};
})(jQuery);
