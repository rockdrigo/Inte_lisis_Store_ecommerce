<?php

/**
* Generic, basic event bind/trigger class. Only works within the scope of a script execution, does not persist event bindings.
*
* Think of this as being similar to jquery's bind and trigger: bindings must be set before triggers are fired, event names and data are arbitrary.
*
* When called, callbacks are given one argument: an instance of Interspire_Event
*
* The static trigger() method returns this same instance of Interspire_Event, which can be queried for preventDefault status, if necessary.
*
* Basic binds:
* Interspire_Event::bind('some_event_name', array($obj, 'method'));
* Interspire_Event::bind('some_event_name', array('ClassName', 'method'), $contextData);
*
* Basic triggers:
* $result = Interspire_Event::trigger('some_event_name', $eventData);
*/
class Interspire_Event
{
	/**
	* Storage for event name -> callback relationships.
	*
	* Storage format is:
	*
	* $_bindings = array(
	* 	eventname => array(
	* 		0 => array(callback, context),
	* 		1 => array(callback, context),
	* 		...
	* 	),
	* 	...,
	* );
	*
	* @var array
	*/
	protected static $_bindings = array();

	/**
	* Determines if the named event exists. If an event has no bindings, it is deemed non-existant.
	*
	* @param string $event event name
	* @return bool true if event exists and has bindings, otherwise false
	*/
	public static function exists($event)
	{
		return isset(self::$_bindings[$event]) && (bool)count(self::$_bindings[$event]);
	}

	/**
	* Bind the given callback to the named event
	*
	* Notes:
	* - To avoid event name conflicts, you should namespace your event names where possible, such as module_blah_something, or the name of the class that triggers the specific event
	* - Duplicates are not checked, it's entirely possible to bind the same callback to an event multiple times so you need to manage this
	* - Callbacks are fired in the order they are bound
	* - If a callback returns false it will prevent further callbacks from executing (this does not include returning nothing/void/undefined/0/blank string)
	*
	* @param string $event event name
	* @param mixed $callback callback as defined by PHP - either a function name, array(class name, static method) or array(instance, method) - the first and only parameter sent to all callbacks is an instance of Interspire_Event
	* @param mixed $context optional data specific to this binding which will be assigned to the ->context property of the Interspire_Event instance which is sent to the callback
	* @return void
	*/
	public static function bind($event, $callback, $context = null)
	{
		self::$_bindings[$event][] = array(
			'callback' => $callback,
			'context' => $context,
		);
	}

	/**
	* Determines if the specified callback is already bound to the named event. This is a callback check only and will return true even if the provided $context is different.
	*
	* @param string $event event name
	* @param mixed $callback callback as defined by PHP - either a function name, array(class name, static method) or array(instance, method)
	*/
	public static function bound($event, $callback)
	{
		if (!self::exists($event)) {
			return false;
		}

		foreach (self::$_bindings[$event] as $index => $binding) {
			if ($binding['callback'] === $callback) {
				return true;
			}
		}

		return false;
	}

	/**
	* Unbinds a callback from the named event
	*
	* @param string $event event name
	* @param mixed $callback callback as defined by PHP - either a function name, array(class name, static method) or array(instance, method)
	*/
	public static function unbind($event, $callback)
	{
		if (!self::exists($event)) {
			return;
		}

		foreach (self::$_bindings[$event] as $index => $binding) {
			if ($binding['callback'] === $callback) {
				array_splice(self::$_bindings[$event], $index, 1);
			}
		}
	}

	/**
	* Runs all callbacks that are bound to the named event, if any, until a callback returns false.
	*
	* Notes:
	* - To avoid event name conflicts, you should namespace your event names where possible, such as module_blah_something, or the name of the class that triggers the specific event
	*
	* @param string $event
	* @param mixed $data to be assigned to ->data property of an instance of Interspire_Event, which will be sent to each binding
	* @return Interspire_Event
	*/
	public static function trigger($eventName, $data = null)
	{
		$event = new self($eventName);
		$event->data = $data;

		if (!self::exists($eventName)) {
			return $event;
		}

		foreach (self::$_bindings[$eventName] as $index => $binding) {
			if (!is_callable($binding['callback'])) {
				// skip invalid callbacks
				continue;
			}

			$event->context = $binding['context'];

			$result = call_user_func($binding['callback'], $event);

			if ($result === false) {
				$event->stopPropagation();
				$event->preventDefault();
			}

			if ($event->isPropagationStopped()) {
				break;
			}
		}

		return $event;
	}

	/**
	* Remove an event completely, unbinding all callbacks
	*
	* @param string $event
	*/
	public static function remove($event)
	{
		if (self::exists($event)) {
			unset(self::$_bindings[$event]);
		}
	}

	/**
	* Storage of stopPropagation flag
	*
	* @var bool
	*/
	protected $_stopPropagation = false;

	/**
	* Storage of preventDefault flag
	*
	* @var bool
	*/
	protected $_preventDefault = false;

	/**
	* Name of event being triggered
	*
	* @var string
	*/
	public $eventName;

	/**
	* Data passed in at trigger point
	*
	* @var string
	*/
	public $data;

	/**
	* Data passed in at binding point
	*
	* @var mixed
	*/
	public $context;

	/**
	* @param string $eventName
	* @return Interspire_Event
	*/
	public function __construct($eventName)
	{
		$this->eventName = $eventName;
	}

	/**
	* Sets stop propagation flag to tru
	*
	* @return void
	*/
	public function stopPropagation()
	{
		$this->_stopPropagation = true;
	}

	/**
	* Returns stop propagation flag
	*
	* @return bool
	*/
	public function isPropagationStopped()
	{
		return $this->_stopPropagation;
	}

	/**
	* Sets prevent default flag
	*
	* @return void
	*/
	public function preventDefault()
	{
		$this->_preventDefault = true;
	}

	/**
	* Returns prevent default flag
	*
	* @return bool
	*/
	public function isDefaultPrevented()
	{
		return $this->_preventDefault;
	}
}
