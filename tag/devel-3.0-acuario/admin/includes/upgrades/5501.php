<?php
/**
 * Upgrade class for 5.5.1
 * This class runs a series of methods used to upgrade the store to a specific version
 *
 * @package ISC
 * @subpackage ISC_Upgrade
 */

class ISC_ADMIN_UPGRADE_5501 extends ISC_ADMIN_UPGRADE_BASE
{
	public $steps = array(
		"fix_pages_structure",
	);

	public function fix_pages_structure()
	{
		$db = &$GLOBALS['ISC_CLASS_DB'];

		// fix any pages with self assigned as parent
		$query = "UPDATE `[|PREFIX|]pages` set pageparentid=0 WHERE pageid = pageparentid";
		$result = $db->Query($query);

		if(!$result) {
			$this->SetError('Unable to repair self-parent pages. MySQL said: ' . $db->GetErrorMsg());
			return false;
		}

		require_once(ISC_BASE_PATH . "/lib/tree.php");

		// get all pages
		$pageTree = new Tree();

		$query = "
			SELECT *
			FROM [|PREFIX|]pages
			WHERE pageid > 0
		";

		$query .= "ORDER BY pagesort ASC, pagetitle ASC";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$pagesById[$row['pageid']] = $row;
			$tree->nodesByPid[(int) $row['pageparentid']][] = (int) $row['pageid'];
		}

		// get flat array of all page IDs
		$allPages = array_keys($pagesById);

		$pagesInTree = array();

		// loop through tree structure, making a note of all page ids
		$this->_get_all_valid_tree_pages($pageTree, $pagesInTree);

		// compare ids found in the tree with flat list of all ids to find any orphans
		$orphans = array_diff($allPages, $pagesInTree);

		if(!empty($orphans) && is_array($orphans)) {
			$query = "UPDATE `[|PREFIX|]pages` set pageparentid=0 WHERE pageid IN (" . implode(',', $orphans) . ")";
			$result = $db->Query($query);
			if(!$result) {
				$this->SetError('Unable to repair bad parent-child relationships between pages. MySQL said: ' . $db->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function _get_all_valid_tree_pages($pageTree, &$pagesInTree, $parentId=0)
	{
		if(!isset($pageTree->nodesByPid[$parentId])) {
			return;
		}

		// get all children of this parent item
		$children = $pageTree->nodesByPid[$parentId];

		foreach($children as $childId) {
			if(in_array($childId, $pagesInTree)) {
				return;
			}
			$pagesInTree[] = $childId;
			$this->_get_all_valid_tree_pages($pageTree, $pagesInTree, $childId);
		}
	}
}
