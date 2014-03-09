<?php

/**
* This class implements a very basic stack of timers, useful for nested profiling without needing to track vars yourself.
*
* The user of this class needs to be aware of the stack and be careful in its management. If you use it, always 'stop' your timers to clear the stack before some other code may use it.
*
* Basic usage:
* Interspire_Timer::start();
* ... do stuff ...
* Interspire_Timer::start();
* ... do stuff ...
* echo 'timer 2 took ' . Interspire_Timer::stop() . ' secs';
* ... do stuff ...
* echo 'timer 1 took ' . Interspire_Timer::stop() . ' secs';
*/
class Interspire_TimerStack
{
	protected static $_stack = array();

	/**
	* returns the current stack depth
	*
	* @return int the current stack depth
	*/
	public function depth()
	{
		return count(self::$_stack);
	}

	/**
	* start a timer and return the depth it was created at in the stack
	*
	* @return int timer stack depth
	*/
	public static function start()
	{
		$depth = self::depth();
		self::$_stack[] = microtime(true);
		return $depth;
	}

	/**
	* returns the elapsed time of the current timer or a timer at a specific stack level
	*
	* @return float time elapsed in seconds or false if no timers are active
	*/
	public static function elapsed($depth = null)
	{
		if (empty(self::$_stack)) {
			return false;
		}

		if ($depth === null) {
			return microtime(true) - end(self::$_stack);
		}

		return microtime(true) - self::$_stack[$depth];
	}

	/**
	* stops the current timer, removing it from the stack and returning the duration of the timer
	*
	* @return float time elapsed in seconds or false if no timers are active
	*/
	public static function stop()
	{
		if (empty(self::$_stack)) {
			return false;
		}

		return (microtime(true) - array_pop(self::$_stack));
	}
}
