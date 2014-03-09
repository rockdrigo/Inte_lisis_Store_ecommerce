<?php

/**
 * Extends SplFileObject to extend and patch base functionality.
 *
 * @package Interspire
 * @subpackage File
 */
class Interspire_File extends SplFileObject
{
	/**
	 * @var int|false
	 */
	protected $start = false;

	/**
	 * @var int|false
	 */
	protected $stop = false;

	/**
	 * The real path that is stored upon instantiation so that it is available
	 * at any time.
	 *
	 * @var string
	 */
	protected $realpath;

	/**
	 * Construct a new file object and set defaults.
	 *
	 * @param string $file
	 * @param string $mode
	 * @param bool $include
	 * @param stream $context
	 * @return Interspire_File
	 */
	public function __construct($file, $mode = 'r', $include = false, $context = null)
	{
		if ($context) {
			parent::__construct($file, $mode, $include, $context);
		}
		else {
			parent::__construct($file, $mode, $include);
		}

		if (method_exists('SplFileObject', 'getRealPath')) {
			// getRealPath is PHP >= 5.2.2
			$this->realpath = parent::getRealPath();
		} else {
			$this->realpath = realpath($file);
		}
	}

	/**
	 * A [hacked] way to use fputcsv in object context.
	 *
	 * @param array $row
	 * @return Interspire_File
	 */
	public function fputcsv($row)
	{
		$pathname = 'php://temp/' . md5(microtime());
		$file     = fopen($pathname, 'w+');
		$csvInfo  = $this->getCsvControl();

		// if php 5.3.0 make use of the escape parameter
		if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
			fputcsv(
				$file,
				$row,
				$csvInfo[0],
				$csvInfo[1],
				$csvInfo[2]
			);
		}
		// if not, don't use it
		else {
			fputcsv(
				$file,
				$row,
				$csvInfo[0],
				$csvInfo[1]
			);
		}

		rewind($file);

		// write temporary file to physical file
		while ($line = fgets($file)) {
			$this->fwrite($line);
		}

		// close pointer
		fclose($file);

		return $this;
	}

	/**
	 * Overloads the parent method so that  the real path is always returned.
	 *
	 * This is useful for example when you call this function in a __destruct
	 * method which was invoked through the termination of a script. Normally
	 * getRealPath would return false since by the time it is called, you are
	 * no longer in the same working directory. Since this is recorded at the
	 * time of instantiation, it will work at script termination.
	 *
	 * @return string|false
	 */
	public function getRealPath()
	{
		return $this->realpath;
	}

	/**
	 * Rewinds the internal file pointer.
	 *
	 * @return array
	 */
	public function rewind()
	{
		// if a starting point is set, set the position
		if ($this->start) {
			$this->seek($this->start);
		}
		// otherwise rewind to the beginning
		else {
			parent::rewind();
		}

		return $this;
	}

	/**
	 * Returns whether or not the current iteration is valid.
	 *
	 * @return bool
	 */
	public function valid()
	{
		// if a stopping point is set, stop and reset
		if ($this->stop && $this->key() > $this->stop) {
			return false;
		}

		return parent::valid();
	}

	/**
	 * Sets the starting point when iterating. Auto-cleared at end of iteration.
	 *
	 * @param int $pos
	 * @return Interspire_Csv
	 */
	public function start($pos = false)
	{
		$this->start = (int) $pos;

		return $this;
	}

	/**
	 * Sets an ending point when iterating. Auto-cleared at end of iteration.
	 *
	 * @param int $pos
	 * @return Interspire_Csv
	 */
	public function stop($pos = false)
	{
		$this->stop = (int) $pos;

		return $this;
	}

	/**
	 * Retrieves a portion of a file split into lines.
	 *
	 * @param int $page A numeric string or integer of which page to retrieve.
	 * @param int $limit A numeric string or integer of the number of results
	 * to retrieve on the given page.
	 * @return array
	 */
	public function page($page = 1, $limit = 10)
	{
		$start = ($page - 1) * $limit;
		$stop  = $start + $limit - 1;

		return $this->start($start)->stop($stop);
	}

	protected $_csvControlDelimiter = ',';
	protected $_csvControlEnclosure = '"';
	protected $_csvControlEscape = "\\";

	/**
	* This method is only present in PHP 5.2, so we need one for < 5.2
	*/
	public function setCsvControl($delimiter = ',', $enclosure = '"', $escape = "\\")
	{
		$this->_csvControlDelimiter = $delimiter;
		$this->_csvControlEnclosure = $enclosure;
		$this->_csvControlEscape = $escape;
	}

	/**
	* This method is only present in PHP 5.2, so we need one for < 5.2
	*/
	public function getCsvControl()
	{
		// for php < 5.2
		return array(
			$this->_csvControlDelimiter,
			$this->_csvControlEnclosure,
		);
	}
}