<?php

class Resque_Job
{
	/**
	 */
	public $queue;
	public $worker;
	public $payload;

	public function __construct($queue, $payload)
	{
		$this->queue = $queue;
		$this->payload = $payload;
	}

	/**
	 * Create a new job and save it to the specified queue.
	 *
	 * @param string $queue The name of the queue to place the job in.
	 * @param string|callback $method The name of the class that contains the code to execute the job, or an array containing a class name and method name
	 * @param array $args Any optional arguments that should be passed when the job is executed.
	 * @param boolean $monitor Set to true to be able to monitor the status of a job.
	 * @param string $include Path to a file to include before attempting to execute the callback
	 */
	public static function create($queue, $method, array $args=array(), $monitor = false, $include = '')
	{
		if (is_array($method)) {
			$class = $method[0];
			$method = $method[1];
		} else {
			$class = '';
		}

		$id = md5(uniqid('', true));

		$payload = array(
			'class' => $class,
			'method' => $method,
			'args' => $args,
			'id' => $id,
			'include' => $include,
		);

		Resque::push($queue, $payload);

		if($monitor) {
			Resque_Job_Status::create($id);
		}

		return $id;
	}

	/**
	 * Find the next available job from the specified queue and return an
	 * instance of Resque_Job for it.
	 *
	 * @param string $queue The name of the queue to check for a job in.
	 * @return null|object Null when there aren't any waiting jobs, instance of Resque_Job when a job was found.
	 */
	public static function reserve($queue)
	{
		$payload = Resque::pop($queue);
		if(!$payload) {
			return;
		}

		return new Resque_Job($queue, $payload);
	}

	/**
	 * Update the status of the current job.
	 *
	 * @param int $status Status constant from Resque_Job_Status indicating the current status of a job.
	 */
	public function updateStatus($status)
	{
		if(empty($this->payload->id)) {
			return;
		}

		$statusInstance = new Resque_Job_Status($this->payload->id);
		$statusInstance->update($status);
	}

	/**
	 * Return the status of the current job.
	 *
	 * @return int The status of the job as one of the Resque_Job_Status constants.
	 */
	public function getStatus()
	{
		$status = new Resque_Job_Status($this->payload->id);
		return $status->get();
	}

	/**
	 * Actually execute a job by calling the perform method on the class
	 * associated with the job with the supplied arguments.
	 *
	 * @throws Resque_Exception When the job's class could not be found or it does not contain a perform method.
	 */
	public function perform()
	{
		if(!class_exists($this->payload->class)) {
			throw new Resque_Exception('Could not find job class '.$this->payload->class.'.');
		}

		if(!method_exists($this->payload->class, $this->payload->method)) {
			throw new Resque_Exception('Job class '.$this->payload->class.' does not contain a '.$this->payload->method.' method.');
		}

		call_user_func_array(array($this->payload->class, $this->payload->method), $this->payload->args);
	}

	/**
	 * Mark the current job as having failed.
	 */
	public function fail($exception)
	{
		$this->updateStatus(Resque_Job_Status::STATUS_FAILED);
		require_once dirname(__FILE__).'/Failure.php';
		Resque_Failure::create(
			$this->payload,
			$exception,
			$this->worker,
			$this->queue
		);
	}

	/**
	 * Re-queue the current job.
	 */
	public function recreate()
	{
		$status = new Resque_Job_Status($id);
		$monitor = false;
		if($status->isTracking()) {
			$monitor = true;
		}

		self::create($this->queue, $this->payload->class, $this->payload->args, $monitor);
	}

	/**
	 * Generate a string representation used to describe the current job.
	 *
	 * @return string The string representation of the job.
	 */
	public function __toString()
	{
		$args = $this->payload->args;
		foreach($args as $k => $v) {
			if(is_object($v)) {
				$args[$k] = '{'.get_class($v).' - '.implode(',', get_object_vars($v)).'}';
			}
		}

		$name = array(
			'Job{'.$this->queue.'}'
		);
		if($this->payload->id) {
			$name[] = 'ID: '.$this->payload->id;
		}
		$name[] = $this->payload->class;
		$name[] = implode(',', $args);
		return '('.implode(' | ', $name).')';
	}
}
