<?php
/**
 * Basic Data Store Class for Interspire Shopping Cart
 *
 * This class serves as the basis for a basic data storage system
 * used to store frequently accessed items instead of always having
 * to load them from the database.
 *
 * It supports several cache methods - disk, memcached, APC etc
 * that can be used to store the data.
 */
class ISC_DATA_STORE
{
	/**
	 * @var object The object that will perform all of the data storage.
	 */
	private $handler = null;

	/**
	 * @var array An internal cache of loaded items from the data store.
	 */
	private $cache = array();

	/**
	 * The constructor. Sets up the data storage engine and connects to the handler.
	 */
	public function __construct()
	{
		require_once ISC_BASE_PATH.'/lib/class.datastore.disk.php';
		$this->handler = new ISC_DATA_STORE_DISK;
		$this->handler->Connect();
	}

	/**
	 * Deconstructor that disconnects from the data storage engine on script shutdown.
	 */
	public function __destruct()
	{
		$this->handler->Disconnect();
	}

	/**
	 * Read an item from the data store.
	 *
	 * @param string The name of the item to read from the data store.
	 * @param boolean Set to true to force a reload of this item if it's already been loaded previously.
	 * @param string A name to append to the end of the specified data store name. This is useful if you're caching items to the data store based on different groups.
	 * @return mixed The data from the data store.
	 */
	public function Read($name, $forceReload = false, $nameAppend='')
	{
		// Already cached this item? Return it
		if(isset($this->cache[$name.$nameAppend]) && $forceReload != true) {
			return $this->cache[$name.$nameAppend];
		}

		// Fetch this cached item from the handler
		$data = $this->handler->Read($name.$nameAppend);

		// Does the item not exist? If so, can we rebuild it?
		if($data === false) {
			if(method_exists($this, 'Update'.$name)) {
				$method = 'Update'.$name;

				// If we can't do an update stop here
				$data = $this->$method($nameAppend);
			}
		}

		// Cache the data internally
		$this->cache[$name.$nameAppend] = $data;

		return $data;
	}

	/**
	 * Write an item to the data store.
	 *
	 * @param string The name of the item to save to the data store.
	 * @param mixed The data to write to the data store.
	 * @param mixed The saved data if it were able to be saved successfully, false if not.
	 */
	public function Save($name, $data)
	{
		if($this->handler->Save($name, $data)) {
			return $data;
		}
		else {
			return false;
		}
	}

	/**
	 * Delete an item from the data store.
	 *
	 * @param string The name of the item to delete.
	 * @return boolean True if successful, false if there was an error.
	 */
	public function Delete($name)
	{
		return $this->handler->Delete($name);
	}

	/**
	 * Clear the content from the data store. Empties the entire data store cache.
	 *
	 * @return boolean True if successful, false if there was an error.
	 */
	public function Clear()
	{
		return $this->handler->Clear();
	}

	/**
	 * Force a reload of the cache. Basically reloads the file data back into the cache
	 *
	 * @param string The name of the item to reload from the data store.
	 * @param string A name to append to the end of the specified data store name. This is useful if you're caching items to the data store based on different groups.
	 * @return mixed The reloaded data from the data store.
	 */
	public function Reload($name, $nameAppend='')
	{
		return $this->Read($name, true, $nameAppend);
	}

	/**
	 * Update the currencies in the data store.
	 *
	 * @return mixed The data that was saved if successful, false if there was a problem saving the data.
	 */
	public function UpdateCurrencies()
	{
		$data = array();
		$query = "SELECT * FROM [|PREFIX|]currencies";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($currency = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$data[$currency['currencyid']] = $currency;
			if($currency['currencyisdefault'] == 1) {
				$data['default'] = $currency['currencyid'];
			}
		}

		return $this->Save('Currencies', $data);
	}

	/**
	 * Update the pages in the data store. The pages data store stores all of the pages used for the menu at the top of the store
	 *
	 * @return mixed The data that was saved if successful, false if there was a problem saving the data.
	 */
	public function UpdatePages()
	{
		$data = array();

		$query = "
			SELECT pagetitle, pagetype, pagelink, pageparentid, pageid, pagecustomersonly
			FROM [|PREFIX|]pages
			WHERE pageparentid='0' AND pagestatus='1' AND pageishomepage='0' AND pagevendorid='0'
			ORDER BY pagesort ASC, pagetitle ASC
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($page = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$data[$page['pageparentid']][$page['pageid']] = $page;
		}

		if(!empty($data)) {
			$parentids = implode(",", array_keys($data[0]));

			$query = "
				SELECT pagetitle, pagetype, pagelink, pageparentid, pageid, pagecustomersonly
				FROM [|PREFIX|]pages
				WHERE pageparentid IN (".$parentids.") AND pagestatus='1' AND pageishomepage='0' AND pagevendorid='0'
				ORDER BY pagesort ASC, pagetitle ASC
			";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			while($page = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$data[$page['pageparentid']][$page['pageid']] = $page;
			}
		}

		// Select the page that is the homepage for the store
		$query = "SELECT * FROM [|PREFIX|]pages WHERE pageishomepage='1' AND pagetype!='1'";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$data['defaultPage'] = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		return $this->Save('Pages', $data);
	}

	/**
	 * Update the root categories list in the data store.
	 *
	 * @return mixed The data that was saved if successful, false if there was a problem saving the data.
	 */
	public function UpdateRootCategories()
	{
		$nestedset = new ISC_NESTEDSET_CATEGORIES();

		$data = array();
		foreach ($nestedset->getTree(array('categoryid', 'catparentid', 'catname'), ISC_NESTEDSET_START_ROOT, GetConfig('CategoryListDepth') - 1, null, null, false, array('MIN(`parent`.`catvisible`) = 1')) as $category) {
			$data[(int)$category['catparentid']][(int)$category['categoryid']] = $category;
		}

		$this->Save('ChildCategories', array());
		return $this->Save('RootCategories', $data);
	}

	/**
	 * Update the customer groups in the data store.
	 *
	 * @return mixed The data that was saved if successful, false if there was a problem saving the data.
	 */
	public function UpdateCustomerGroups()
	{
		$data = array();
		$query = "SELECT * FROM [|PREFIX|]customer_groups";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($group = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			if($group['isdefault']) {
				$data['default'] = $group['customergroupid'];
			}

			$categories = array();
			//load access categories
			if ($group['categoryaccesstype'] == "specific") {
				$query = "SELECT * FROM [|PREFIX|]customer_group_categories WHERE customergroupid = " . $group['customergroupid'];
				$catres = $GLOBALS['ISC_CLASS_DB']->Query($query);
				while ($category = $GLOBALS['ISC_CLASS_DB']->Fetch($catres)) {
					$categories[] = $category['categoryid'];
				}
			}
			$group['accesscategories'] = $categories;

			$data[$group['customergroupid']] = $group;
		}

		return $this->Save('CustomerGroups', $data);
	}

	/**
	 * Update the customer groups category discounts cache
	 *
	 * @return mixed THe data that was saved if successful, false if there was a problem saving the data.
	 */
	public function UpdateCustomerGroupsCategoryDiscounts($customergroupId = 0)
	{
		$customergroupId = (int)$customergroupId;

		// fetch the customer groups to update based on the provided $customergroupId
		if ($customergroupId) {
			// dummy db entry
			$customergroups = array(
				array(
					'customergroupid' => $customergroupId,
				),
			);
		} else {
			// actual db results
			$customergroups = array();
			$result = $GLOBALS['ISC_CLASS_DB']->Query("SELECT customergroupid FROM [|PREFIX|]customer_groups");
			while ($customergroup = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$customergroups[] = $customergroup;
			}
			unset($result);
		}

		if (empty($customergroups)) {
			// nothing to update
			return false;
		}

		foreach ($customergroups as $customergroup) {
			$customergroupId = $customergroup['customergroupid'];

			// the nested set api will return rows in a hierarchy so that makes it easy to use a stack to track inheritence
			$currentDiscount = array(
				'groupdiscountid' => null,
				'discountpercent' => null,
				'appliesto' => null,
				'discountmethod' => null,
			);
			$nilDiscount = $currentDiscount;
			$stack = array($nilDiscount);
			$depth = 0;

			// utilise the nested set api's ability to generate an sql query to create a derived table for post-group-by joining
			$derived = new ISC_NESTEDSET_CATEGORIES;
			$derived = str_replace('SQL_CALC_FOUND_ROWS', '', $derived->generateGetTreeSql(array('categoryid', 'catparentid', 'catname')));

			$sql = "
				SELECT
					tree.*,
					cgd.groupdiscountid,
					cgd.discountpercent,
					cgd.appliesto,
					cgd.discountmethod

				FROM
					(" . $derived . ") as tree

				LEFT JOIN
					[|PREFIX|]customer_group_discounts cgd
					ON cgd.catorprodid = tree.categoryid
					AND cgd.discounttype = 'CATEGORY'
					AND cgd.customergroupid = " . $customergroupId . "
			";

			// data which will actually be saved to the datastore
			$data = array();

			$result = $GLOBALS['ISC_CLASS_DB']->Query($sql);
			while ($category = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$category['catdepth'] = (int)$category['catdepth'];

				if ($category['catdepth'] > $depth) {
					if ($currentDiscount['appliesto'] == 'CATEGORY_AND_SUBCATS') {
						// if we're going down a level and the discount from the parent applies to subcats put that on the stack
						array_push($stack, $currentDiscount);
					} else {
						// otherwise put the parent's-parent discount
						array_push($stack, current($stack));
					}
					end($stack);
				} else if ($category['catdepth'] < $depth) {
					array_pop($stack);
					end($stack);
				}

				if ($category['groupdiscountid'] === null) {
					// no discount defined by the db results so grab the one that current applies from the stack (which may also be a nil discount)
					$category = array_merge($category, current($stack));
				}

				if ($category['groupdiscountid'] !== null) {
					// if, after applying inheritence above, the category has a discount, store it for saving to the datastore
					$data[(int)$category['categoryid']] = array(
						'discountAmount' => $category['discountpercent'],
						'discountMethod' => $category['discountmethod'],
					);
				}

				// remember the current discount and depth for next iteration so we can remember inheritence
				$currentDiscount = array(
					'groupdiscountid' => $category['groupdiscountid'],
					'discountpercent' => $category['discountpercent'],
					'appliesto' => $category['appliesto'],
					'discountmethod' => $category['discountmethod'],
				);

				$depth = $category['catdepth'];
			}

			$result = $this->Save('CustomerGroupsCategoryDiscounts' . $customergroupId, $data);
			if (!$result) {
				return false;
			}
		}

		return $data;
	}

	/**
	 * Update the news items in the data store.
	 *
	 * @return mixed The data that was saved if successful, false if there was a problem saving the data.
	 */
	public function UpdateNews()
	{
		$data = array();
		$query = "SELECT * FROM [|PREFIX|]news WHERE newsvisible='1'";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($news = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$data[$news['newsid']] = $news;
		}

		return $this->Save('News', $data);
	}

	/**
	 * Update a type of module variables in the data store.
	 *
	 * @param string The type of module variable files to save.
	 * @return mixed The data that was saved if successful, false if there was a problem saving the data.
	 */
	private function UpdateModuleVars($type)
	{
		$data = array();
		switch($type) {
			case "Checkout":
				$dbType = 'checkout';
				$enabledMethods = GetConfig('CheckoutMethods');
				break;
			case "Shipping":
				$dbType = 'shipping';
				$enabledMethods = GetConfig('ShippingMethods');
				break;
			case "Notification":
				$dbType = 'notification';
				$enabledMethods = GetConfig('NotificationMethods');
				break;
			case "Analytics":
				$dbType = 'analytics';
				$enabledMethods = GetConfig('AnalyticsMethods');
				break;
			case "LiveChat":
				$dbType = 'livechat';
				$enabledMethods = GetConfig('LiveChatMethods');
				break;
			case "Addon":
				$dbType = 'addon';
				$enabledMethods = GetConfig('AddonModules');
				break;
			case "Accounting":
				$dbType = 'accounting';
				$enabledMethods = GetConfig('AccountingMethods');
				break;
			case "EmailIntegration":
				$dbType = 'emailintegration';
				$enabledMethods = GetConfig('EmailIntegrationMethods');
				break;
			default:
				return false;
		}

		$enabledMethods = explode(',', $enabledMethods);
		if(!empty($enabledMethods)) {
			foreach($enabledMethods as $method) {
				$data[$method] = array();
			}
		}

		$query = "SELECT * FROM [|PREFIX|]module_vars WHERE modulename LIKE '".$dbType."\_%'";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($var = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			// If it's already been set, then this variable is an array, so restructure it as so
			if(isset($data[$var['modulename']][$var['variablename']])) {
				if(!is_array($data[$var['modulename']][$var['variablename']])) {
					$data[$var['modulename']][$var['variablename']] = array($data[$var['modulename']][$var['variablename']]);
				}
				$data[$var['modulename']][$var['variablename']][] = $var['variableval'];
				continue;
			}
			$data[$var['modulename']][$var['variablename']] = $var['variableval'];
		}

		return $this->Save($type.'ModuleVars', $data);
	}

	/**
	 * Update the addon module variables in the data store.
	 *
	 * @return mixed The data that was saved if successful, false if there was a problem saving the data.
	 */
	public function UpdateAddonModuleVars()
	{
		return $this->UpdateModuleVars('Addon');
	}

	/**
	 * Update the accounting module variables in the data store.
	 *
	 * @return mixed The data that was saved if successful, false if there was a problem saving the data.
	 */
	public function UpdateAccountingModuleVars()
	{
		return $this->UpdateModuleVars('Accounting');
	}

	/**
	 * Update the checkout module variables in the data store.
	 *
	 * @return mixed The data that was saved if successful, false if there was a problem saving the data.
	 */
	public function UpdateCheckoutModuleVars()
	{
		return $this->UpdateModuleVars('Checkout');
	}

	/**
	 * Update the email integration module variables in the data store.
	 *
	 * @return mixed The data that was saved if successful, false if there was a problem saving the data.
	 */
	public function UpdateEmailIntegrationModuleVars()
	{
		ISC_EMAILINTEGRATION::flushEnabledModuleCache();
		return $this->UpdateModuleVars('EmailIntegration');
	}

	/**
	 * Update the shipping module variables in the data store.
	 *
	 * @return mixed The data that was saved if successful, false if there was a problem saving the data.
	 */
	public function UpdateShippingModuleVars()
	{
		return $this->UpdateModuleVars('Shipping');
	}

	/**
	 * Update the analytics module variables in the data store.
	 *
	 * @return mixed The data that was saved if successful, false if there was a problem saving the data.
	 */
	public function UpdateAnalyticsModuleVars()
	{
		return $this->UpdateModuleVars('Analytics');
	}

	/**
	 * Update the notification module variables in the data store.
	 *
	 * @return mixed The data that was saved if successful, false if there was a problem saving the data.
	 */
	public function UpdateNotificationModuleVars()
	{
		return $this->UpdateModuleVars('Notification');
	}

	/**
	 * Update the live chat module variables in the data store.
	 *
	 * @return mixed The data that was saved if successful, false if there was a problem saving the data.
	 */
	public function UpdateLiveChatModuleVars()
	{
		return $this->UpdateModuleVars('LiveChat');
	}

	/**
	 * Update the vendor list in the data store.
	 *
	 * @return mixed The data that was saved if successful, false if there was a problem saving the data.
	 */
	public function UpdateVendors()
	{
		$data = array();
		$query = "
			SELECT vendorid, vendorname, vendorfriendlyname, vendorshipping
			FROM [|PREFIX|]vendors
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($vendor = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$data[$vendor['vendorid']] = $vendor;
		}

		return $this->Save('Vendors', $data);
	}

	/**
	 * Update the gift wrapping list in the data store.
	 *
	 * @return mixed The data that was saved if successful, false if there was a problem saving the data.
	 */
	public function UpdateGiftWrapping()
	{
		$data = array();
		$query = 'SELECT * FROM [|PREFIX|]gift_wrapping';
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($wrap = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$data[$wrap['wrapid']] = $wrap;
		}

		return $this->Save('GiftWrapping', $data);
	}



	/**
	 * Update the store wide optimizer test list in the data store.
	 *
	 * @return mixed The data that was saved if successful, false if there was a problem saving the data.
	 */
	public function UpdateOptimizerData()
	{
		$optimizerData = array();
		$query = "SELECT
						*
					FROM
						[|PREFIX|]module_vars
					WHERE
						modulename like 'optimizer_%%'";

		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($optimizer = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$curModule = $optimizer['modulename'];
			$varName = $optimizer['variablename'];
			$optimizerData[$curModule][$varName] = $optimizer['variableval'];

		}
		return $this->Save('OptimizerData', $optimizerData);
	}

	/**
	 * Update the customer group default tax zone data store.
	 *
	 * @return mixed The data that was saved if successful, false if there was a problem saving the data.
	 */
	public function UpdateDefaultTaxZones()
	{
		$defaultTaxZones = array();

		// Calculate the default for no customer group
		$defaultTaxZones[0] = getClass('ISC_TAX')->determineTaxZoneForAddress(
			getConfig('taxDefaultCountry'),
			getConfig('taxDefaultState'),
			getConfig('taxDefaultZip'),
			0
		);

		$query = "
			SELECT customergroupid
			FROM [|PREFIX|]customer_groups
		";
		$result = $GLOBALS['ISC_CLASS_DB']->query($query);
		while($group = $GLOBALS['ISC_CLASS_DB']->fetch($result)) {
			$defaultTaxZones[$group['customergroupid']] = getClass('ISC_TAX')->determineTaxZoneForAddress(
				getConfig('taxDefaultCountry'),
				getConfig('taxDefaultState'),
				getConfig('taxDefaultZip'),
				$group['customergroupid']
			);
		}

		return $this->save('DefaultTaxZones', $defaultTaxZones);
	}

}
