<?php

/**
* This class provides an API for database-based nested set hierarchy models.
*
* Based on http://dev.mysql.com/tech-resources/articles/hierarchical-data.html
*
* To implement this functionality on a database table, the following setup is recommended:
*
* - Extend this class to a new class named after the table such as ISC_NESTEDSET_CATEGORIES
* - Define the extendee class's constructor to setup the correct column names
* - Add two int columns to the table to be managed for storing LEFT and RIGHT values, they need (at most) twice the storage space of the primary key column to guarantee full availability of numbers
* - Add an index that includes the database pkey, left and right columns
* - Add another index for only the left column
*/
class ISC_NESTEDSET {

	/**
	* Database table name to manage
	*
	* @var string
	*/
	private $_tableName;

	/**
	* Primary key column of the database table. Only numeric columns are supported.
	*
	* @var string
	*/
	private $_pkeyColumn;

	/**
	* Name of the column containing a row's parent id. Only numeric columns are supported.
	*
	* @var string
	*/
	private $_parentColumn;

	/**
	* A list of of the columns used for sorting - usually referred to as sort column or display order and may be followed by another fallback column for when the sorting columns are equal.
	*
	* @var array
	*/
	private $_sortingColumns;

	/**
	* Name of the column used for storing left values.
	*
	* @var string
	*/
	private $_leftColumn;

	/**
	* Name of the column used for storing right values.
	*
	* @var string
	*/
	private $_rightColumn;

	/**
	* The column name that should be used for depth calculations. To avoid confusion, the column should not already exist in the table.
	*
	* @var string
	*/
	private $_depthColumn;

	/**
	*
	* @param string $tableName The name of the table in the database to attach to
	* @param string $pkeyColumn The name of the column containing primary keys
	* @param string $parentColumn The name of the column containing parent ids
	* @param array $sortingColumns The names of the columns used for sorting on a per-tree-level basis - currently only ASC sorts are supported
	* @param string $leftColumn The name of the column containing left ids
	* @param string $rightColumn The name of the column containing right ids
	* @param string $depthColumn The name to use as the calculated column containing depth data
	* @return ISC_NESTEDSET
	*/
	public function __construct($tableName, $pkeyColumn, $parentColumn, $sortingColumns, $leftColumn, $rightColumn, $depthColumn)
	{
		$this->_tableName = $tableName;
		$this->_pkeyColumn = $pkeyColumn;
		$this->_parentColumn = $parentColumn;
		$this->_sortingColumns = $sortingColumns;
		$this->_leftColumn = $leftColumn;
		$this->_rightColumn = $rightColumn;
		$this->_depthColumn = $depthColumn;
	}

	/**
	* Generates and returns the SQL statement used for retrieving a tree from a nested set table.
	*
	* @see getTree()
	* @return string
	*/
	public function generateGetTreeSql($columns, $startingNodeId = ISC_NESTEDSET_START_ROOT, $depth = ISC_NESTEDSET_DEPTH_ALL, $limit = null, $limitOffset = null, $countDepth = true, $restrictions = array(), $exclusions = array())
	{
		// the nested set query which calculates depth of a sub-tree can handle all queries but at a performance cost
		// if this query becomes too slow, for certain situations it's possible to use simpler queries from the mysql docs above
		// if alternative queries are implemented, change the NOT YET IMPLEMENTED note above

		$columnString = "`node`.`" . implode('`, `node`.`', $columns) .'`';

		if ($startingNodeId === ISC_NESTEDSET_START_ROOT) {
			// generalised query when selecting a whole tree

			$sql = "
				SELECT SQL_CALC_FOUND_ROWS /*ISC_NESTEDSET::generateGetTreeSql*/ " . $columnString . ", (COUNT(`parent`.`" . $columns[0] . "`) - 1) AS `" . $this->_depthColumn . "`
				FROM `[|PREFIX|]" . $this->_tableName . "` AS `node`,
					`[|PREFIX|]" . $this->_tableName . "` AS `parent`
				WHERE `node`.`" . $this->_leftColumn . "` BETWEEN `parent`.`" . $this->_leftColumn . "` AND `parent`.`" . $this->_rightColumn . "`
				GROUP BY `node`.`" . $this->_pkeyColumn . "`
				/*%HAVING%*/ /*%HAVING_RESTRICTIONS%*/
				ORDER BY `node`.`" . $this->_leftColumn . "`
				/*%LIMIT%*/";

		} else {
			// generalised query when selecting a sub-tree

			$sql = "
				SELECT SQL_CALC_FOUND_ROWS /*ISC_NESTEDSET::generateGetTreeSql*/ " . $columnString . ", (COUNT(`parent`.`" . $columns[0] . "`) - (`sub_tree`.`" . $this->_depthColumn . "` + 1)) AS `" . $this->_depthColumn . "`
				FROM `[|PREFIX|]" . $this->_tableName . "` AS `node`,
					`[|PREFIX|]" . $this->_tableName . "` AS `parent`,
					`[|PREFIX|]" . $this->_tableName . "` AS `sub_parent`,
					(
						SELECT " . $columnString . ", (COUNT(`parent`.`" . $columns[0] . "`) - 1) AS `" . $this->_depthColumn . "`
						FROM `[|PREFIX|]" . $this->_tableName . "` AS `node`,
							`[|PREFIX|]" . $this->_tableName . "` AS `parent`
						WHERE `node`.`" . $this->_leftColumn . "` BETWEEN `parent`.`" . $this->_leftColumn . "` AND `parent`.`" . $this->_rightColumn . "`
							AND `node`.`" . $this->_pkeyColumn . "` = " . (int)$startingNodeId . "
						GROUP BY `node`.`" . $this->_pkeyColumn . "`
						ORDER BY `node`.`" . $this->_leftColumn . "`
					) AS `sub_tree`
				WHERE `node`.`" . $this->_leftColumn . "` BETWEEN `parent`.`" . $this->_leftColumn . "` AND `parent`.`" . $this->_rightColumn . "`
					AND `node`.`" . $this->_leftColumn . "` BETWEEN `sub_parent`.`" . $this->_leftColumn . "` AND `sub_parent`.`" . $this->_rightColumn . "`
					AND `sub_parent`.`" . $this->_pkeyColumn . "` = `sub_tree`.`" . $this->_pkeyColumn . "`
				GROUP BY `node`.`" . $this->_pkeyColumn . "`
				/*%HAVING%*/ /*%HAVING_RESTRICTIONS%*/
				ORDER BY `node`.`" . $this->_leftColumn . "`
				/*%LIMIT%*/";
		}

		if ($depth !== ISC_NESTEDSET_DEPTH_ALL) {
			$restrictions[] = "`" . $this->_depthColumn . "` <= " . (int)$depth;
		}

		if (!empty($exclusions)) {
			$exclusions = array_map('intval', $exclusions);
			$restrictions[] = "(SELECT COUNT(*) FROM `[|PREFIX|]" . $this->_tableName . "` `sub_node`, `[|PREFIX|]" . $this->_tableName . "` `sub_parent` WHERE `sub_node`.`" . $this->_leftColumn . "` BETWEEN `sub_parent`.`" . $this->_leftColumn . "` AND `sub_parent`.`" . $this->_rightColumn . "` AND `sub_node`.`" . $this->_pkeyColumn . "` = `node`.`" . $this->_pkeyColumn . "` AND `sub_parent`.`" . $this->_pkeyColumn . "` IN (" . implode(',', $exclusions) . ")) = 0";
		}

		if (!empty($restrictions)) {
			$sql = str_replace('/*%HAVING%*/', 'HAVING', $sql);

			$restrictionsString = " (" . implode(") AND (", $restrictions) . ")";
			$sql = str_replace('/*%HAVING_RESTRICTIONS%*/', $restrictionsString, $sql);
		}

		if ($limit !== null) {

			$limitString = "LIMIT ";

			if ($limitOffset !== null) {
				$limitString .= $limitOffset . "," . $limit;
			} else {
				$limitString .= $limit;
			}

			$sql = str_replace('/*%LIMIT%*/', $limitString, $sql);
		}

		return $sql;
	}

	/**
	* Generates and returns the SQL statement used for finding a list of parent nodes in the tree.
	*
	* @see getParentPath()
	* @return string
	*/
	public function generateGetParentPathSql($columns, $startingId)
	{
		$columnString = "`parent`.`" . implode('`, `parent`.`', $columns) .'`';

		$sql = "
				SELECT /*ISC_NESTEDSET::generateGetParentPathSql*/ " . $columnString . "
				FROM `[|PREFIX|]" . $this->_tableName . "` AS `node`,
					`[|PREFIX|]" . $this->_tableName . "` AS `parent`
				WHERE `node`.`" . $this->_leftColumn . "` BETWEEN `parent`.`" . $this->_leftColumn . "` AND `parent`.`" . $this->_rightColumn . "`
				AND `node`.`" . $this->_pkeyColumn . "` = " . (int)$startingId . "
				ORDER BY `parent`.`" . $this->_leftColumn . "`";

		return $sql;
	}

	/**
	* Queries the database and retrieves an array of record results based on input tree parameters. If memory issues arise with using this in certain situations with large lists, you should use generateGetTreeSql and perform your own row handling.
	*
	* @param array A list of column names to retrieve from database. This must be an array and it must not contain the * specifier to select all columns due to possible issues with MySQL grouping and aggregate functions used to calculate tree depths.
	* @param int $startingNodeId Record id to begin at
	* @param int $depth Number of levels below to retrieve or ISC_NESTEDSET_DEPTH_ALL
	* @param int $limit Amount of rows to return
	* @param int $limitOffset Amount of rows to offset
	* @param boolean $countDepth Count and retrieve depth information, turn off to save some calculations in the query (NOT YET IMPLEMENTED but included in parameters for future use)
	* @param array $restrictions A set of criteria to restrict items returned on, should be suitable for a MySQL HAVING clause using current- and parent-node data
	* @param array $exclusions A list of record ids (by primary key) to exclude from the result, will also exclude their child nodes
	* @return array
	*/
	public function getTree($columns, $startingNodeId = ISC_NESTEDSET_START_ROOT, $depth = ISC_NESTEDSET_DEPTH_ALL, $limit = null, $limitOffset = null, $countDepth = true, $restrictions = array(), $exclusions = array())
	{

		$sql = $this->generateGetTreeSql($columns, $startingNodeId, $depth, $limit, $limitOffset, $countDepth, $restrictions, $exclusions);

		$result = $GLOBALS['ISC_CLASS_DB']->Query($sql);

		$rows = array();

		while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$rows[] = $row;
		}

		return $rows;
	}

	/**
	* Given a starting record id, this will query the database and return an array of parent tree records.
	*
	* @param array A list of column names to retrieve from database.
	* @param int $startingId Record id to begin at
	*/
	public function getParentPath($columns, $startingId)
	{

		$sql = $this->generateGetParentPathSql($columns, $startingId);

		$result = $GLOBALS['ISC_CLASS_DB']->Query($sql);

		$rows = array();

		while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$rows[] = $row;
		}

		return $rows;
	}

	/**
	* Given an existing node id $nodeId, this will add $spacesToInsert spaces in the left/right number values to allow for inserting of $spacesToInsert nodes either before or after the given node id.
	*
	* @param int $nodeId
	* @param int $spacesToInsert
	* @param boolean $before
	* @param boolean $inside
	* @return int The value returned will be the first available left value of the newly inserted spaces
	*/
	private function _insertSpace($nodeId, $spacesToInsert = 1, $before = false, $inside = false)
	{
		$nodeId = (int)$nodeId;
		$sqls = array();

		$sqls[] = "LOCK TABLE `[|PREFIX|]" . $this->_tableName . "` WRITE";

		// discover and store the right value of the node we're inserting spaces after
		if ($before) {
			// when inserting before a node, the right value is equal to the left value of the target node, minus one

			$leftAdjustment = ' - 1';
			if ($inside) {
				$leftAdjustment = '';
			}

			$sqls[] = "SELECT @myright := `" . $this->_leftColumn . "` " . $leftAdjustment . " FROM `[|PREFIX|]" . $this->_tableName . "` WHERE `" . $this->_pkeyColumn . "` = " . $nodeId;

		} else {

			$rightAdjustment = '';
			if ($inside) {
				$rightAdjustment = ' - 1';
			}

			$sqls[] = "SELECT @myright := `" . $this->_rightColumn . "` " . $rightAdjustment . " FROM `[|PREFIX|]" . $this->_tableName . "` WHERE `" . $this->_pkeyColumn . "` = " . $nodeId;

		}

		// increase the left/right values of nodes that are "after" the area in the tree to be inserted
		$sqls[] = "UPDATE `[|PREFIX|]" . $this->_tableName . "` SET `" . $this->_rightColumn . "` = `" . $this->_rightColumn . "` + " . ($spacesToInsert * 2) . " WHERE `" . $this->_rightColumn . "` > @myright";
		$sqls[] = "UPDATE `[|PREFIX|]" . $this->_tableName . "` SET `" . $this->_leftColumn . "` = `" . $this->_leftColumn . "` + " . ($spacesToInsert * 2) . " WHERE `" . $this->_leftColumn . "` > @myright";

		$sqls[] = "UNLOCK TABLES";

		$sqls[] = "SELECT @myright as `right`";

		foreach ($sqls as $sql) {
			// don't halt on error because UNLOCK TABLES must always execute
			$result = $GLOBALS['ISC_CLASS_DB']->Query($sql);
		}

		// the final result will contain the previous right value
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		return (int)$row['right'] + 1;
	}

	/**
	* Given an existing node id $afterNodeId, this will add $spacesToInsert spaces in the left/right number values to allow for inserting of $spacesToInsert nodes after (to the right of) the given node id.
	*
	* @param int $afterNodeId
	* @param int $spacesToInsert
	* @return int The first available left value of the newly inserted spaces
	*/
	public function insertSpaceAfter($afterNodeId, $spacesToInsert = 1)
	{
		return $this->_insertSpace($afterNodeId, $spacesToInsert);
	}

	/**
	* Given an existing node id $beforeNodeId, this will add $spacesToInsert spaces in the left/right number values to allow for inserting of $spacesToInsert nodes before (to the left of) the given node id.
	*
	* @param int $afterNodeId
	* @param int $spacesToInsert
	* @return int The first available left value of the newly inserted spaces
	*/
	public function insertSpaceBefore($beforeNodeId, $spacesToInsert = 1)
	{
		return $this->_insertSpace($beforeNodeId, $spacesToInsert, true);
	}

	/**
	* Given an existing node id, this will append or prepend $spacesToInsert spaces in the left/right number values to allow for inserting of $spacesToInsert nodes below the given node id.
	*
	* @param int $insideNodeId
	* @param int $spacesToInsert
	* @param boolean $prepend
	* @return int The first available left value of the newly inserted spaces
	*/
	public function insertSpaceBelow($belowNodeId, $spacesToInsert = 1, $prepend = false)
	{
		return $this->_insertSpace($belowNodeId, $spacesToInsert, $prepend, true);
	}

	/**
	* Given an existing node id, this will append $spacesToInsert spaces in the left/right number values to allow for inserting of $spacesToInsert nodes below the given node id.
	*
	* @param int $insideNodeId
	* @param int $spacesToInsert
	* @param boolean $prepend
	* @return int The first available left value of the newly inserted spaces
	*/
	public function appendSpaceBelow($belowNodeId, $spacesToInsert = 1)
	{
		return $this->_insertSpace($belowNodeId, $spacesToInsert, false, true);
	}

	/**
	* Given an existing node id, this will prepend $spacesToInsert spaces in the left/right number values to allow for inserting of $spacesToInsert nodes below the given node id.
	*
	* @param int $insideNodeId
	* @param int $spacesToInsert
	* @param boolean $prepend
	* @return int The first available left value of the newly inserted spaces
	*/
	public function prependSpaceBelow($belowNodeId, $spacesToInsert = 1)
	{
		return $this->_insertSpace($belowNodeId, $spacesToInsert, true, true);
	}

	/**
	* Returns true if the given $nodeId exists otherwise false
	*
	* @param int $nodeId
	* @return bool
	*/
	public function nodeExists($nodeId)
	{
		$sql = "SELECT COUNT(*) as `count` FROM `[|PREFIX|]" . $this->_tableName . "` WHERE `" . $this->_pkeyColumn . "` = " . (int)$nodeId;
		$result = $GLOBALS['ISC_CLASS_DB']->Query($sql);
		if (!$result) {
			return false;
		}

		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		if (!$row || (int)$row['count'] < 1) {
			return false;
		}

		return true;
	}

	/**
	* This function will delete a given node id and all child nodes. Before deleting, it is recommended that you use getTree to find all child nodes that will be deleted and perform appropriate maintenance since mysql will not automatically cascade deletes to other tables.
	*
	* @param int $nodeId
	* @return boolean Returns true on success or if $nodeId does not exist and doesn't need to be deleted. Returns false if any of the queries failed. The error message can be taken from the DB class. Some records may have been deleted even if this returns false.
	*/
	public function deleteNode($nodeId)
	{
		$nodeId = (int)$nodeId;
		if (!$this->nodeExists($nodeId)) {
			return true;
		}

		$sqls = array();

		$sqls[] = "LOCK TABLE `[|PREFIX|]" . $this->_tableName . "` WRITE";

		// discover and store left, right and total "width" values for the node to be deleted
		// left and right values are used for deleting child nodes, while the width value is used for adjusting remaining node left/right values to compensate for the deleted space
		$sqls[] = "SELECT @myleft := `" . $this->_leftColumn . "`, @myright := `" . $this->_rightColumn . "`, @mywidth := `" . $this->_rightColumn . "` - `" . $this->_leftColumn . "` + 1 FROM `[|PREFIX|]" . $this->_tableName . "` WHERE `" . $this->_pkeyColumn . "` = " . $nodeId;

		// remove all nodes from the deleted node down
		$sqls[] = "DELETE FROM `[|PREFIX|]" . $this->_tableName . "` WHERE `" . $this->_leftColumn . "` BETWEEN @myleft AND @myright";

		// shrink the remaining left and right values
		$sqls[] = "UPDATE `[|PREFIX|]" . $this->_tableName . "` SET `" . $this->_rightColumn . "` = `" . $this->_rightColumn . "` - @mywidth WHERE `" . $this->_rightColumn . "` > @myright";
		$sqls[] = "UPDATE `[|PREFIX|]" . $this->_tableName . "` SET `" . $this->_leftColumn . "` = `" . $this->_leftColumn . "` - @mywidth WHERE `" . $this->_leftColumn . "` > @myright";

		$sqls[] = "UNLOCK TABLES";

		$success = true;
		foreach ($sqls as $sql) {
			// don't halt on error because UNLOCK TABLES must always execute
			if ($GLOBALS['ISC_CLASS_DB']->Query($sql) === false) {
				$success = false;
			}
		}

		return $success;
	}

	/**
	* This function is called recursively over a tree structure built by rebuildTree() to populate left/right values.
	*
	* @param mixed $tree
	* @param mixed $left
	*/
	private function _populateTreeLeftRightValues(&$tree, &$left = 0)
	{

		foreach ($tree as &$node) {
			$node['left'] = ++$left;
			$this->_populateTreeLeftRightValues($node['childnodes'], $left);
			$node['right'] = ++$left;
		}
	}

	/**
	* Rebuilds the entire nested set tree of the associated table.
	*
	* @return boolean returns false if any of the queries in the process failed, otherwise true
	*/
	public function rebuildTree()
	{

		$sql = "SELECT `" . $this->_pkeyColumn . "` as `pkey`, `" . $this->_parentColumn . "` as `parentid` FROM `[|PREFIX|]" . $this->_tableName . "` ORDER BY `" . implode('`,`', $this->_sortingColumns) . "`";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($sql);
		if ($result === false) {
			return false;
		}

		$rows = array();

		//
		// build a flat list of rows from the database and add some values we need for processing

		while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$row['childnodes'] = array();
			$row['left'] = 0;
			$row['right'] = 0;
			$row['parent'] = null;
			$rows[] = $row;
		}

		//
		// fill in the parent-child structure using a temporary array using database table pkey values as the array key

		$structure = array();

		foreach ($rows as &$row) {
			$structure[$row['pkey']] = &$row;
		}

		foreach ($rows as &$row) {
			if (!empty($structure[$row['parentid']]) && $row['parentid']) {
				$structure[$row['parentid']]['childnodes'][] = &$row;
			}
		}

		unset($rows); // this array isn't needed any more

		//
		// make a final structure array that doesn't include every row at the root level

		$tree = array();

		foreach ($structure as &$row) {
			if (empty($structure[$row['parentid']]) || !$row['parentid']) {
				// row has no matching parent record in the tree, it could be parentid = 0 or parentid = (invalid row id)
				// either way, this qualifies it as belonging at the root level, otherwise it would never receive a left/right value since it's not linked to anything in the set
				$tree[] = &$row;
			}
		}

		//
		// call the recursive value populating function

		$this->_populateTreeLeftRightValues($tree);

		//
		// update all left/right values from the database

		foreach ($structure as &$row) {
			$sql = "UPDATE `[|PREFIX|]" . $this->_tableName . "` SET `" . $this->_leftColumn . "` = " . $row['left'] . ", `" . $this->_rightColumn . "` = " . $row['right'] . " WHERE `" . $this->_pkeyColumn . "` = " . $row['pkey'];
			if ($GLOBALS['ISC_CLASS_DB']->Query($sql) === false) {
				return false;
			}
		}

		return true;
	}

	/**
	* Given a node id, returns the sibling nodes based on the sorting columns. This is useful if you have inserted a record into the database with 0 for nested-set left/right and need to find "where" it is in the tree compared to existing nodes.
	*
	* @param array $columns
	* @param int $nodeId
	* @return array|mixed Returns false if $nodeId was not found at all. Returns array where index 0 is the previous node, index 1 is the next node. Array values will contain an array of column values specified by $columns, but may be false if there was no next or previous node found because it is at the end of a set, or false for both indexes if the node is the only child of a parent node.
	*/
	public function getSiblingNodesBySortingValues($columns, $nodeId)
	{
		$columnString = "`" . implode('`, `', $columns) .'`';
		$sql = "SELECT " . $columnString . " FROM `[|PREFIX|]" . $this->_tableName . "` WHERE `" . $this->_parentColumn . "` = (SELECT `" . $this->_parentColumn . "` FROM `[|PREFIX|]" . $this->_tableName . "` WHERE `" . $this->_pkeyColumn . "` = " . $nodeId . ") ORDER BY ";
		$sql .= implode(",", $this->_sortingColumns);
		$result = $GLOBALS['ISC_CLASS_DB']->Query($sql);

		$found = false;
		$previous = false;

		while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			if ((int)$row[$this->_pkeyColumn] === $nodeId) {
				$found = true;
				$next = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
				break;
			}
			$previous = $row;
		}

		if (!$found) {
			return false;
		}

		return array($previous, $next);
	}

	/**
	* Given the node id of a newly inserted node with 0 for it's left / right values, this function will adjust the nested set data for the table to finalise the insertion of the node (based on this table's sorting values) into the nested set. This should be called whenever a node is inserted into the database without knowing what it's true left/right values should be.
	*
	* @param int $nodeId The id of the node to be updated
	* @param int $parentNodeId The parent node id for this node
	* @return void
	*/
	public function adjustInsertedNode($nodeId, $parentNodeId)
	{
		// at this point the node has been created in the database but with 0 for nested-set left and right values
		// based on the defined sorting columns for this table, we need to find the node it has been inserted "after" or "before" or "inside of" so we can adjust the nested set values properly
		$siblings = $this->getSiblingNodesBySortingValues(array($this->_pkeyColumn), $nodeId);

		if ($siblings[0] === false && $siblings[1] === false) {
			// new node is an only child, we need space to be inserted below the parent node
			$left = $this->appendSpaceBelow($parentNodeId);
		} else if ($siblings[0] === false) {
			// new node is at the beginning of a set
			$left = $this->insertSpaceBefore((int)$siblings[1][$this->_pkeyColumn]);
		} else {
			// new node is at the end of a set
			$left = $this->insertSpaceAfter((int)$siblings[0][$this->_pkeyColumn]);
		}

		// the insertSpace methods above will return the first available left value, so we use that, and the new right value is simply left + 1
		$update = array();
		$update[$this->_leftColumn] = $left;
		$update[$this->_rightColumn] = $left + 1;

		// save changes to the node
		$GLOBALS['ISC_CLASS_DB']->UpdateQuery($this->_tableName, $update, $this->_pkeyColumn . " = '" . $GLOBALS['ISC_CLASS_DB']->Quote($nodeId) . "'");
	}
}
