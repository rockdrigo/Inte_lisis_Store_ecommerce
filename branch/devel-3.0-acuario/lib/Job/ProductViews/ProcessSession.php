<?php

class Job_ProductViews_ProcessSession extends Job_Store_Abstract
{
	/** @var int the number of records to insert in one batch when summarising log information */
	const VIEW_SUMMARISE_INSERT_BATCH = 100;

	/** @var Db */
	protected $_db;

	public function setUp ()
	{
		parent::setUp();
		$this->_db = $GLOBALS['ISC_CLASS_DB'];
	}

	public function perform ()
	{
		$time = time();

		// build multiline insert query to summarise product views in old session
		$insertCounter = 0;
		foreach ($this->args['viewedProducts'] as $productA) {
			foreach ($this->args['viewedProducts'] as $productB) {
				if ($productB == $productA) {
					// ids are equal, do not record
					continue;
				}
				// note: the above check used to discard duplicate records (like 1,2 and 2,1) but it was found, for SELECT db performance reasons, that it was better to store those duplicates

				if ($insertCounter == 0) {
					$sql = "INSERT INTO `[|PREFIX|]product_related_byviews` (prodida, prodidb, relevance, lastview) VALUES ";
				} else {
					$sql .= ",";
				}

				$sql .= "(" . $productA . "," . $productB . ",1," . $time . ")";
				$insertCounter++;

				if ($insertCounter === self::VIEW_SUMMARISE_INSERT_BATCH) {
					// bundle and insert VIEW_SUMMARISE_INSERT_BATCH records at a time
					$sql .= " ON DUPLICATE KEY UPDATE relevance = relevance + 1, lastview = " . $time;
					if (!$this->_db->Query($sql)) {
						return false;
					}
					$insertCounter = 0;
				}
			}
		}

		if ($insertCounter) {
			// loops ended with rows left to insert - finalise
			$sql .= " ON DUPLICATE KEY UPDATE relevance = relevance + 1, lastview = " . $time;
			if (!$this->_db->Query($sql)) {
				return false;
			}
		}

		return true;
	}
}
