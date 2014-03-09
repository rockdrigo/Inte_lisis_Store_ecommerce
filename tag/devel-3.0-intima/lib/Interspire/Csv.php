<?php

/**
 * Manages CSV files for both importing and exporting.
 *
 * In the future, one may consider extending Interspire_File as to directly
 * manipulate the temporary file instead of having to store it in a property
 * and duplicate functionality for certain operations. However, this seemed
 * the better approach since extending would inherit a lot of unecessary
 * overhead from Interspire_File as well as SplFileObject.
 *
 * @package Interspire
 * @subpackage Csv
 */
class Interspire_Csv implements Iterator, ArrayAccess
{
	/**
	 * The path of the file being edited.
	 *
	 * @var string
	 */
	protected $filepath = null;

	/**
	 * The temporary file containing the CSV data.
	 *
	 * @var stream resource
	 */
	protected $tmp;

	/**
	 * Keeps an array of modified rows.
	 *
	 * @var array
	 */
	protected $writeCache = array();

	/**
	 * The maximum number of cached rows to keep in both the retrieved cache and
	 * the modified cache.
	 *
	 * @var int
	 */
	protected $cacheSize = 1000;

	/**
	 * Creates a new CSV object from a string, file or array. The class then
	 * intelligently handles the input appropriately.
	 *
	 * @return Interspire_Csv_Exporter
	 */
	public function __construct($filepath = null)
	{
		// generate temporary name
		$tmpName = tempnam(ISC_BASE_PATH . '/cache', 'csv');

		// handle an array
		if (is_array($filepath)) {
			$this->tmp = new Interspire_File($tmpName, 'w+');

			foreach ($filepath as $key => $row) {
				$this[$key] = $row;
			}
		}
		// else if a file is provided, copy it over to the tmp file
		elseif (is_string($filepath) && is_file($filepath)) {
			$file = new Interspire_File($filepath);

			// overwrite temp file
			copy($file->getPathname(), $tmpName);

			// close file
			unset($file);

			// set the file path
			$this->filepath = $filepath;

			// now create/open temp file
			$this->tmp = new Interspire_File($tmpName, 'r+');
		}
		else {
			$this->tmp = new Interspire_File($tmpName, 'w+');
		}
	}

	/**
	 * Performs temporary file cleanup on object termination.
	 *
	 * @return void
	 */
	public function __destruct()
	{
		$path = $this->tmp->getRealPath();
		unset($this->tmp);

		if (is_file($path)) {
			unlink($path);
		}
	}

	/**
	 * Returns the contents of the current csv file.
	 */
	public function __toString()
	{
		$this->writeCache();

		return file_get_contents($this->tmp->getRealPath());
	}

	/**
	 * Sets the maximum size of the array that can be used for the write cache.
	 *
	 * @param int $size The maximum number of elements to cache before writing.
	 * @return Interspire_Csv
	 */
	public function setCacheSize($size)
	{
		$this->cacheSize = $size;

		return $this;
	}

	/**
	 * Saves the current array to a csv file.
	 *
	 * @param string $filepath The path of the file to save.
	 * @return bool
	 */
	public function save($filepath = null)
	{
		// if a file path isn't set, try and use the one being edited
		// IF one is being edited
		if (!$filepath) {
			$filepath = $this->filepath;
		}

		// if no filepath is still set, thrown an exception
		if (!$filepath) {
			throw new Interspire_Csv_Exception(
				'A file name must be provided when saving a CSV FILE'
				, Interspire_Csv_Exception::NO_FILEPATH
			);
		}

		// update the filepath
		$this->filepath = $filepath;

		// write temp cache
		$this->writeCache();

		// copy the temporary file to the saved file
		copy($this->tmp->getPathname(), $filepath);

		return $this;
	}

	/**
	 * Pushes the csv file to the browser as a file to download and exits.
	 *
	 * @return void
	 */
	public function push($asName = null)
	{
		$this->writeCache();

		if (!$asName) {
			$asName = $this->tmp->getFilename() . '.csv';
		}

		header('Content-Type: application/csv');
		header('Content-Length: ' . filesize($this->tmp->getRealPath()));
		header('Content-Disposition: attachment; filename="' . $asName . '"');

		readfile($this->tmp->getRealPath());

		exit;
	}

	/**
	 * The position to start iterating at.
	 *
	 * @param int $pos
	 * @return Interspire_Csv
	 */
	public function start($pos)
	{
		$this->tmp->start($pos);

		return $this;
	}

	/**
	 * The position to stop iterating at.
	 *
	 * @param int $pos
	 * @return Interspire_Csv
	 */
	public function stop($pos)
	{
		$this->tmp->stop($pos);

		return $this;
	}

	/**
	 * Retrieves a page of csv rows.
	 *
	 * @param int $page The page to get.
	 * @param int $limit The number of rows to get.
	 * @return array
	 */
	public function page($page = 1, $limit = 10)
	{
		$this->tmp->page($page, $limit);

		return $this;
	}

	/**
	 * Returns the current row of column values.
	 *
	 * @return array
	 */
	public function current()
	{
		return $this->offsetGet($this->key());
	}

	/**
	 * Sets the current element to the next csv row and increments the index.
	 *
	 * @return void
	 */
	public function next()
	{
		$this->tmp->next();

		return $this;
	}

	/**
	 * Returns the index of the current row.
	 *
	 * @return int
	 */
	public function key()
	{
		return $this->tmp->key();
	}

	/**
	 * Rewinds the internal file pointer.
	 *
	 * @return array
	 */
	public function rewind()
	{
		$this->tmp->rewind();

		return $this;
	}

	/**
	 * Returns whether or not the current iteration is valid.
	 *
	 * @return bool
	 */
	public function valid()
	{
		return $this->tmp->valid();
	}

	/**
	 * Checks to see if the given offset specified by $index exists.
	 *
	 * @param int $index
	 * @return bool
	 */
	public function offsetExists($index)
	{
		return (bool) $this->offsetGet($index);
	}

	/**
	 * Returns the specified offset. If it doesn't exist, null is returned.
	 *
	 * @param int $index
	 * @return array|null
	 */
	public function offsetGet($index)
	{
		// if it is a modified row that hasn't been written, return it
		if (array_key_exists($index, $this->writeCache)) {
			return $this->writeCache[$index];
		}

		// save old index to reset to
		$oldIndex = $this->key();

		// seek to new index
		$this->tmp->seek($index);

		// get the item
		// http://bugs.php.net/bug.php?id=46569 prevents us from calling fgetcsv on the file object after seeking
		// current() works as expected however, so read the item and then parse it through standard fgetcsv function from php memory stream
		$fp = fopen("php://memory", 'r+');
		fputs($fp, $this->tmp->current());
		rewind($fp);
		$csvInfo = $this->tmp->getCsvControl();
		$data = fgetcsv($fp, 1000, $csvInfo[0],	$csvInfo[1]);
		fclose($fp);

		// reset back to original index
		$this->tmp->seek($oldIndex);

		// return the retrieved item
		return $data;
	}

	/**
	 * Sets the specified offset to a particular value.
	 *
	 * @param int $index
	 * @param array|null $value
	 * @return Interspire_Csv
	 */
	public function offsetSet($index, $value)
	{
		// add to modified
		if (!is_numeric($index)) {
			return $this;
		}

		// set the value
		$this->writeCache[(int) $index] = $value;

		// if cache has reached limit, write cache to tmp file
		if (count($this->writeCache) >= $this->cacheSize) {
			$this->writeCache();
		}

		return $this;
	}

	/**
	 * Unsets a particular offset.
	 *
	 * @param int $index
	 * @return Interspire_Csv
	 */
	public function offsetUnset($index)
	{
		// just set the offset to null since
		// null values won't be added to the file
		$this->offsetSet($index, null);

		return $this;
	}

	/**
	 * Writes the current cache to the temporary file.
	 *
	 * @return Interspire_Csv
	 */
	protected function writeCache()
	{
		if (!$this->writeCache) {
			return $this;
		}

		// temporary file name
		$tmpName = tempnam(sys_get_temp_dir(), 'csv');

		// new temporary file
		$file = new Interspire_File($tmpName, 'w+');

		// record original position
		$originalPosition = $this->tmp->key();

		// reset to beginning
		$this->tmp->rewind();

		// go through each line, if inserting a
		while ($row = $this->tmp->fgetcsv()) {
			$key = $this->tmp->key();

			if (array_key_exists($key, $this->writeCache)) {
				$val = $this->writeCache[$key];

				// if not a valid value, continue
				if ($this->isValidRow($val)) {
					$file->fputcsv($val);
				}

				unset($this->writeCache[$key]);

				continue;
			}

			$file->fputcsv($row);
		}

		// apply any others that may have been appended
		foreach ($this->writeCache as $cacheRow) {
			if ($this->isValidRow($cacheRow)) {
				$file->fputcsv($cacheRow);
			}
		}

		// overwirte temp file reference
		$this->tmp = $file;

		// restore original position
		$this->tmp->seek($originalPosition);

		// make sure write cache is cleared
		$this->writeCache = array();

		return $this;
	}

	/**
	 * Checks to see if the passed in row is valid before being manipulated.
	 *
	 * @param array $row The row to check.
	 * @return bool
	 */
	protected function isValidRow($row)
	{
		return is_array($row) && count($row);
	}
}