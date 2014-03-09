(function($){

/**
* Internal task manager polling script
*
* @constructor
*/
TaskManager = function (options) {
	var self = this;

	self.options = $.extend({
		delay: 1000,		// after page load, wait this long to first request, and when there is another job waiting, wait this long before starting it (msec)
		frequency: 15000,	// when no jobs are waiting, wait this long between automatic checks, or if zero will only process one loop
		variance: 500,		// when determining queue timers, vary +/- by this amount
		url: '',			// url to task manager trigger on server
		start: true
	}, options);

	var _timeout = null;

	var _busy = false;

	var _vary = function (delay) {
		return delay + (Math.random() * self.options.variance * 2 - self.options.variance);
	};

	var _requeue = function (delay) {
		if (self.busy()) {
			// don't attempt to requeue while waiting on a response
			return;
		}
		delay = _vary(delay);
		_timeout = window.setTimeout(_tick, delay);
	};

	var _tickAjaxComplete = function () {

	};

	var _tickAjaxSuccess = function (data) {
		_busy = false;
		if (data.remaining) {
			_requeue(self.options.delay);
		} else {
			if (self.options.frequency) {
				_requeue(self.options.frequency);
			}
		}
	};

	var _tickAjaxError = function () {
		_busy = false;
		if (self.options.frequency) {
			_requeue(self.options.frequency);
		}
	};

	var _tick = function () {
		if (self.busy()) {
			// don't attempt to ajax while waiting on a response
			return;
		}

		_busy = true;
		$.ajax({
			url: self.options.url,
			cache: false,
			datatype: 'json',
			complete: _tickAjaxComplete,
			success: _tickAjaxSuccess,
			error: _tickAjaxError,
			global: false
		});
	};

	self.running = function () {
		return _timeout !== null;
	}

	self.busy = function () {
		return !!_busy;
	};

	self.start = function () {
		if (self.running()) {
			return true;
		}
		if (!self.options.url) {
			return false;
		}
		_requeue(self.options.delay);
		return true;
	};

	self.stop = function () {
		if (!self.running()) {
			return;
		}
		window.clearTimeout(_timeout);
	};

	self.check = function () {
		if (self.start()) {
			_tick();
		}
	};

	if (self.options.start) {
		self.start();
	}
};

})(jQuery);
