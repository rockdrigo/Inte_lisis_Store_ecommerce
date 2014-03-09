<?php
class ISC_ADMIN_CSVPARSER
{
	public $File;
	public $MaxRecords = 0;
	public $FileFP = null;
	public $LastRecord = 0;
	public $TotalRecords = 0;

	public $FieldSeparator = ",";
	public $FieldEnclosure = "\"";

	public $AutoDetectLineEndings = "";

	/**
	 * Open a CSV file for parsing
	 *
	 * @param string The file to open for parsing
	 * @return resource
	 */
	public function OpenCSVFile($file, $startPosition = 0, $MaxRecords = 0)
	{
		$this->MaxRecords = $MaxRecords;

		// Save our original setup
		$this->AutoDetectLineEndings = ini_get("auto_detect_line_endings");

		// Apply the auto detecting line endings so we can split each line correctly
		ini_set("auto_detect_line_endings", "1");

		if(!is_file($file)) {
			trigger_error("Invalid file", E_USER_ERROR);
		}
		$this->FileFP = fopen($file, "r");

		// Seeking to a certain part of the file
		if($startPosition > 0) {
			fseek($this->FileFP, $startPosition);
		}
	}

	/**
	 * Return the current position that we're at in the file we have open.
	 *
	 * @return int The current position we're at in the file.
	 */
	public function GetCurrentPosition()
	{
		return @ftell($this->FileFP);
	}

	/**
	 * Get the number of records we've parsed in this file so far.
	 *
	 * @return int The number of records parsed.
	 */
	public function GetRecordNum()
	{
		return $this->LastRecord;
	}

	/**
	 * Fetch the next record from the open CSV file
	 */
	public function FetchNextRecord($add_original=false)
	{
		// Reached the end of the file
		if(!$this->FileFP) {
			return false;
		}

		// We've reached the max iterations we'll be doing per page
		if($this->LastRecord == $this->MaxRecords && $this->MaxRecords > 0) {
			return false;
		}

		$record = @fgetcsv($this->FileFP, 100000, $this->FieldSeparator, $this->FieldEnclosure);
		if (is_array($record) && GetConfig('CharacterSet') == 'UTF-8' && function_exists('utf8_encode')) {
			// ISC-583
			// if the store is running as utf8, try our best to ensure records read by fgetcsv are utf8 encoded because
			// database import routines are going to assume stuff is utf8 encoded
			// note that fgetcsv may have already broken the string beyond repair in regards to the original characters
			// (since fgetcsv relies on the system locale) but at least we can ensure mysql will not complain when it
			// is eventually sent as a query
			foreach ($record as $index => $field) {
				if (Interspire_String::isUtf8($field)) {
					continue;
				}
				$record[$index] = utf8_encode($field);
			}
		}

		$new_record = $record;
		if(is_array($record) && isset($this->FieldList)) {
			$new_record = array();
			foreach($this->FieldList as $field => $index) {

				/**
				 * Custom field check
				 */
				if ($field == 'custom' && is_array($index)) {
					foreach ($index as $fieldId => $fieldIndex) {
						if (array_key_exists($fieldIndex, $record)) {
							if (!array_key_exists('custom', $new_record) || !is_array($new_record['custom'])) {
								$new_record['custom'] = array();
							}

							$new_record['custom'][$fieldId] = trim($record[$fieldIndex]);
						}
					}

				/**
				 * Else normal field
				 */
				} else {
					if (!is_scalar($index)) {
						// this shouldn't be happening here and is causing a problem with array_key_exists below which only takes scalar values in argument 1 -- I can't trace it back at the moment, only add the check to skip it -GE
						continue;
					}
					if (!array_key_exists($index, $record)) {
						continue;
					}

					$new_record[$field] = trim($record[$index]);
				}
			}
		}
		if(is_array($new_record) && isset($add_original) && $add_original == true) {
			$new_record['original_record'] = $record;
		}

		// Reached the end of the file
		if(@feof($this->FileFP) || $record === false) {
			@fclose($this->FileFP);
		}

		++$this->LastRecord;

		return $new_record;
	}

	/**
	 * Sets the list of field names, used by FetchNextRecord to return a pretty array in the format we want.
	 *
	 * @param array Numerical-indexed array of records.
	 */
	public function SetRecordFields($fields=array())
	{
		$this->FieldList = $fields;
	}

	public function CloseCSVFile()
	{
		fclose($this->FileFP);

		//revert our ini set changes
		ini_set("auto_detect_line_endings", $this->AutoDetectLineEndings);
	}
}

if(!function_exists("array_combine")) {
	function array_combine($keys, $values)
	{
		$out = array();
		foreach($keys as $key1 => $value1) {
			$out[$value1] = $values[$key1];
		}
		return $out;
	}
}