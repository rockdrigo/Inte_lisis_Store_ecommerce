<?php
	require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'class.api.php');

	class API_CATEGORY extends API
	{
		// {{{ Class variables
		public $fields = array (
			'categoryid',
			'catname',
			'catdesc',
			'catparentid',
			'catviews',
			'catsort',
			'catpagetitle',
			'catmetakeywords',
			'catmetadesc',
			'catsearchkeywords',
			'catlayoutfile',
			'catparentlist',
			'catimagefile',
			'cataltcategoriescache',
			'cat_enable_optimizer',
		);

		protected $defaultvalues = array(
			'cataltcategoriescache' => ''
		);

		public $categoryid = 0;
		public $catname = '';
		public $catdesc = '';
		public $catparentid = 0;
		public $catsort = 0;
		public $catviews = 0;
		public $catpagetitle = '';
		public $catmetakeywords = '';
		public $catmetadesc = '';
		public $catsearchkeywords = '';
		public $catlayoutfile = '';
		public $catparentlist = '';
		public $catimagefile = '';
		public $cataltcategoriescache = '';
		public $cat_enable_optimizer = '';

		// }}}

		// {{{ setupDatabase()
		/**
		* Setup the connection to the database and some other database
		* properties
		*
		* @return void
		*/
		public function setupDatabase()
		{
			$this->db = $GLOBALS['ISC_CLASS_DB'];
			$tableSuffix = 'categories';
			$this->table = '[|PREFIX|]'.$tableSuffix;
			$this->tablePrefix = '[|PREFIX|]';
		}
		// }}}

		/**
		* Create a new item in the database
		*
		* @return mixed false if failed to create, the id of the item otherwise
		*/
		public function create($updateCache = true)
		{
			$_POST['catparentlist'] = '';
			$_POST['catviews'] = 0;
			if (!$this->CategoryExists($_POST['catparentid'], $_POST['catname'])) {
				$CatId = parent::create();

				// If the save was successful
				if($CatId) {
					if ($updateCache) {
						// adjust the nested set data for the new category
						$nested = new ISC_NESTEDSET_CATEGORIES();
						$nested->adjustInsertedNode($CatId, (int)$_POST['catparentid']);

						// If the category doesn't have a parent, rebuild the root categories cache
						if($_POST['catparentid'] == 0) {
							$GLOBALS['ISC_CLASS_DATA_STORE']->UpdateRootCategories();
						}

						// Rebuild the group pricing caches
						$GLOBALS['ISC_CLASS_DATA_STORE']->UpdateCustomerGroupsCategoryDiscounts();
					}

					// Also save our search record
					$this->saveSearch($CatId);

					// Save the words to the category_words table for search spelling suggestions
					Store_SearchSuggestion::manageSuggestedWordDatabase("category", $CatId, $_POST["catname"]);
				}

				return $CatId;
			} else {
				$this->error = sprintf(GetLang('apiCatAlreadyExists'), $_POST['catname']);
				return false;
			}
		}

		/**
		 * Save the category record
		 *
		 * Method will save the category record
		 *
		 * @access public
		 * @return bool TRUE if the category was saved successfully, FALSE if not
		 */
		public function save()
		{
			if (!parent::save()) {
				return false;
			}

			$CatId = $this->categoryid;
			$this->saveSearch($CatId);

			// Save the words to the category_words table for search spelling suggestions
			Store_SearchSuggestion::manageSuggestedWordDatabase("category", $CatId, $_POST["catname"]);

			return true;
		}

		/**
		 * Save our search record
		 *
		 * Method will add/update the search record
		 *
		 * @access private
		 * @param int $catId The category ID
		 * @return bool TRUE if the search was added/edited successfully, FALSE if not
		 */
		private function saveSearch($catId)
		{
			if (!isId($catId)) {
				return false;
			}

			// Update our search record
			$savedata = array(
				"categoryid" => $catId,
				"catname" => $_POST["catname"],
				"catdesc" => stripHTMLForSearchTable($_POST["catdesc"]),
				"catsearchkeywords" => $_POST["catsearchkeywords"]
			);

			$query = "SELECT categorysearchid
						FROM [|PREFIX|]category_search
						WHERE categoryid=" . (int)$catId;

			$searchId = $this->db->FetchOne($query);

			if (isId($searchId)) {
				$rtn = $this->db->UpdateQuery("category_search", $savedata, "categorysearchid=" . (int)$searchId);
			} else {
				$rtn = $this->db->InsertQuery("category_search", $savedata);
			}

			if ($rtn === false) {
				return false;
			}

			return true;
		}


		/**
		* delete
		* Delete a category, if $id is given and is positive then delete it and
		* delete any category associations it may have
		*
		* @param int $id The id of the category to delete
		*
		* @return bool Was the delete successful ?
		*/
		public function delete($id=0)
		{
			return $this->multiDelete(array($id));
		}

		/**
		* Delete multiple categories in one database query, useful for bulk
		* actions
		*
		* @param $ids array The array of ids to delete.
		*
		* @return boolean Return true on successful deletion
		*/
		public function multiDelete($ids=0)
		{
			$nestedSet = new ISC_NESTEDSET_CATEGORIES();

			$ids = array_keys($ids);

			// To run database maintenance after the deletion, we need a list of all the categories that are going to be deleted
			$deleted = array();

			foreach ($ids as $id) {
				$deleted = array_merge($deleted, $nestedSet->getTree(array('categoryid'), $id));
			}

			// Delete the categories
			if (parent::multiDeleteNestedSet($nestedSet, $ids) === false) {
				return false;
			}

			// Rebuild the group pricing caches
			$GLOBALS['ISC_CLASS_DATA_STORE']->UpdateCustomerGroupsCategoryDiscounts();

			// If the category doesn't have a parent, rebuild the root categories cache
			$GLOBALS['ISC_CLASS_DATA_STORE']->UpdateRootCategories();

			$child_cats = array();
			foreach ($deleted as $deletedNode) {
				$child_cats[] = $deletedNode['categoryid'];
			}

			//delete the discount rules associated with these categories
			$query = "DELETE FROM ".$this->tablePrefix."customer_group_discounts where discounttype='CATEGORY' AND catorprodid IN (".(implode(',', $child_cats)).")";
			$this->db->Query($query);

			// Delete the search records
			$query = "DELETE FROM ".$this->tablePrefix."category_search where categoryid IN (".(implode(',', $child_cats)).")";
			$this->db->Query($query);

			// Delete any category associations we have
			$this->DeleteCategoryProducts($child_cats);
			return true;
		}

		/**
		 * DeleteCategoryProducts
		 * Delete any products associated with any of the listed categories
		 *
		 * @param array $ids Array of IDs for the categories being removed
		 */
		public function DeleteCategoryProducts($ids)
		{
			if(!is_array($ids)) {
				$ids = array($ids);
			}

			// Delete any category associations
			$query = "DELETE FROM ".$this->tablePrefix."categoryassociations WHERE categoryid IN (".implode(",", $ids).")";
			$this->db->Query($query);

			// Now we check to see if there are any products without an associated category & remove them too
			$productIds = array();
			$query = "SELECT p.prodname, p.productid FROM ".$this->tablePrefix."products p LEFT JOIN ".$this->tablePrefix."categoryassociations ca ON (ca.productid=p.productid) WHERE ca.categoryid IS NULL";
			$result = $this->db->Query($query);
			while($product = $this->db->Fetch($result)) {
				$productIds[] = $product['productid'];
			}
			// Any products to delete?
			if(!empty($productIds)) {
				$GLOBALS['ISC_CLASS_ADMIN_PRODUCT'] = GetClass('ISC_ADMIN_PRODUCT');
				$GLOBALS['ISC_CLASS_ADMIN_PRODUCT']->DoDeleteProducts($productIds);
			}
		}

		/**
		* CategoryExists
		* Check to see if a category with a given name exists under a given
		* parent categoryid
		*
		* @param int $parentid The id of the parent
		* @param int $name The name of the category
		*
		* @return boolean Does the category exist or not ?
		*/
		public function CategoryExists($parentid, $name)
		{
			if (!$this->is_positive_int($parentid)) {
				return false;
			}

			$query = "SELECT COUNT(*)
			FROM [|PREFIX|]categories
			WHERE catparentid='".$this->db->Quote($parentid)."'
			AND catname='".$this->db->Quote($name)."'";

			$result = $this->db->Query($query);

			$num = $this->db->FetchOne($result);

			if ($num > 0) {
				return true;
			} else {
				return false;
			}
		}

		/**
		* validate_categoryid
		*
		* Ensure the categoryid is a pos int
		*
		* @param string $var
		*
		* @return bool
		*/
		public function validate_categoryid($var)
		{
			return $this->is_positive_int($var);
		}

		/**
		* validate_catname
		*
		* Ensure the name isn't empty or too long
		*
		* @param string $var
		*
		* @return bool
		*/
		public function validate_catname($var)
		{
			if (empty($var)) {
				$this->error = GetLang('apiCatNameEmpty');
				return false;
			}

			if (isc_strlen($var) > 50) {
				$this->error = GetLang('apiCatNameLong');
				return false;
			}

			// Make sure a category cannot be renamed to have the same name
			// as an existing category at the same level
			if ($this->loaded) {
				if ($this->CategoryExists($this->catparentid, $var)) {
					$this->error = GetLang('apiCatAlreadyExists');
					return false;
				}
			}

			return true;
		}

		/**
		* validate_catparentid
		*
		* Ensure the catparentid is a pos int
		*
		* @param string $var
		*
		* @return bool
		*/
		public function validate_catparentid($var)
		{
			return $this->is_positive_int($var);
		}

		/**
		* validate_catviews
		*
		* Ensure the catviews is a pos int
		*
		* @param string $var
		*
		* @return bool
		*/
		public function validate_catviews($var)
		{
			return $this->is_positive_int($var);
		}

		/**
		 * Build the parent list for a particular category.
		 *
		 * @param int The category ID
		 * @return string The build parent list
		 */
		public function BuildParentList($catid)
		{
			$set = new ISC_NESTEDSET_CATEGORIES();
			$parents = $set->getParentPath(array('categoryid'), $catid);
			$res = array();
			foreach ($parents as $p) {
				$res[] = $p['categoryid'];
			}

			return implode(',', $res);
		}

		/**
		 * Find the IDs of all subcategories.
		 *
		 * @param array $ids The array of category IDs.
		 * @param array $excludes The array of IDs to exclude.
		 * @return array The array of all subcategory IDs
		 */
		public function getSubCategories($ids=0, $excludes=array())
		{
			if (is_array($ids) == false) {
				$ids = array($ids);
			}

			// Find sub category IDs.
			$cats = array();
			$nestedSet = new ISC_NESTEDSET_CATEGORIES();
			foreach ($ids as $id) {
				if (in_array($id, $excludes) == true) {
					continue;
				}
				$cats = array_merge($cats, $nestedSet->getTree(array('categoryid'), $id));
			}

			$subcats = array();
			foreach ($cats as $cat) {
				$subcats[] = $cat['categoryid'];
			}

			$res = array_diff($subcats, $excludes);
			return $res;
		}

		/**
		 * Find all the exclusively linked product IDs under categories.
		 * These products will be orphaned if parent category is deleted.
		 *
		 * @param array $ids The array of category IDs
		 * @return int The total number of products.
		 */
		public function getExclusiveProductsForCategories($ids)
		{
			$query = '
				SELECT
					productid
				FROM
					[|PREFIX|]products
				WHERE
					productid NOT IN (
						SELECT DISTINCT
							productid
						FROM
							[|PREFIX|]categoryassociations
						WHERE
							categoryid NOT IN ('.(implode(',', $ids)).')
					)';
			$res = $this->db->query($query);
			$prod = array();
			while($row = $this->db->fetch($res)) {
				$prod[] = $row['productid'];
			}

			return $prod;
		}
	}