/**
 * AjaxDataProvider - Wrapper for AJAX server data that allows for queueing of requests for the same data
 *
 * @version 1.0.0
 * @author Gwilym Evans <@interspire.com>
 * @date 2010-04-01
 * @copyright Copyright © 2010 Interspire Pty Ltd - All Rights Reserved.
 */
/**
 * Changelog
 *
 * 2010-04-01 1.0.0
 * - Initial release
 */
;(function($){
/**
 * Note: the provided options are also sent to jQuery.ajax so the options object here is also used to define ajax request type, url and data, as per jQuery.ajax specifications. However, event handlers such as complete, success and error are ignored, as they're handled internally.
 *
 * @constructor
 * @param {Object} options:result If the results are already available when this object is created, provide them here and they will be pre-cached. This is useful if you wish to code around an AjaxDataProvider-like interface, for situations where the data may or may not be available on page load, also allowing you to later flush preloaded to re-fetch it if needed. The data can be in any format, except null will indicate that no data has been received.
 * @param {Function} options:process An optional function to call when the data has been received from the server and you want it manipulated or checked before it's cached and handed over to success handlers. Useful for translating raw JSON data into local classes, for instance. If this is not provided, the server data will be sent directly to success handlers. The callback will be provided one parameter; the data returned from the server. If the callback returns null or undefined, this will indicate failure and error handlers will be called instead of success handlers. Otherwise, any returned value will be cached and supplied to success handlers.
 * @param {Function} options:flush An option function to call when data inside this provider is flushed, can be useful if elements need to be cleared if local data is arbitrarily removed.
 */
AjaxDataProvider = function (options) {
	var self = this;

	self.options = $.extend({}, options);

	if (typeof self.options.process != 'function') {
		self.options.process = false;
	}

	var _flushQueues = function () {
		self.successQueue = [];
		self.errorQueue = [];
	};

	/**
	 * This function (re-)initialises the AjaxDataProvider, clearing any cached data so it can contact the server again on the next call to get()
	 */
	self.flush = function () {
		_flushQueues();
		self.busy = false;
		self.result = null;
		if (typeof self.options.flush == 'function') {
			self.options.flush();
		}
	};

	self.flush();

	if (typeof self.options.result != 'undefined') {
		self.result = self.options.result;
	}

	/**
	 * This function sends the actual AJAX request to the server and handles the response
	 */
	var _dispatch = function () {
		self.busy = true;

		// inherit options from original object creation; but some will be overridden completely below
		var options = $.extend({}, self.options);

		// success handler for internal ajax request
		options.success = function (data) {
			if (self.options.process) {
				data = self.options.process(data);
				if (typeof data == 'undefined') {
					data = null;
				}
			}
			self.result = data;

			var queue;
			if (data === null) {
				queue = self.errorQueue;
			} else {
				queue = self.successQueue;
			}

			for (var i = 0; i < queue.length; i++) {
				queue[i](data, true);
			}

			self.busy = false;
			_flushQueues();
		};

		// error handler for internal ajax request
		options.error = function () {
			var queue = self.errorQueue;
			for (var i = 0; i < queue.length; i++) {
				queue[i]();
			}

			self.busy = false;
			_flushQueues();
		};

		// fire the actual ajax request
		$.ajax(options);
	};

	/**
	 * This function handles the retrieving of data from the server and dispatching to success handlers when data is received.
	 *
	 * @param {Function} success A function to call when the data has been returned and is available. The function will be sent two parameters; the data (the format of which varies, as determined by the process callback), and a boolean that indicates whether the response was delayed or immediate
	 * @param {Function} options:error A function to call when retrieving the data failed, or if the process callback indicates failure by returning false
	 * @param {Function} options:loading A function to call when the AJAX request is created, which is useful for indicating in the browser that values are being requested (a loading indicator)
	 * @returns One of AjaxDataProvider.RESULT_ values; IMMEDIATE if the result was immediately returned, AJAX if the call resulted in creating an AJAX request, or QUEUED if the call requires an already-created AJAX request to finish
	 * @type Number
	 */
	self.get = function (success, options) {
		if (typeof success == 'undefined') {
			var success = false;
		}

		options = $.extend({}, options);

		if (self.result !== null) {
			// server response has already been received and stored; immediately call the success callback and return
			if (success) {
				success(self.result, false);
			}
			return AjaxDataProvider.RESULT_IMMEDIATE;
		}

		if (typeof options.loading == 'function') {
			// whether we're going into a queue or not, call the provided loading callback
			options.loading();
		}

		// add the provided success/error callbacks to our queues
		if (success) {
			self.successQueue.push(success);
		}

		if (typeof options.error == 'function') {
			self.errorQueue.push(options.error);
		}

		if (self.busy) {
			// alread waiting on server response
			return AjaxDataProvider.RESULT_QUEUED;
		}

		_dispatch();
		return AjaxDataProvider.RESULT_AJAX;
	};
};

AjaxDataProvider.RESULT_IMMEDIATE = 0;
AjaxDataProvider.RESULT_AJAX = 1;
AjaxDataProvider.RESULT_QUEUED = 2;

})(jQuery);
