<?php
class Isc_ShoppingComparison_Taxonomy
{
	protected $filename;
	protected $taxonomyId;

	public function __construct($id, $file)
	{
		$this->taxonomyId = $id;
		$this->filename = $file;
	}

	protected function isLoaded()
	{
		$db = $GLOBALS['ISC_CLASS_DB'];
		$query = '
			SELECT
				last_updated
			FROM
				[|PREFIX|]shopping_comparison_taxonomies
			WHERE
				filename = "'. basename($this->filename) .'"
			AND
				id = "'. $this->taxonomyId .'";';

		$result = $db->FetchOne($query);

		return !empty($result);
	}

	protected function updateTaxonomyInformation()
	{
		$db = $GLOBALS['ISC_CLASS_DB'];
		$filename = basename($this->filename);

		$query = '
			INSERT INTO
				[|PREFIX|]shopping_comparison_taxonomies
				(id, filename, last_updated)
			VALUES
				("'.$this->taxonomyId.'", "'.$filename.'", '.time().')
			ON DUPLICATE KEY UPDATE filename = "'.$filename.'", last_updated = '.time().';';

		$db->Query($query);
	}

	/**
	 * Loads a taxonomy file into memory, creates parent -> child
	 * associations and saves them to the database. Todo: This is
	 * not the optimal way to do this, potential memory issues
	 * and timeouts with large taxonomies (nextag). Different
	 * approach is required.
	 */
	public function load($force=false)
	{
		if($this->isLoaded() && !$force)
			return;

		$this->deleteAll();

		/**
		 * @todo Consider using load data infile if possible eg:
		 * $db = $GLOBALS['ISC_CLASS_DB'];
		 * $q = "load data infile 'nextag.txt' into table isc_shopping_comparison_categories;";
		 * $db->Query($q);
		 */

		$file = fopen($this->filename, 'r');
		$paths = array();
		$autoGenCatId = 1;
		//$fp = fopen("/tmp/taxonomy.err", 'w+');

		while(!feof($file) && $line = fgets($file))
		{
			list($id, $categoryPath) = explode("\t", $line);

			preg_match('/(.*)(( > )|^)(.+)/', $categoryPath, $matches);

			$path = $matches[1];
			$leaf = $matches[4];

			if(!$path)
				$parent = 0;
			else if(isset($paths[$path]))
				$parent = $paths[$path];
			else
			{
				// No ancestors data, generate our own ancestors with
				// auto generated ids.

				$ancestors = explode(" > ", $path);
				$ancestorPath = "";
				$parent = 0;

				foreach($ancestors as $ancestor) {
					$lastPath = $ancestorPath;

					if($ancestorPath)
						$ancestorPath .= ' > '.$ancestor;
					else
						$ancestorPath = $ancestor;

					if(isset($paths[$ancestorPath])){
						//fwrite($fp, '>> Ancestor found for $ancestorPath : {$paths[$ancestorPath]}\n');
						$parent = $paths[$ancestorPath];
						continue;
					}

					//fwrite($fp, ">> Name: $ancestor, AutoId:$autoGenCatId, P:$parent, Path:$ancestorPath\n");
					$this->insert($ancestor, $autoGenCatId, $parent, $lastPath);
					$paths[$ancestorPath] = $autoGenCatId;
					$parent = $autoGenCatId++;
				}
			}

			//fwrite($fp, "\tLeaf: $leaf, Id: $id, P: $parent, Path:$path\n");
			$this->insert($leaf, $id, $parent, $path);

			if($parent)
				$this->incrementChildren($parent);

			if($path)
				$paths[$path.' > '.$leaf] = $id;
			else
				$paths[$leaf] = $id;
		}

		//fclose($fp);
		fclose($file);

		$this->updateTaxonomyInformation();
	}

	/**
	 * Returns an array of the parent categories for a
	 * given child category id.
	 */
	public function getParentCategories($id)
	{
		$db = $GLOBALS['ISC_CLASS_DB'];
		$parentids = array();

		do
		{
			$query = '
				SELECT
					parent_id
				FROM
					[|PREFIX|]shopping_comparison_categories
				WHERE
					id = '.(int)$id.'
					AND shopping_comparison_id = "'.$db->Quote($this->taxonomyId).'";';

			$id = $db->FetchOne($query);
			$parentids[] = $id;
		}while($id);

		return $parentids;
	}

	/**
	 * Returns an array of sub categories for a given
	 * parent category id.
	 *
	 * @param integer category id
	 */
	public function getSubcategories($id)
	{
		$db = $GLOBALS['ISC_CLASS_DB'];

		$query = '
			SELECT
				*
			FROM
				[|PREFIX|]shopping_comparison_categories
			WHERE
				parent_id = '.(int)$id.'
				AND shopping_comparison_id = "'.$db->Quote($this->taxonomyId).'"
			ORDER BY
				name;';

		if(!($result = $db->Query($query)))
			return false;

		$children = array();

		while($row = $db->Fetch($result))
			$children[] = $row;

		return $children;
	}

	private function insert($category, $id, $parent, $path)
	{
		$db = $GLOBALS['ISC_CLASS_DB'];

		$query = '
			INSERT INTO [|PREFIX|]shopping_comparison_categories
				(shopping_comparison_id, id, parent_id, name, path)
			VALUES
				("'.$db->Quote($this->taxonomyId).'", '. $id .', "'. $parent .'", "'
				. $db->Quote($category) . '", "'. $db->Quote($path) . '");';

		$result = $db->Query($query);

		if($result)
			return $db->LastId();

		return false;
	}

	private function incrementChildren($categoryid)
	{
		$db = $GLOBALS['ISC_CLASS_DB'];

		$query = '
			UPDATE [|PREFIX|]shopping_comparison_categories
			SET
				num_children = num_children + 1
			WHERE
				shopping_comparison_id = "'.$db->Quote($this->taxonomyId).'"
				AND id = '.$categoryid.';';

		return $db->Query($query);
	}

	private function deleteAll()
	{
		$query = 'DELETE FROM [|PREFIX|]shopping_comparison_categories where shopping_comparison_id = "'.$this->taxonomyId.'";';
		return $GLOBALS['ISC_CLASS_DB']->Query($query);
	}
}