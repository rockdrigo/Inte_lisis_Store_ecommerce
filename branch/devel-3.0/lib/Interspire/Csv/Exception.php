<?php

/**
 * The Interspire_Csv Exception class.
 * 
 * @package Interspire
 * @subpackage Csv
 * @subpackage Exception
 */
class Interspire_Csv_Exception extends Interspire_Exception
{
	/**
	 * Exception thrown when no filepath is provided when creating a new CSV
	 * document.
	 * 
	 * @var int
	 */
	const NO_FILEPATH = 1;
}