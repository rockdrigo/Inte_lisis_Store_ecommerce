<?php

/**
* This class wraps a database query in an iterator. This allows for existing code that relies on an entire dataset being fetched into an array to be easily converted as it allows the results of a query to be accessed one row at a time using a foreach loop or through any other iterator usage.
*/
class Interspire_Db_QueryIterator implements Iterator
{
	/** @var Db */
	protected $_db;

	/** @var string sql query to execute */
	protected $_query;

	/** @var mixed database query result; either false or associative array of column => value */
	protected $_result;

	/** @var int row counter to be returned by key() */
	protected $_counter;

	/**
	* @param Db $db
	* @param string $query
	*/
	public function __construct (Db $db, $query)
	{
		$this->_db = $db;
		$this->_query = $query;
	}

	/**
	* Returns the current row data
	*
	* @return mixed
	*/
	public function current ()
	{
		return $this->_current;
	}

	/**
	* Returns the current row number
	*
	* @return int
	*/
	public function key ()
	{
		return $this->_counter;
	}

	/**
	* Moves the iterator to the next record, if any
	*
	* @return void
	*/
	public function next ()
	{
		$this->_current = $this->_fetch();
		if ($this->_current) {
			$this->_counter++;
		}
	}

	/**
	* Moves the iterator to the first record
	*
	* @return void
	*/
	public function rewind ()
	{
		$this->_result = null;
		$this->_counter = 0;
		$this->_current = $this->_fetch();
	}

	/**
	* Determines if the current record is valid
	*
	* @return bool
	*/
	public function valid ()
	{
		return is_array($this->_current);
	}

	/**
	* Executes the query for this iterator and stores the result in $this->_result.
	*
	* Will not re-execute if $this->_result is non-null, in this case it will return the current result resource.
	*
	* @param bool $force set to true to ignore any cached result
	* @return mixed false if the query failed otherwise returns php result resource
	*/
	protected function _execute ($force = false)
	{
		if ($this->_result !== null && !$force) {
			return $this->_result;
		}
		$this->_result = $this->_db->Query($this->_query);
		return $this->_result;
	}

	/**
	* Gets the next record from the query result
	*
	* @return mixed array of record data or false if there are no more records (also returns false if the query failed)
	*/
	protected function _fetch ()
	{
		if (!$this->_execute()) {
			return false;
		}
		return $this->_db->Fetch($this->_result);
	}
}
