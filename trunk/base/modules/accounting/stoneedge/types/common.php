<?php
/**
 * Count the number of items in the database
 *
 * Method to return a count of items in the database (orders, products, customers, etc.)
 *
 * @access public
 * @return string the count.
 */
function StoneEdgeCount($table,$whereCond = '')
{
	if($table == 'products') {
		$query = StoneEdgeProductQueryCount();
		$count = (int)$GLOBALS['ISC_CLASS_DB']->FetchOne($query);

	} else {
		$query = $GLOBALS['ISC_CLASS_DB']->Query("SELECT COUNT(*) FROM [|PREFIX|]$table " .  $whereCond);
		$count = (int)$GLOBALS['ISC_CLASS_DB']->FetchOne($query);
	}
	//it's ok to return zero.
	return $count;
}

/**
 * Return a valid SQL query
 *
 * Method to create a common SQL query for the products
 *
 * @access public
 * @return string the valid SQL query.
 */
function StoneEdgeProductQuery($end='')
{
	return "SELECT * FROM `[|PREFIX|]products` p
		LEFT JOIN [|PREFIX|]product_images i ON (p.productid = i.imageprodid AND i.imageisthumb=1)
		LEFT JOIN `[|PREFIX|]product_variation_combinations` vc ON p.productid = vc.vcproductid
		 " . $end;
}

/**
 * Return a valid SQL query
 *
 * Method to create a common SQL query for the product row count
 *
 * @access public
 * @return string the valid SQL query.
 */

function StoneEdgeProductQueryCount($end='')
{
	return "SELECT COUNT(*) FROM `[|PREFIX|]products` p
		LEFT JOIN [|PREFIX|]product_images i ON (p.productid = i.imageprodid AND i.imageisthumb=1)
		LEFT JOIN `[|PREFIX|]product_variation_combinations` vc ON p.productid = vc.vcproductid
		" . $end;
}

/**
 * Return a valid SQL query
 *
 * Method to create a common SQL query for the orders
 *
 * @access public
 * @return string the valid SQL query.
 */

function StoneEdgeOrderQuery($whereCond="WHERE o.ordstatus > 0 ORDER BY o.orderid ASC")
{
	return "SELECT o.*,cs.stateabbrv,os.method FROM [|PREFIX|]orders o
	LEFT JOIN [|PREFIX|]country_states cs ON cs.statename=o.ordbillstate
	LEFT JOIN [|PREFIX|]order_shipping os ON os.order_id = o.orderid " . $whereCond;
}

/**
 * Return a valid SQL query
 *
 * Method to create a common SQL query for the product row count
 *
 * @access public
 * @return string the valid SQL query.
 */

function StoneEdgeOrderQueryCount($whereCond="WHERE o.ordstatus > 0 ORDER BY o.orderid ASC")
{
	return "SELECT COUNT(*) FROM [|PREFIX|]orders o
	LEFT JOIN [|PREFIX|]country_states cs ON cs.statename=o.ordbillstate " .$whereCond;
}
