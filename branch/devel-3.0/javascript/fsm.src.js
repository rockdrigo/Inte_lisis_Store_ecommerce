/**
 * Interspire_FSM - A 'simple' Finite State Machine implementation in JavaScript
 *
 * Requires jQuery (tested on 1.4.2)
 *
 * @version 1.0.1
 * @author Gwilym Evans <@interspire.com>
 * @date 2010-05-26
 * @copyright Copyright © 2010 Interspire Pty Ltd - All Rights Reserved.
 */
/**
 * Changelog
 *
 * 2010-05-26 1.0.1
 * - Allow transition 'to' state to be provided as a custom function which returns the real state, allowing for dynamic transition targets
 * - Fixed: starting a machine with no payload would not clear the previous run's payload
 *
 * 2010-05-12 1.0.0
 * - Initial version
 */
(function($){

Interspire_FSM = function () {
	var self = this;
	var $$ = $(self);

	var _running = false;
	var _initial;
	var _state;

	self.payload = {};
	self.states = {};

	self.state = function (name) {
		if (typeof name == 'undefined') {
			return _state;
		}
		if (typeof self.states[name] == 'undefined') {
			self.states[name] = new Interspire_FSM.State(self, name);
		}
		return self.states[name];
	};

	self.initial = function (state) {
		if (typeof state == 'undefined') {
			return _initial;
		}

		if (typeof state == 'object') {
			_initial = state;
		} else {
			_initial = self.state(state);
		}
		return this;
	};

	self.start = function (payload) {
		if (_running) {
			return;
		}
		_running = true;

		if (typeof payload != 'undefined') {
			self.payload = payload;
		} else {
			self.payload = {};
		}
		_state = _initial;

		var event = jQuery.Event('machine_start');
		$$.trigger(event, self);
		if (event.isDefaultPrevented()) {
			self.finish();
			return;
		}
		_state.enter();
	};

	self.finish = function () {
		if (!_running) {
			return;
		}
		_running = false;

		$$.trigger('machine_finish', self);
	};

	self.can = function (id) {
		if (!_running) {
			false;
		}

		for (transition in _state.transitions) {
			if (id == transition) {
				transition = _state.transitions[id];
				if (transition.test() !== false) {
					return true;
				}
				return false;
			}
		}
		return false;
	};

	self.transition = function (id) {
		if (!_running) {
			return;
		}

		var transition;
		for (transition in _state.transitions) {
			if (id == transition) {
				transition = _state.transitions[id];
				if (transition.test() !== false) {
					if (transition.execute() !== false) {
						_state.exit();
						_state = transition.to();
						_state.enter();
					}
				}
				break;
			}
		}
	};

	self.refresh = function () {
		_state.refresh();
	};

	self.running = function () {
		return _running;
	};
};

Interspire_FSM.State = function (fsm, name) {
	var self = this;
	var $$ = $(self);

	/**
	* storage for whether transitions can be executed, used to trigger the fsm's transitions_changed event - this is kept up todate by reset() and refresh()
	*/
	var _can;

	self.name = name;
	self.machine = fsm;

	self.transitions = {};
	self.transition = function (id, to) {
		return self.transitions[id] = new Interspire_FSM.Transition(self, id, to);
	};

	self.enter = function (callback) {
		if (typeof callback == 'function') {
			$$.bind('state_enter', callback);
			return this;
		}

		self.reset();
		self.refresh();
		$(self.machine).trigger('state_enter', self);
		$$.trigger('state_enter', self);
	};

	self.exit = function (callback) {
		if (typeof callback == 'function') {
			$$.bind('state_exit', callback);
			return this;
		}

		$(self.machine).trigger('state_exit', self);
		$$.trigger('state_exit', self);
	};

	self.initial = function () {
		self.machine.initial(self);
		return this;
	};

	self.reset = function () {
		_can = {};
	};

	self.refresh = function () {
		var _new_can = {};
		var id;
		var changed = false;
		for (id in self.transitions) {
			_new_can[id] = self.transitions[id].test();

			if (_new_can[id] != _can[id]) {
				changed = true;
			}
		}

		if (changed) {
			$(self.machine).trigger('transitions_change', self.machine);
		}
	};
};

/**
* Create a transition from one state to another
*
* @param Interspire_FSM.State from The state this transition links from
* @param String id The name of this transition
* @param String|Interspire_FSM.State|function The state this transition links to either as a string name, an Interspire_FSM.State instance or a function that returns an Interspire_FSM.State instance
*/
Interspire_FSM.Transition = function (from, id, to) {
	var self = this;
	var $$ = $(self);

	self.id = id;
	self.from = from;

	var _to;

	// by default, calling to() returns the value of _to but this can be overwritten by providing a function in the constructor
	self.to = function () {
		return _to;
	};

	if (typeof to == 'function') {
		// custom to() function provided, replace the internal one, it's now the developer's responsibility to return a valid `to` refrence
		self.to = to;
	} else if (to instanceof Interspire_FSM.State) {
		_to = to;
	} else {
		_to = self.from.machine.state(to);
	}

	var _test;
	self.test = function (callback) {
		if (typeof callback == 'function') {
			// set the test callback
			_test = callback;
			return this;
		} else {
			// actually test to see if the transition is available
			if (typeof _test == 'function') {
				// a test is defined
				return _test();
			} else {
				// no test defined, assume transition is available
				return true;
			}
		}
	};

	self.poll = function (selector, event) {
		$(selector).live(event, self.from.refresh);
		return this;
	};

	self.execute = function (callback) {
		if (typeof callback == 'function') {
			$$.bind('transition_execute', callback)
			return this;
		}

		var event = jQuery.Event('transition_execute');
//		console.debug('attempting transition ' + self.from.name + ':' + self.id + ' -> ' + self.to.name);
		$$.trigger(event, self);
		if (event.isDefaultPrevented()) {
//			console.debug('transition halted by event binding');
			return false;
		}
		return true;
	};
};

})(jQuery);
