<?php

/**
* This class is a container for shopping-cart-specific date/time methods.
*/
class Store_DateTime extends Interspire_DateTime
{
	// keep these in a numerical order where smaller units are lower numbers
	const DURATION_SECONDS = 0;
	const DURATION_MINUTES = 1;
	const DURATION_HOURS = 2;
	const DURATION_DAYS = 3;

	/**
	* Given a time-duration in seconds, returns a language-based string representing the duration as text.
	*
	* @param int $seconds
	* @param int $minimumResolution one of self::DURATION_? constants (DURATION_SECONDS by default)
	* @param int $maximumResolution one of self::DURATION_? constants (DURATION_DAYS by default)
	* @return string e.g. '1 day, 2 hours, 30 minutes' or 'less than 1 minute'
	*/
	public static function duration($seconds, $minimumResolution = self::DURATION_SECONDS, $maximumResolution = self::DURATION_DAYS)
	{
		$return = array();

		$seconds = (int)$seconds;
		$days = 0;
		$hours = 0;
		$minutes = 0;

		if ($minimumResolution <= self::DURATION_DAYS && $maximumResolution >= self::DURATION_DAYS && $seconds > self::SECONDS_PER_DAY) {
			$units = floor($seconds / self::SECONDS_PER_DAY);
			$seconds = $seconds % self::SECONDS_PER_DAY;
			if ($units == 1) {
				$return[] = $units . ' ' . GetLang('day');
			} else {
				$return[] = $units . ' ' . GetLang('days');
			}
		}

		if ($minimumResolution <= self::DURATION_HOURS && $maximumResolution >= self::DURATION_HOURS && $seconds > self::SECONDS_PER_HOUR) {
			$units = floor($seconds / self::SECONDS_PER_HOUR);
			$seconds = $seconds % self::SECONDS_PER_HOUR;
			if ($units == 1) {
				$return[] = $units . ' ' . GetLang('hour');
			} else {
				$return[] = $units . ' ' . GetLang('hours');
			}
		}

		if ($minimumResolution <= self::DURATION_MINUTES && $maximumResolution >= self::DURATION_MINUTES && $seconds > self::SECONDS_PER_MINUTE) {
			$units = floor($seconds / self::SECONDS_PER_MINUTE);
			$seconds = $seconds % self::SECONDS_PER_MINUTE;
			if ($units == 1) {
				$return[] = $units . ' ' . GetLang('minute');
			} else {
				$return[] = $units . ' ' . GetLang('minutes');
			}
		}

		if ($minimumResolution <= self::DURATION_SECONDS && $maximumResolution >= self::DURATION_SECONDS && $seconds) {
			if ($seconds == 1) {
				$return[] = $seconds . ' ' . GetLang('second');
			} else {
				$return[] = $seconds . ' ' . GetLang('seconds');
			}
		}

		if (!empty($return)) {
			return implode(', ', $return);
		}

		switch ($minimumResolution) {
			case self::DURATION_SECONDS:
				$interval = GetLang('second');
				break;
			case self::DURATION_MINUTES:
				$interval = GetLang('minute');
				break;
			case self::DURATION_HOURS:
				$interval = GetLang('hour');
				break;
			case self::DURATION_DAYS:
				$interval = GetLang('day');
				break;
		}

		return GetLang('DurationLessThan', array(
			'interval' => $interval,
		));
	}

	/**
	 * Calculate and return a friendly displayable date such as "less than a minute ago"
	 * "x minutes ago", "Today at 6:00 PM" etc.
	 *
	 * @param string The UNIX timestamp to format.
	 * @param boolean True to include the time details, false if not.
	 * @return string The formatted date.
	 */
	public static function niceDate($timestamp, $includeTime=false)
	{
		$now = time();
		$difference = $now - $timestamp;
		$time = isc_date('h:i A', $timestamp);

		$timeDate = isc_date('Ymd', $timestamp);
		$todaysDate = isc_date('Ymd', $now);
		$yesterdaysDate = isc_date('Ymd', $now-86400);

		if($difference < 60) {
			return GetLang('LessThanAMinuteAgo');
		}
		else if($difference < 3600) {
			$minutes = ceil($difference/60);
			if($minutes == 1) {
				return GetLang('OneMinuteAgo');
			}
			else {
				return sprintf(GetLang('XMinutesAgo'), $minutes);
			}
		}
		else if($difference < 43200) {
			$hours = ceil($difference/3600);
			if($hours == 1) {
				return GetLang('OneHourAgo');
			}
			else {
				return sprintf(GetLang('XHoursAgo'), $hours);
			}
		}
		else if($timeDate == $todaysDate) {
			if($includeTime == true) {
				return sprintf(GetLang('TodayAt'), $time);
			}
			else {
				return GetLang('Today');
			}
		}
		else if($timeDate == $yesterdaysDate) {
			if($includeTime == true) {
				return sprintf(GetLang('YesterdayAt'), $time);
			}
			else {
				return GetLang('Yesterday');
			}
		}
		else {
			$date = CDate($timestamp);
			if($includeTime == true) {
				return sprintf(GetLang('OnDateAtTime'), $date, $time);
			}
			else {
				return sprintf(GetLang('OnDate'), $date);
			}
		}
	}

	/**
	* NiceTime
	*
	* Returns a formatted timestamp
	* @return string The formatted string
	* @param int The unix timestamp to format
	*/
	public static function niceTime($UnixTimestamp)
	{
		return isc_date('jS F Y H:i:s', $UnixTimestamp);
	}
}
