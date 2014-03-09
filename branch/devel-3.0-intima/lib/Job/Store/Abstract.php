<?php

abstract class Job_Store_Abstract
{
	protected $args;

	public function __construct($args)
	{
		$this->args = $args;
	}

	public function setUp()
	{

	}

	abstract public function perform();

	public function tearDown()
	{

	}
}
