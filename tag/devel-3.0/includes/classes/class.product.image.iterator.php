<?php

/**
* Image iterator class
* Can be used in a loop to iterate over all images in the database and using the product image class for each element.
*
* @example
*
* $imageIterator = new ISC_PRODUCT_IMAGE_ITERATOR;
* foreach($imageIterator as $imageId => $image) {
* 	// $image is an ISC_PRODUCT_IMAGE object
* }
*
* The default SQL selects all images, this can be changed by passing in SQL to the constructor:
*
* $imageIterator = new ISC_PRODUCT_IMAGE_ITERATOR('SELECT * from etc...');
*/

class ISC_PRODUCT_IMAGE_ITERATOR implements Iterator, Countable {
	/**
	* The database class
	*
	* @var MySQLDb
	*/
	private $db;

	/**
	* The current database resource that is being used to retrieve image information from the database
	*
	* @var Resource
	*/
	private $result;

	/**
	* The current SQL query that is being used by the class when retrieving product images
	*
	* @var string
	*/
	private $sql;

	/**
	* This is the current instance of the ISC_PRODUCT_IMAGE class with the current row from the database loaded in
	*
	* @var ISC_PRODUCT_IMAGE
	*/
	private $currentRow;

	/**
	* This is the total number of rows to expect from the database query
	*
	* @var integer
	*/
	private $totalRows = 0;

	/**
	* This is the number of the current row, used to help determine if there is another result to be pulled from the database or not
	*
	* @var integer
	* @see valid
	*/
	private $rowNumber = 0;

	/**
	* The constructor function which takes in an SQL query (or defaults to a select all from database) and calls the rewind() function to prep for looping
	*
	* @param string $sql (Optional) The SQL query used to retrieve product image rows from the database. If not used all rows will be selected from the database
	* @return ISC_PRODUCT_IMAGE_ITERATOR
	*/

	public function __construct($sql='')
	{
		if(empty($sql)) {
			$this->sql = ISC_PRODUCT_IMAGE::generateGetAllProductImagesFromDatabaseSql();
		} else {
			$this->sql = $sql;
		}

		$this->db = &$GLOBALS['ISC_CLASS_DB'];
		$this->rewind();
	}

	public function __destruct()
	{
		if ($this->result) {
			// free any stored mysql result
			@$this->db->FreeResult($this->result);
		}
	}

	/**
	* This function preps the class for looping by using the loaded SQL query on the database and saving the returned resource. It then loads the first row by calling next()
	* Implemented from as a requirement of the iterator interface.
	*
	* @see next
	*/

	public function rewind()
	{
		$this->result = $this->db->Query($this->sql);
		$this->totalRows = $this->db->CountResult($this->result);
		$this->rowNumber = 0;
		$this->next();

		if (!$this->result) {
			throw new ISC_PRODUCT_IMAGE_DBERROR_EXCEPTION(sprintf(GetLang('ProductImageDatabaseError'), __CLASS__, __METHOD__, $db->GetErrorMsg()));
		}
	}

	/**
	* This function returns the value of the current row.
	* Implemented from as a requirement of the iterator interface.
	*
	* @return ISC_PRODUCT_IMAGE
	*/
	public function current()
	{
		return $this->currentRow;
	}

	/**
	* This function returns the key of the current row. The key is the productImageId column from the database.
	* Implemented from as a requirement of the iterator interface.
	*
	* @return integer
	*/
	public function key()
	{
		return $this->currentRow->getProductImageId();
	}

	/**
	* This function loads the next row from the database and populates an instance of the ISC_PRODUCT_IMAGE class with the returned table row. This is then set as the current object for the loop.
	* Implemented from as a requirement of the iterator interface.
	*
	* @return void
	* @see currentRow
	* @see rowNumber
	*/

	public function next()
	{
		++$this->rowNumber;

		$row = $this->db->Fetch($this->result);

		if(!$row) {
			$this->currentRow = false;
			return false;
		}

		$this->currentRow = new ISC_PRODUCT_IMAGE();
		$this->currentRow->populateFromDatabaseRow($row);
	}

	/**
	* When looping over this iterator class, this function determines when the loop should stop (when there are no more database rows to be returned).
	* Implemented from as a requirement of the iterator interface.
	* @return boolean TRUE if there are more rows to work with (i.e. continue the loop), FALSE if there are no more rows (i.e. break the loop)
	*/

	public function valid()
	{
		if($this->rowNumber <= $this->totalRows) {
			return true;
		}
		return false;
	}

	/**
	* Gets the amount of images in the iterator. This function will hook php's count() function.
	* @return int
	*/
	public function count()
	{
		return $this->totalRows;
	}
}

