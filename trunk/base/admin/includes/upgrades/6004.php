<?php

/**
 * Upgrade class for 6.0.4
 * This class runs a series of methods used to upgrade the store to a specific version
 *
 * @package ISC
 * @subpackage ISC_Upgrade
 */
class ISC_ADMIN_UPGRADE_6004 extends ISC_ADMIN_UPGRADE_BASE
{
	public $steps = array(
		'addEmailProviderListFieldsSettingsColumn',
		'populate_missing_variation_combination',
		'add_disable_google_checkout_column_to_products_table',
		'create_new_shopping_comparison_tables',
		'populate_new_shopping_comparison_tables',
		'drop_old_shopping_comparison_tables',
	);

	public function addEmailProviderListFieldsSettingsColumn ()
	{
		if (!$this->ColumnExists('[|PREFIX|]email_provider_list_fields', 'settings')) {
			$query = "ALTER TABLE [|PREFIX|]email_provider_list_fields ADD COLUMN `settings` TEXT NOT NULL";
			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function populate_missing_variation_combination()
	{
		// script to fix ISC-859
		// find products with missing variation combination
		$query = "
		SELECT
			productid, prodvariationid
		FROM
			[|PREFIX|]products
		WHERE
			prodvariationid != 0 AND
			productid NOT IN (
				SELECT DISTINCT
					p.productid
				FROM
					[|PREFIX|]products p,
					[|PREFIX|]product_variation_combinations c
				WHERE
					p.prodvariationid != 0 AND
					c.vcproductid = p.productid
			);
		";
		$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
		while($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {
			// populate default combinations in the db table
			// so that update query will work, and user can save
			$pid = $row['productid'];
			$vid = $row['prodvariationid'];
			$query = "
			SELECT
				voptionid, voname
			FROM
				[|PREFIX|]product_variation_options
			WHERE
				vovariationid='".$GLOBALS['ISC_CLASS_DB']->Quote($vid)."'
			ORDER BY
				vooptionsort, vovaluesort
			";
			$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
			$optionIds = array();
			while($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {
				$optionIds[$row['voname']][] = $row['voptionid'];
			}
			GetClass('ISC_ADMIN_PRODUCT')->SaveCombinations('', $optionIds, $pid, $vid);
		}

		return true;
	}

	public function add_disable_google_checkout_column_to_products_table()
	{
		if ($this->ColumnExists('[|PREFIX|]products', 'disable_google_checkout') == false) {
			$query = "ALTER TABLE [|PREFIX|]products ADD COLUMN disable_google_checkout int(1) NOT NULL default '0'";
			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function create_new_shopping_comparison_tables()
	{
		$db = $GLOBALS['ISC_CLASS_DB'];

		$createTables = array(
			// alternate_categories -> shopping_comparison_categories
			"shopping_comparison_categories" =>
				"CREATE TABLE IF NOT EXISTS `[|PREFIX|]shopping_comparison_categories` (
					`id` int(11) NOT NULL,
					`shopping_comparison_id` varchar(255) NOT NULL,
					`parent_id` int(11) NOT NULL,
					`name` varchar(255) NOT NULL,
					`path` varchar(255) NOT NULL,
					`num_children` int(11) NOT NULL default '0',
					PRIMARY KEY  (`shopping_comparison_id`, `id`),
					KEY `i_shopping_comparison_categories_path` (`path`),
					KEY `i_shopping_comparison_categories_comparison_id` (`shopping_comparison_id`)
				) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE utf8_general_ci;",

			// alternate_category_taxonomies -> shopping_comparison_taxonomies
			"shopping_comparison_taxonomies" =>
				"CREATE TABLE IF NOT EXISTS `[|PREFIX|]shopping_comparison_taxonomies` (
					`id` varchar(255) NOT NULL,
					`filename` varchar(255) NOT NULL,
					`last_updated` int(11) NOT NULL,
					PRIMARY KEY  (`id`)
				) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE utf8_general_ci;",

			// categories_to_alternate_categories -> shopping_comparison_category_associations
			"shopping_comparison_category_associations" =>
				"CREATE TABLE IF NOT EXISTS `[|PREFIX|]shopping_comparison_category_associations` (
					`category_id` int(11) NOT NULL,
					`shopping_comparison_id` varchar(255) NOT NULL,
					`shopping_comparison_category_id` int(11) NOT NULL,
					PRIMARY KEY  (`category_id`, `shopping_comparison_id`)
				) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE utf8_general_ci;",
			);

		foreach($createTables as $table => $createQuery) {
			if(!$db->query($createQuery)) {
					$this->setError($db->getErrorMsg());
					return false;
			}
		}

		return true;
	}

	public function populate_new_shopping_comparison_tables()
	{
		$db = $GLOBALS['ISC_CLASS_DB'];

		$insertQueries = array(
			// alternate_categories -> shopping_comparison_categories
			"alternate_categories" =>
				"INSERT INTO [|PREFIX|]shopping_comparison_categories (id, shopping_comparison_id, parent_id, name, path, num_children)
					SELECT
						category_id, taxonomy_id, parent_id, name, path, num_children
					FROM
						[|PREFIX|]alternate_categories;",

			// alternate_category_taxonomies -> shopping_comparison_taxonomies
			"alternate_category_taxonomies" =>
				"INSERT INTO [|PREFIX|]shopping_comparison_taxonomies (id, filename, last_updated)
					SELECT
						taxonomy_id, filename, lastupdated
					FROM
						[|PREFIX|]alternate_category_taxonomies;",

			// categories_to_alternate_categories -> shopping_comparison_category_associations
			"categories_to_alternate_categories" =>
				"INSERT INTO [|PREFIX|]shopping_comparison_category_associations (category_id, shopping_comparison_id, shopping_comparison_category_id)
					SELECT
						category_id, alternate_taxonomy_id, alternate_category_id
					FROM
						[|PREFIX|]categories_to_alternate_categories;",
			);

		foreach($insertQueries as $table => $query)
		{
			if($this->tableExists($table)) {
				if(!$db->query($query)) {
					$this->setError($db->getErrorMsg());
					return false;
				}
			}
		}

		return true;
	}

	public function drop_old_shopping_comparison_tables()
	{
		$db = $GLOBALS['ISC_CLASS_DB'];

		$dropTables = array(
			'alternate_categories',
			'alternate_category_taxonomies',
			'categories_to_alternate_categories',
			);

		foreach($dropTables as $table) {
			if($this->tableExists($table)) {
				$query = 'DROP TABLE [|PREFIX|]'.$table;
				if(!$db->query($query)) {
					$this->setError($db->getErrorMsg());
					return false;
				}
			}
		}

		return true;
	}
}
