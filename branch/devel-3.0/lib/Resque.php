<?php

class Resque
{
	const VERSION = '1.0';

	public static $redis = null;

	public static function setBackend($server)
	{
		list($host, $port) = explode(':', $server);

		require_once dirname(__FILE__).'/Resque/Redis.php';
		self::$redis = new Resque_Redis($host, $port);
	}

	public static function redis()
	{
		if(is_null(self::$redis)) {
			self::setBackend('localhost:6379');
		}

		return self::$redis;
	}

	/**
	 * Push a job to the end of a specific queue. If the queue does not
	 * exist, then create it as well.
	 *
	 * @param string $queue The name of the queue to add the job to.
	 * @param object $item Job description as an object to be JSON encoded.
	 */
	public static function push($queue, $item)
	{
		self::redis()->sadd('queues', $queue);
		self::redis()->rpush('queue:'.$queue, json_encode($item));
	}

	public static function pop($queue)
	{
		$item = self::redis()->lpop('queue:'.$queue);
		if(!$item) {
			return;
		}

		return json_decode($item);
	}

	public static function size($queue)
	{
		return self::redis()->llen('queue:'.$queue);
	}

	public static function enqueue($queue, $class, $args=array(), $trackStatus = false)
	{
		require_once dirname(__FILE__).'/Resque/Job.php';
		return Resque_Job::create($queue, $class, $args, $trackStatus);
	}

	public static function reserve($queue)
	{
		require_once dirname(__FILE__).'/Resque/Job.php';
		return Resque_Job::reserve($queue);
	}

	public static function queues()
	{
		$queues = self::redis()->smembers('queues');
		if(!is_array($queues)) {
			$queues = array();
		}
		return $queues;
	}
}
